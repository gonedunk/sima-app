<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class CetakLemburController extends Controller
{
    public function cetakBulanan(Request $request)
    {
        // 1. Menangkap parameter tanggal dan jenis jam
        $tanggal_awal  = $request->query('tanggal_awal', date('Y-m-01'));
        $tanggal_akhir = $request->query('tanggal_akhir', date('Y-m-t'));
        $jenis_jam     = $request->query('jenis_jam', 'normal'); 
        $isKjp2        = ($jenis_jam === 'kjp2');
        
        // 2. Menangkap NIP yang dicentang
        $nip_pilihan   = $request->query('nip_kjp2') 
                        ?? $request->query('nip_pilihan') 
                        ?? $request->query('nip') 
                        ?? [];

        if (is_string($nip_pilihan)) {
            $nip_pilihan = array_filter(explode(',', $nip_pilihan));
        }

        // =========================================================================
        // ID INTEGER BATCH IDTBREKAP SAMA DENGAN CONTROLLER GENERATE HISTORY
        // Jam Normal => 2026051 (Angka Murni)
        // Jam KJP2   => 2026052 (Angka Murni)
        // =========================================================================
        $baseBatchId   = (int) date('Ym', strtotime($tanggal_awal));
        $targetBatchId = $isKjp2 ? (int)($baseBatchId . '2') : (int)($baseBatchId . '1');

        // 3. Query Data Rekap Lembur History (DIISOLASI OLEH IDTBREKAP)
        $queryHistory = DB::table('tbrekaplemburhistory')
            ->select(
                'nip',
                'namaPegawai',
                'dariTanggal',
                'sampaiTanggal',
                'jumlahTotalHariLembur as total_hari',
                'jumlahTotalJamLembur as total_jam',
                'keterangan'
            )
            ->where('idtbrekap', $targetBatchId) // MANDATORI: Memisahkan Jam Normal & KJP2
            ->where('dariTanggal', $tanggal_awal)
            ->where('sampaiTanggal', $tanggal_akhir);

        // FILTER NIP:
        // Jika mode KJP2 dan ada centangan NIP, isolasi hanya NIP yang dicentang.
        if (!empty($nip_pilihan)) {
            $queryHistory->whereIn('nip', $nip_pilihan);
        }

        $dataCetakHistory = $queryHistory->orderBy('id', 'asc')->get();

        // 4. Query Data Pengelola Jurusan sebagai Penanda Tangan
        $pengelolaJurusan = DB::table('tbpengelolajurusan')
            ->leftJoin('tbdosen', 'tbpengelolajurusan.nip', '=', 'tbdosen.nip')
            ->select(
                'tbpengelolajurusan.nip',
                'tbpengelolajurusan.jabatan',
                'tbdosen.nama as nama_pengelola'
            )
            ->where(function ($query) use ($tanggal_awal, $tanggal_akhir) {
                $query->whereNull('tbpengelolajurusan.tanggalSelesai')
                      ->orWhere('tbpengelolajurusan.tanggalSelesai', '>=', $tanggal_awal);
            })
            ->where('tbpengelolajurusan.tanggalMulai', '<=', $tanggal_akhir)
            ->orderBy('tbpengelolajurusan.id', 'asc')
            ->get();

        $ketuaJurusan = $pengelolaJurusan->firstWhere('jabatan', 'Ketua Jurusan') 
            ?? $pengelolaJurusan->first();

        // 5. Render ke PDF View
        $pdf = Pdf::loadView('pdf.RekapLemburBulanan', [
            'dataCetakHistory' => $dataCetakHistory,
            'tanggal_awal'     => $tanggal_awal,
            'tanggal_akhir'    => $tanggal_akhir,
            'jenis_jam'        => $jenis_jam,
            'nip_pilihan'      => $nip_pilihan,
            'pengelolaJurusan' => $pengelolaJurusan,
            'ketuaJurusan'     => $ketuaJurusan
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Rekap_Lembur_' . ($isKjp2 ? 'KJP2_' : 'Normal_') . $tanggal_awal . '_sd_' . $tanggal_akhir . '.pdf');
    }
}