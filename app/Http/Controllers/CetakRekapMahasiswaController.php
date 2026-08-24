<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

class CetakRekapMahasiswaController extends Controller
{
    public function __invoke(Request $request)
    {
        // 1. Ambil data user login
        $user = Auth::user();
        $namaUser = $user->name ?? $user->username ?? 'Administrator';

        // 2. Ambil Setting Tahun Akademik Aktif
        $setting = DB::table('tbsetting')->first();
        $tahunAkademikAktif = $setting->ta_aktif ?? '2025/2026';
        $semesterAktif      = $setting->semester_aktif ?? '';

        // 3. Query Rekapitulasi Data Mahasiswa
        $rekapData = DB::table('tbkelasmahasiswa')
            ->select(
                'kelas',
                'prodi',
                'jurusan',
                DB::raw('COUNT(id) as total_mahasiswa'),
                DB::raw('SUM(CASE WHEN LOWER(statusKeterangan) = "aktif" THEN 1 ELSE 0 END) as akhir_semester'),
                DB::raw('SUM(CASE WHEN LOWER(statusKeterangan) IN ("tidak aktif", "non-aktif", "cuti", "drop out", "keluar") THEN 1 ELSE 0 END) as tidak_aktif')
            )
            ->where('tahunAkademik', $tahunAkademikAktif)
            ->groupBy('kelas', 'prodi', 'jurusan')
            ->orderBy('kelas', 'asc')
            ->get();

        // 4. Render HTML dari File Blade View
        $html = view('pdf.rekap-mahasiswa', [
            'rekapData'          => $rekapData,
            'tahunAkademikAktif' => $tahunAkademikAktif,
            'semesterAktif'      => $semesterAktif,
            'namaUser'           => $namaUser,
        ])->render();

        // 5. Generate PDF menggunakan mPDF
        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->WriteHTML($html);

        $fileName = 'Rekap_Mahasiswa_' . str_replace('/', '-', $tahunAkademikAktif) . '.pdf';

        return response($mpdf->Output($fileName, 'I'))
            ->header('Content-Type', 'application/pdf');
    }
}