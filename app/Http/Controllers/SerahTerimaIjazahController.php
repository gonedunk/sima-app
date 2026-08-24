<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;

class SerahTerimaIjazahController extends Controller
{
    /**
     * Cetak Bukti Serah Terima Ijazah & Transkrip Per Kelas
     */
    public function cetakBukti($id)
    {
        // Ambil jurusan dari user yang sedang login
        $userJurusan = Auth::user()->jurusan ?? null; // Sesuaikan field jika menggunakan session, misal: session('jurusan')

        // 1. Cari data referensi acuan berdasarkan ID & Jurusan User
        $ref = DB::table('tbkelasmahasiswa')
            ->where('id', $id)
            ->when($userJurusan, function ($query) use ($userJurusan) {
                return $query->where('jurusan', $userJurusan);
            })
            ->first();

        if (!$ref) {
            abort(404, 'Data mahasiswa/kelas tidak ditemukan atau Anda tidak memiliki akses ke jurusan ini.');
        }

        // 2. Validasi Semester Akhir (Ambil angka dari nama kelas, misal: '8APB' -> 8, '6AK' -> 6)
        // Atau gunakan $ref->semester jika di database ada kolom 'semester'
        $angkaSemester = (int) filter_var($ref->kelas, FILTER_SANITIZE_NUMBER_INT);

        // Kriteria semester akhir: 6 (D3) atau 8 (D4/S1 Terapan)
        $isSemesterAkhir = in_array($angkaSemester, [6, 8]); 

        if (!$isSemesterAkhir) {
            abort(403, 'Akses ditolak. Cetak dokumen serah terima ijazah hanya diperbolehkan untuk mahasiswa semester akhir.');
        }

        // 3. Ambil seluruh mahasiswa dalam kelas, prodi, jurusan, dan tahun akademik yang sama
        $dataMahasiswa = DB::table('tbkelasmahasiswa')
            ->where('kelas', $ref->kelas)
            ->where('prodi', $ref->prodi)
            ->when($userJurusan, function ($query) use ($userJurusan) {
                return $query->where('jurusan', $userJurusan);
            })
            ->where('tahunAkademik', $ref->tahunAkademik)
            ->orderBy('npm', 'asc')
            ->get();

        // 4. Grouping data berdasarkan 'kelas' sesuai kebutuhan Blade
        $mahasiswaGrouped = $dataMahasiswa->groupBy('kelas');
        $ta = $ref->tahunAkademik;

        // 5. Render file Blade
        $html = view('pdf.cetak_serah_terima', compact('mahasiswaGrouped', 'ta'))->render();

        // 6. Inisialisasi mPDF
        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);

        $mpdf->WriteHTML($html);

        // 7. Stream file PDF ke browser
        return response($mpdf->Output('Serah_Terima_Ijazah_Kelas_' . $ref->kelas . '.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }
}