<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Mpdf\Mpdf;
use Carbon\Carbon;

class CetakUniversalController extends Controller
{
    /**
     * 1. Menampilkan Halaman Form Cetak Universal
     */
    public function index()
    {
        // Data Pengelola Jurusan Akuntansi (TTD Kanan)
        $pengelolaList = DB::table('tbpengelolajurusan as p')
            ->leftJoin('tbdosen as d', 'p.nip', '=', 'd.nip')
            ->select('p.id', 'p.nip', 'p.jabatan', DB::raw('IFNULL(d.nama, p.nip) as nama'))
            ->orderBy('p.jabatan', 'asc')
            ->get();

        // Data Pimpinan Polsri (TTD Kiri)
        $pimpinanList = DB::table('tbpimpinanpolsri')
            ->select('id', 'nip', 'nama', 'jabatan')
            ->orderBy('id', 'asc')
            ->get();

        return view('laporan.formcetakuniversal', compact('pengelolaList', 'pimpinanList'));
    }

    /**
     * Helper untuk Menyiapkan Payload Data TTD & Parameter Universal
     */
    private function preparePayload(array $reportData, Request $request)
    {
        $modeTtd   = $request->input('mode_ttd', 'single');
        $ttdKanan  = null;
        $ttdKiri   = null;
        $isAnKanan = false;
        $isAnKiri  = false;

        $qrKanan   = $request->input('qr_kanan', null);
        $qrKiri    = $request->input('qr_kiri', null);

        if ($modeTtd !== 'none') {
            // TTD KANAN (Pengelola Jurusan)
            if ($request->filled('pengelola_kanan_id')) {
                $ttdKanan = DB::table('tbpengelolajurusan as p')
                    ->leftJoin('tbdosen as d', 'p.nip', '=', 'd.nip')
                    ->select('p.jabatan', 'p.nip', DB::raw('IFNULL(d.nama, p.nip) as nama'))
                    ->where('p.id', $request->input('pengelola_kanan_id'))
                    ->first();

                if ($ttdKanan && !empty($ttdKanan->jabatan)) {
                    $jKanan = strtolower(trim($ttdKanan->jabatan));
                    if (!str_contains($jKanan, 'ketua jurusan') && !str_contains($jKanan, 'kajur')) {
                        $isAnKanan = true;
                    }
                }
            }

            // TTD KIRI (Pimpinan Polsri)
            if ($modeTtd === 'dual' && $request->filled('pengelola_kiri_id')) {
                $ttdKiri = DB::table('tbpimpinanpolsri')
                    ->select('id', 'nip', 'nama', 'jabatan')
                    ->where('id', $request->input('pengelola_kiri_id'))
                    ->first();

                if ($ttdKiri && !empty($ttdKiri->jabatan)) {
                    if (strtolower(trim($ttdKiri->jabatan)) !== 'direktur') {
                        $isAnKiri = true;
                    }
                }
            }
        }

        return array_merge($reportData, [
            'jenis_laporan' => $request->input('jenis_laporan'),
            'mode_ttd'      => $modeTtd,
            'jenis_ttd'     => $request->input('jenis_ttd', 'manual'),
            'ttdKanan'      => $ttdKanan,
            'ttdKiri'       => $ttdKiri,
            'isAnKanan'     => $isAnKanan,
            'isAnKiri'      => $isAnKiri,
            'qrKanan'       => $qrKanan,
            'qrKiri'        => $qrKiri,
            'tgl_cetak'     => Carbon::now()->locale('id')->isoFormat('D MMMM Y'),
            'tgl_mulai'     => $request->input('tgl_mulai'),
            'tgl_selesai'   => $request->input('tgl_selesai'),
        ]);
    }

    /**
     * 2. Render Menggunakan DomPDF (Cocok untuk data standar / Stok Opname)
     */
    private function renderDomPdf(string $viewPath, array $reportData, Request $request, string $orientation = 'portrait', string $paperSize = 'a4')
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $payload = $this->preparePayload($reportData, $request);

