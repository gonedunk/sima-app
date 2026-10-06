<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TandaTanganController extends Controller
{
    /**
     * Halaman Utama Kelola Penanda Tangan & Konfirmasi Cetak
     */
    public function index(Request $request)
    {
        // Parameter tanggal awal & akhir dari halaman rekaplembur
        $tanggal_awal  = $request->query('tanggal_awal', date('Y-m-01'));
        $tanggal_akhir = $request->query('tanggal_akhir', date('Y-m-t'));

        // Ambil daftar penanda tangan dari tbpengelolajurusan
        $pengelolaList = DB::table('tbpengelolajurusan')
            ->leftJoin('tbdosen', 'tbpengelolajurusan.nip', '=', 'tbdosen.nip')
            ->select('tbpengelolajurusan.*', 'tbdosen.nama as nama_pengelola')
            ->orderBy('tbpengelolajurusan.tanggalMulai', 'desc')
            ->get();

        // Ambil daftar dosen/pegawai untuk modal input
        $dosenList = DB::table('tbdosen')
            ->select('nip', 'nama')
            ->orderBy('nama', 'asc')
            ->get();

        return view('admin.tandatangan.index', compact('pengelolaList', 'dosenList', 'tanggal_awal', 'tanggal_akhir'));
    }

    /**
     * Eksekusi Generate PDF ke views/pdf/RekapLemburBulanan.blade.php
     */
    public function cetakPdfLembur(Request $request)
    {
        $request->validate([
            'tanggal_awal'  => 'required|date',
            'tanggal_akhir' => 'required|date',
            'pengelola_id'  => 'required',
        ]);

        $tanggal_awal  = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $jenis_jam     = $request->input('jenis_jam', 'normal');
        $isKjp2        = ($jenis_jam === 'kjp2');

        // MULTI-FALLBACK NIP: Menangkap NIP yang dicentang dari form/URL
        $nipSelected = $request->input('nip_pilihan') 
                        ?? $request->input('nip_kjp2') 
                        ?? $request->input('selected_nip') 
                        ?? $request->input('nip') 
                        ?? [];

        if (is_string($nipSelected)) {
            $nipSelected = array_filter(explode(',', $nipSelected));
        }

        // 1. Ambil Data Penanda Tangan Terpilih
        $ketuaJurusan = DB::table('tbpengelolajurusan')
            ->leftJoin('tbdosen', 'tbpengelolajurusan.nip', '=', 'tbdosen.nip')
            ->select('tbpengelolajurusan.*', 'tbdosen.nama as nama_pengelola')
            ->where('tbpengelolajurusan.id', $request->pengelola_id)
            ->first();

        // =========================================================================
        // BATASI IDTBREKAP SESUAI KATEGORI JAM (NIP SAMA TIDAK AKAN DOUBLE LAGI)
        // Normal => YYYYMM1 (misal: 2026051)
        // KJP2   => YYYYMM2 (misal: 2026052)
        // =========================================================================
        $baseBatchId   = (int) date('Ym', strtotime($tanggal_awal));
        $targetBatchId = $isKjp2 ? (int)($baseBatchId . '2') : (int)($baseBatchId . '1');

        // 2. Query Data dari tbrekaplemburhistory dengan ISOLASI IDTBREKAP
        $query = DB::table('tbrekaplemburhistory')
            ->select(
                'id',
                'idtbrekap',
                'nip',
                'namaPegawai',
                'dariTanggal',
                'sampaiTanggal',
                'jumlahTotalHariLembur as total_hari',
                'jumlahTotalJamLembur as total_jam',
                'jumlahMingguLembur',
                'keterangan'
            )
            ->where('idtbrekap', $targetBatchId) // PERBAIKAN UTAMA: Filter khusus Batch Normal/KJP2
            ->where('dariTanggal', $tanggal_awal)
            ->where('sampaiTanggal', $tanggal_akhir);

        // FILTER NIP: Jika mode KJP2 atau ada centangan NIP, saring khusus NIP tersebut
        if (!empty($nipSelected)) {
            $query->whereIn('nip', $nipSelected);
        }

        $dataCetakHistory = $query->orderBy('id', 'asc')->get();

        // 3. Render PDF
        $pdf = Pdf::loadView('pdf.RekapLemburBulanan', [
            'tanggal_awal'     => $tanggal_awal,
            'tanggal_akhir'    => $tanggal_akhir,
            'jenis_jam'        => $jenis_jam,
            'nip_pilihan'      => $nipSelected,
            'dataCetakHistory' => $dataCetakHistory,
            'ketuaJurusan'     => $ketuaJurusan,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Rekap_Lembur_Bulanan_' . ($isKjp2 ? 'KJP2_' : 'Normal_') . date('Ymd') . '.pdf');
    }

    /**
     * Simpan Data Penanda Tangan Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'nip'          => 'required',
            'jabatan'      => 'required|string|max:100',
            'tanggalMulai' => 'required|date',
        ]);

        DB::table('tbpengelolajurusan')->insert([
            'nip'            => $request->nip,
            'jabatan'        => $request->jabatan,
            'tanggalMulai'   => $request->tanggalMulai,
            'tanggalSelesai' => $request->tanggalSelesai ?? null,
        ]);

        return redirect()->back()->with('success', 'Data penanda tangan berhasil ditambahkan.');
    }

    /**
     * Update Data Penanda Tangan
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nip'          => 'required',
            'jabatan'      => 'required|string|max:100',
            'tanggalMulai' => 'required|date',
        ]);

        DB::table('tbpengelolajurusan')
            ->where('id', $id)
            ->update([
                'nip'            => $request->nip,
                'jabatan'        => $request->jabatan,
                'tanggalMulai'   => $request->tanggalMulai,
                'tanggalSelesai' => $request->tanggalSelesai ?? null,
            ]);

        return redirect()->back()->with('success', 'Data penanda tangan berhasil diperbarui.');
    }

    /**
     * Hapus Data Penanda Tangan
     */
    public function destroy($id)
    {
        DB::table('tbpengelolajurusan')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Data penanda tangan berhasil dihapus.');
    }
}