<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TranskripPublikController extends Controller
{
    public function index()
    {
        // 1. Ambil data setting aktif untuk mendapatkan tahun akademik
        $setting = DB::table('tbsetting')->first();

        // 2. Ambil daftar mahasiswa sesuai tahunAkademik aktif
        $mahasiswa = DB::table('tbkelasmahasiswa')
            ->select('npm', 'nama', 'prodi')
            ->distinct()
            ->where('tahunAkademik', $setting->ta_aktif ?? null) // Filter Tahun Akademik Aktif
            ->where('statusKeterangan', 'Lulus')
            ->where(function($query) {
                // D3 (3050) -> Sem 6
                $query->where(function($q) {
                    $q->where('prodi', '3050')
                      ->where('semester', 6);
                });
                
                // D4 (4051) -> Sem 8
                $query->orWhere(function($q) {
                    $q->where('prodi', '4051')
                      ->where('semester', 8);
                });
            })
            ->orderBy('nama', 'asc')
            ->get();

        return view('jurusan.transkrip.index', compact('mahasiswa', 'setting'));
    }

    /**
     * Endpoint AJAX untuk cek status upload per NPM
     */
    public function cekStatus(Request $request)
    {
        $transkrip = DB::table('tb_transkrip')
            ->where('npm', $request->npm)
            ->first();

        if ($transkrip) {
            return response()->json([
                'sudah_upload' => true,
                'nama_file'    => $transkrip->nama_file_asli,
                'tanggal'      => date('d-m-Y H:i', strtotime($transkrip->updated_at ?? $transkrip->created_at))
            ]);
        }

        return response()->json([
            'sudah_upload' => false
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'npm' => 'required',
            'file_transkrip' => 'required|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $file = $request->file('file_transkrip');
        $filename = time() . '_' . $request->npm . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('transkrip', $filename, 'public');

        DB::table('tb_transkrip')->updateOrInsert(
            ['npm' => $request->npm],
            [
                'path_file'      => $path,
                'nama_file_asli' => $file->getClientOriginalName(),
                'diunggah_oleh'  => 'mahasiswa',
                'created_at'     => now(),
                'updated_at'     => now()
            ]
        );

        return back()->with('success', 'Transkrip nilai berhasil diunggah!');
    }
}