        $pdf = DomPdf::loadView($viewPath, $payload)
            ->setPaper($paperSize, $orientation)
            ->setOption([
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => false,
            ]);

        return $pdf->stream('Laporan_DomPDF_' . date('Ymd_His') . '.pdf');
    }

    /**
     * 3. Render Menggunakan mPDF Native (Universal untuk semua view)
     */
    private function renderMpdf(string $viewPath, array $reportData, Request $request, string $orientation = 'P', string $paperSize = 'A4')
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);
        ini_set('pcre.backtrack_limit', '50000000');
        ini_set('pcre.recursion_limit', '50000000');

        $payload = $this->preparePayload($reportData, $request);

        // 1. Render Blade view
        $html = view($viewPath, $payload)->render();

        // 2. Pastikan folder temporary mPDF tersedia
        $tempDir = storage_path('app/temp-mpdf');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // 3. Inisialisasi Mpdf
        $mpdf = new \Mpdf\Mpdf([
            'mode'                 => 'utf-8',
            'format'               => $paperSize,
            'orientation'          => $orientation,
            'margin_left'          => 15,
            'margin_right'         => 15,
            'margin_top'           => 12,
            'margin_bottom'        => 12,
            'tempDir'              => storage_path('app/mpdf'),
            'allow_local_file_access' => true,
            'base_script_url'         => public_path(),

            // KUNCI PERBAIKAN PHP 8.5
            'packTableData'        => false,
            'simpleTables'         => true,
            'use_kpp'              => false,
        ]);

        // 4. Masukkan HTML ke mPDF
        $mpdf->WriteHTML($html);

        // 5. Output PDF ke Browser Stream
        $filename = 'Laporan_' . ucfirst($request->input('jenis_laporan', 'universal')) . '_' . date('Ymd_His') . '.pdf';

        return response($mpdf->Output($filename, 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * 4. Eksekusi Cetak Laporan (Routing Universal)
     */
    public function prosesCetak(Request $request)
    {
        // Tangkap input dasar dari request
        $jenisLaporan = $request->input('jenis_laporan');

        // =========================================================
        // OPSI 1: LAPORAN STOK OPNAME / DATA BARANG (DomPDF)
        // =========================================================
        if ($jenisLaporan === 'stok_opname') {
            $opsi_cetak  = $request->input('opsi_cetak', 'semua');
            $tgl_mulai   = ($opsi_cetak === 'filter') ? $request->input('tgl_mulai') : null;
            $tgl_selesai = ($opsi_cetak === 'filter') ? $request->input('tgl_selesai') : null;

            $masterBarangList = DB::table('tbmasterbarang')
                ->select('id', 'namaBarang')
                ->orderBy('namaBarang', 'asc')
                ->get();

            $bukuBesar = [];

            foreach ($masterBarangList as $master) {
                $anakBarangList = DB::table('tbanakbarang as ab')
                    ->leftJoin('tbsatuan as st', 'ab.idsatuan', '=', 'st.id')
                    ->select('ab.id', 'ab.merkBarang', 'ab.spesifikasi', DB::raw('IFNULL(st.jenisBarang, "Pcs") as namaSatuan'))
                    ->where('ab.idMaster', $master->id)
                    ->get();

                $itemsData = [];

                foreach ($anakBarangList as $anak) {
                    // Transaksi Masuk
                    $queryBM = DB::table('tbtransaksibarangmasuk as bm')
                        ->leftJoin('tbdosen as d_penerima', DB::raw('bm.penerima COLLATE utf8mb4_general_ci'), '=', DB::raw('d_penerima.nip COLLATE utf8mb4_general_ci'))
                        ->select('bm.tglMasuk as tanggal', 'bm.jumlah', 'bm.namaSupplier as supplier', DB::raw('IFNULL(d_penerima.nama, bm.penerima) as penerima'), 'bm.keterangan')
                        ->where('bm.idAnak', $anak->id);

                    if ($opsi_cetak === 'filter' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
                        $queryBM->whereBetween('bm.tglMasuk', [$tgl_mulai, $tgl_selesai]);
                    }

                    $barangMasuk = $queryBM->orderBy('bm.tglMasuk', 'asc')->get();

                    // Transaksi Keluar
                    $queryBK = DB::table('tbtransaksibarangkeluar as bk')
                        ->leftJoin('tbdosen as d_petugas', DB::raw('bk.petugas COLLATE utf8mb4_general_ci'), '=', DB::raw('d_petugas.nip COLLATE utf8mb4_general_ci'))
                        ->leftJoin('tbdosen as d_penerima', DB::raw('bk.penerima COLLATE utf8mb4_general_ci'), '=', DB::raw('d_penerima.nip COLLATE utf8mb4_general_ci'))
                        ->select('bk.tglKeluar as tanggal', 'bk.jumlah', DB::raw('IFNULL(d_petugas.nama, bk.petugas) as petugas'), DB::raw('IFNULL(d_penerima.nama, bk.penerima) as penerima'), 'bk.catatan as keterangan')
                        ->where('bk.idAnak', $anak->id);

                    if ($opsi_cetak === 'filter' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
                        $queryBK->whereBetween('bk.tglKeluar', [$tgl_mulai, $tgl_selesai]);
                    }

                    $barangKeluar = $queryBK->orderBy('bk.tglKeluar', 'asc')->get();

                    $totalMasuk  = $barangMasuk->sum('jumlah');
                    $totalKeluar = $barangKeluar->sum('jumlah');

                    $itemsData[] = (object) [
                        'idAnak'       => $anak->id,
                        'merkBarang'   => $anak->merkBarang,
                        'spesifikasi'  => $anak->spesifikasi,
                        'namaSatuan'   => $anak->namaSatuan,
                        'barangMasuk'  => $barangMasuk,
                        'totalMasuk'   => $totalMasuk,
                        'barangKeluar' => $barangKeluar,
                        'totalKeluar'  => $totalKeluar,
                        'stokAkhir'    => $totalMasuk - $totalKeluar,
                    ];
                }

                if (count($itemsData) > 0) {
                    $bukuBesar[] = (object) [
                        'master' => $master,
                        'items'  => $itemsData
                    ];
                }
            }

            return $this->renderDomPdf('pdf.rekapstokopnameseluruh', ['bukuBesar' => $bukuBesar], $request, 'landscape');
        }

        // =========================================================
        // OPSI 3: LAPORAN MAHASISWA YUDISIUM (mPDF Native)
        // =========================================================
        if ($jenisLaporan === 'mahasiswa_yudisium') {
            $taAktif = DB::table('tbsetting')->first();
            $user    = auth()->user();

            $prodiUser = DB::table('tbprodi')
                ->where('kodeProdi', $user->kode_prodi ?? null)
                ->first();

            $kodeJurusanUser = $prodiUser->kodeJurusan ?? null;

            $daftarProdiJurusan = DB::table('tbprodi')
                ->where('kodeJurusan', $kodeJurusanUser)
                ->pluck('namaProdi')
                ->toArray();

            $query = DB::table('tbkelasmahasiswa as km')
                ->leftJoin('tbprodi as p', 'km.prodi', '=', 'p.kodeProdi')
                ->leftJoin('tbjurusan as j', 'km.jurusan', '=', 'j.kodeJurusan')
                ->select(
                    'km.npm',
                    'km.nama',
                    'km.kelas',
                    'km.semester',
                    'km.prodi',
                    'km.keterangan',        // A / NA
                    'km.statusKeterangan',  // Status Penjelas
                    'km.tahunAkademik',
                    'km.tahunMasuk',
                    'p.namaProdi',
                    'j.namaJurusan'
                );

            if ($taAktif && !empty($taAktif->ta_aktif)) {
                $query->where('km.tahunAkademik', $taAktif->ta_aktif);
            }

            if ($kodeJurusanUser) {
                $query->where('km.jurusan', $kodeJurusanUser);
            }

            $dataMahasiswa = $query->orderBy('p.namaProdi', 'asc')
                                   ->orderBy('km.kelas', 'asc')
                                   ->orderBy('km.nama', 'asc')
                                   ->get();

            return $this->renderMpdf(
                'pdf.daftarmahasiswa',
                [
                    'dataMahasiswa'      => $dataMahasiswa,
                    'taAktif'            => $taAktif,
                    'prodiUser'          => $prodiUser,
                    'daftarProdiJurusan' => $daftarProdiJurusan,
                    'userLogin'          => $user,
                ],
                $request,
                'P',
                'A4'
            );
        }

        // =========================================================
        // OPSI 4: LAPORAN REKAP MAHASISWA PER KELAS (mPDF Native)
        // =========================================================
if ($jenisLaporan === 'rekap_mahasiswa') {
    $taAktif = DB::table('tbsetting')->first();
    $user    = auth()->user();

    $taAktifString = $taAktif->ta_aktif ?? null;

    // 1. Deteksi Otomatis Semester Ganjil / Genap
    // Contoh pembacaan: Jika ta_aktif berisi "2023/2024 Ganjil" atau "20231", atau ada field $taAktif->semester
    $semesterSetting = strtolower($taAktif->semester ?? $taAktif->ta_aktif ?? '');
    
    // Cek apakah semester aktif saat ini adalah GANJIL
    $isGanjil = (
        str_contains($semesterSetting, 'ganjil') || 
        str_contains($semesterSetting, '1') || 
        (isset($taAktif->smt) && $taAktif->smt % 2 !== 0)
    );

    // Tentukan array semester dinamis berdasar status ganjil/genap
    $semesterD3 = $isGanjil ? [1, 3, 5]    : [2, 4, 6];
    $semesterD4 = $isGanjil ? [1, 3, 5, 7] : [2, 4, 6, 8];

    // Kode Jurusan Standar
    $kodeJurusanD3 = '62401';
    $kodeJurusanD4 = '62301';

    // Cari kodeJurusan user berdasarkan relasi users.kode_prodi -> tbprodi.kodeProdi
    $userKodeJurusan = null;
    if (!empty($user->kode_prodi)) {
        $prodiUser = DB::table('tbprodi')
            ->where('kodeProdi', $user->kode_prodi)
            ->first();
            
        $userKodeJurusan = $prodiUser->kodeJurusan ?? null;
    }

    $isUserD3 = ($userKodeJurusan == $kodeJurusanD3);
    $isUserD4 = ($userKodeJurusan == $kodeJurusanD4);

    // 2. Query Rekap Data Mahasiswa Per Kelas & Semester
    $allData = DB::table('tbkelasmahasiswa as km')
        ->leftJoin('tbprodi as p', 'km.prodi', '=', 'p.kodeProdi')
        ->leftJoin('tbjurusan as j', 'p.kodeJurusan', '=', 'j.kodeJurusan')
        ->select(
            'km.kelas',
            'km.semester',
            'p.namaProdi as prodi',
            'p.kodeJurusan',
            'j.namaJurusan',
            
            DB::raw('COUNT(km.id) as awal_semester'),
            DB::raw('SUM(CASE WHEN LOWER(km.keterangan) IN ("a", "aktif") THEN 1 ELSE 0 END) as akhir_semester'),
            DB::raw('SUM(CASE WHEN LOWER(km.keterangan) NOT IN ("a", "aktif") AND km.keterangan IS NOT NULL THEN 1 ELSE 0 END) as tidak_aktif'),
            DB::raw('(
                SUM(CASE WHEN LOWER(km.keterangan) IN ("a", "aktif") THEN 1 ELSE 0 END) - 
                SUM(CASE WHEN LOWER(km.keterangan) NOT IN ("a", "aktif") AND km.keterangan IS NOT NULL THEN 1 ELSE 0 END)
            ) as total_mahasiswa')
        )
        ->when($taAktifString, function ($q) use ($taAktifString) {
            return $q->where('km.tahunAkademik', $taAktifString);
        })
        ->when($userKodeJurusan, function ($q) use ($userKodeJurusan) {
            return $q->where('p.kodeJurusan', $userKodeJurusan);
        })
        ->groupBy('km.kelas', 'km.semester', 'p.namaProdi', 'p.kodeJurusan', 'j.namaJurusan')
        ->orderBy('km.kelas', 'asc')
        ->get();

    // Filter Data D3 (Dinamis mengikuti semesterD3)
    $dataD3 = collect();
    if (!$isUserD4) { 
        $dataD3 = $allData->filter(function ($item) use ($kodeJurusanD3, $semesterD3) {
            $isD3 = ($item->kodeJurusan == $kodeJurusanD3) || preg_match('/d3|d-3|diploma 3|diploma iii/i', $item->prodi ?? $item->kelas);
            $semesterValid = in_array((int)($item->semester ?? 0), $semesterD3);
            return $isD3 && $semesterValid;
        })->values();
    }

    // Filter Data D4 (Dinamis mengikuti semesterD4)
    $dataD4 = collect();
    if (!$isUserD3) { 
        $dataD4 = $allData->filter(function ($item) use ($kodeJurusanD4, $semesterD4) {
            $isD4 = ($item->kodeJurusan == $kodeJurusanD4) || preg_match('/d4|d-4|diploma 4|diploma iv|sarjana terapan/i', $item->prodi ?? $item->kelas);
            $semesterValid = in_array((int)($item->semester ?? 0), $semesterD4);
            return $isD4 && $semesterValid;
        })->values();
    }

    // 3. Query Detail Mahasiswa Non-Aktif D3 (Semester Dinamis)
    $mhsNonAktifD3 = collect();
    if (!$isUserD4) {
        $mhsNonAktifD3 = DB::table('tbkelasmahasiswa as km')
            ->leftJoin('tbprodi as p', 'km.prodi', '=', 'p.kodeProdi')
            ->select('km.npm', 'km.nama', 'km.statusKeterangan', 'km.keterangan', 'p.namaProdi as prodi')
            ->when($taAktifString, function ($q) use ($taAktifString) {
                return $q->where('km.tahunAkademik', $taAktifString);
            })
            ->where('p.kodeJurusan', $kodeJurusanD3)
            ->whereIn('km.semester', $semesterD3)
            ->whereNotIn(DB::raw('LOWER(km.keterangan)'), ['a', 'aktif'])
            ->get();
    }

    // 4. Query Detail Mahasiswa Non-Aktif D4 (Semester Dinamis)
    $mhsNonAktifD4 = collect();
    if (!$isUserD3) {
        $mhsNonAktifD4 = DB::table('tbkelasmahasiswa as km')
            ->leftJoin('tbprodi as p', 'km.prodi', '=', 'p.kodeProdi')
            ->select('km.npm', 'km.nama', 'km.statusKeterangan', 'km.keterangan', 'p.namaProdi as prodi')
            ->when($taAktifString, function ($q) use ($taAktifString) {
                return $q->where('km.tahunAkademik', $taAktifString);
            })
            ->where('p.kodeJurusan', $kodeJurusanD4)
            ->whereIn('km.semester', $semesterD4)
            ->whereNotIn(DB::raw('LOWER(km.keterangan)'), ['a', 'aktif'])
            ->get();
    }

    return $this->renderMpdf(
        'pdf.rekapmahasiswaaktif',
        [
            'dataD3'         => $dataD3,
            'dataD4'         => $dataD4,
            'mhsNonAktifD3' => $mhsNonAktifD3,
            'mhsNonAktifD4' => $mhsNonAktifD4,
            'taAktif'        => $taAktif,
            'userLogin'      => $user,
        ],
        $request,
        'P',
        'A4'
    );
}

        return back()->with('error', 'Jenis laporan tidak valid.');
    }
}