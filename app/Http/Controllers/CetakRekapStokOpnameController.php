<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class CetakRekapStokOpnameController extends Controller
{
    // 1. Menampilkan Halaman Form Cetak Universal
    public function index()
    {
        $pengelolaList = DB::table('tbpengelolajurusan')
            ->select('id', DB::raw('IFNULL(nama_pengelola, nama) as nama'), 'nip', 'jabatan')
            ->orderBy('jabatan', 'asc')
            ->get();

        return view('laporan.formcetakuniversal', compact('pengelolaList'));
    }

    // 2. Memproses Query & Mencetak PDF
    public function cetakPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        try {
            $opsi_cetak = $request->input('opsi_cetak', 'semua');
            
            if ($opsi_cetak === 'semua') {
                $tgl_mulai   = null;
                $tgl_selesai = null;
            } else {
                $tgl_mulai   = $request->input('tgl_mulai');
                $tgl_selesai = $request->input('tgl_selesai');
            }

            $jenis_ttd = $request->input('jenis_ttd', 'manual');
            $mode_ttd  = $request->input('mode_ttd', 'single');

            // --- DATA BUKU BESAR & STOK OPNAME ---
            $masterBarangList = DB::table('tbmasterbarang')
                ->select('id', 'namaBarang')
                ->orderBy('namaBarang', 'asc')
                ->get();

            $bukuBesar = [];

            foreach ($masterBarangList as $master) {
                $anakBarangList = DB::table('tbanakbarang as ab')
                    ->leftJoin('tbsatuan as st', 'ab.idsatuan', '=', 'st.id')
                    ->select(
                        'ab.id', 
                        'ab.merkBarang', 
                        'ab.spesifikasi', 
                        DB::raw('IFNULL(st.jenisBarang, "Pcs") as namaSatuan')
                    )
                    ->where('ab.idMaster', $master->id)
                    ->get();

                $itemsData = [];

                foreach ($anakBarangList as $anak) {
                    // Query Barang Masuk
                    $queryBM = DB::table('tbtransaksibarangmasuk as bm')
                        ->leftJoin('tbdosen as d_penerima', DB::raw('bm.penerima COLLATE utf8mb4_general_ci'), '=', DB::raw('d_penerima.nip COLLATE utf8mb4_general_ci'))
                        ->select(
                            'bm.tglMasuk as tanggal',
                            'bm.jumlah',
                            'bm.namaSupplier as supplier',
                            DB::raw('IFNULL(d_penerima.nama, bm.penerima) as penerima'),
                            'bm.keterangan'
                        )
                        ->where('bm.idAnak', $anak->id);

                    if ($opsi_cetak === 'filter' && $tgl_mulai && $tgl_selesai) {
                        $queryBM->whereBetween('bm.tglMasuk', [$tgl_mulai, $tgl_selesai]);
                    }

                    $barangMasuk = $queryBM->orderBy('bm.tglMasuk', 'asc')->get();

                    // Query Barang Keluar
                    $queryBK = DB::table('tbtransaksibarangkeluar as bk')
                        ->leftJoin('tbdosen as d_petugas', DB::raw('bk.petugas COLLATE utf8mb4_general_ci'), '=', DB::raw('d_petugas.nip COLLATE utf8mb4_general_ci'))
                        ->leftJoin('tbdosen as d_penerima', DB::raw('bk.penerima COLLATE utf8mb4_general_ci'), '=', DB::raw('d_penerima.nip COLLATE utf8mb4_general_ci'))
                        ->select(
                            'bk.tglKeluar as tanggal',
                            'bk.jumlah',
                            DB::raw('IFNULL(d_petugas.nama, bk.petugas) as petugas'),
                            DB::raw('IFNULL(d_penerima.nama, bk.penerima) as penerima'),
                            'bk.catatan as keterangan'
                        )
                        ->where('bk.idAnak', $anak->id);

                    if ($opsi_cetak === 'filter' && $tgl_mulai && $tgl_selesai) {
                        $queryBK->whereBetween('bk.tglKeluar', [$tgl_mulai, $tgl_selesai]);
                    }

                    $barangKeluar = $queryBK->orderBy('bk.tglKeluar', 'asc')->get();

                    // Subtotal
                    $totalMasuk  = $barangMasuk->sum('jumlah');
                    $totalKeluar = $barangKeluar->sum('jumlah');
                    $stokAkhir   = $totalMasuk - $totalKeluar;

                    $itemsData[] = (object) [
                        'idAnak'       => $anak->id,
                        'merkBarang'   => $anak->merkBarang,
                        'spesifikasi'  => $anak->spesifikasi,
                        'namaSatuan'   => $anak->namaSatuan,
                        'barangMasuk'  => $barangMasuk,
                        'totalMasuk'   => $totalMasuk,
                        'barangKeluar' => $barangKeluar,
                        'totalKeluar'  => $totalKeluar,
                        'stokAkhir'    => $stokAkhir,
                    ];
                }

                if (count($itemsData) > 0) {
                    $bukuBesar[] = (object) [
                        'master' => $master,
                        'items'  => $itemsData
                    ];
                }
            }

            // --- DATA PENANDATANGAN ---
            $penandatangan = DB::table('tbpengelolajurusan')
                ->where('id', $request->input('pengelola_kanan_id'))
                ->first();

            if (!$penandatangan) {
                $penandatangan = (object) [
                    'nama_pengelola' => '....................',
                    'nip'            => '....................',
                    'jabatan'        => 'Pengelola Jurusan'
                ];
            }

            $ttd_kiri = null;
            if ($mode_ttd === 'dual' && $request->filled('pengelola_kiri_id')) {
                $pengelolaKiri = DB::table('tbpengelolajurusan')
                    ->where('id', $request->input('pengelola_kiri_id'))
                    ->first();

                if ($pengelolaKiri) {
                    $ttd_kiri = [
                        'jabatan' => $pengelolaKiri->jabatan,
                        'nama'    => $pengelolaKiri->nama_pengelola ?? $pengelolaKiri->nama,
                        'nip'     => $pengelolaKiri->nip,
                        'qr_code' => $pengelolaKiri->qr_code ?? null
                    ];
                }
            }

            // --- RENDER DOMPDF ---
            $pdf = Pdf::loadView('pdf.rekapstokopnameseluruh', compact(
                'bukuBesar',
                'penandatangan',
                'ttd_kiri',
                'tgl_mulai',
                'tgl_selesai',
                'jenis_ttd'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('Rekap_Stok_Opname_' . date('Ymd_His') . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'Error 500',
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ], 500);
        }
    }
}