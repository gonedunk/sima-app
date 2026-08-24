<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    /**
     * 1. TAMPILKAN FORM PENGATURAN GLOBAL
     */
    public function index()
    {
        // Ambil seluruh opsi daftar tahun akademik dari tbtahunAkademik
        $daftarTA = DB::table('tbtahunakademik')
            ->orderBy('tahunAkademik', 'desc')
            ->orderBy('semesterAkademik', 'desc')
            ->get();

        // Ambil baris pengaturan pertama (ID 1)
        $settingAktif = DB::table('tbsetting')->where('id', 1)->first();

        return view('superadmin.setting', compact('daftarTA', 'settingAktif'));
    }

    /**
     * 2. PROSES UPDATE ATAU INSERT (UPSERT) BERDASARKAN ID = 1
     */
    public function update(Request $request)
    {
        $request->validate([
            'ta_dipilih' => 'required'
        ]);

        // Memecah value gabungan yang dikirim dari form (Contoh: "2025/2026|Ganjil")
        $pecahData = explode('|', $request->ta_dipilih);
        $tahunAkademik    = $pecahData[0];
        $semesterAkademik = $pecahData[1];

        // Menggunakan updateOrInsert agar ID tetap terjaga di angka 1 dan tidak menumpuk baris baru
        DB::table('tbsetting')->updateOrInsert(
            ['id' => 1], // Kondisi pencarian (kunci utama pengaturan global)
            [
                'ta_aktif'       => $tahunAkademik,
                'semester_aktif' => $semesterAkademik
            ]
        );

        return redirect()->route('setting.index')->with('success', 'Tahun Akademik global berhasil diperbarui!');
    }
}