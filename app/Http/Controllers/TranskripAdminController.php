<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TranskripAdminController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil data setting aktif untuk mendapatkan tahun akademik
        $setting = DB::table('tbsetting')->first();

        // 2. Buat query utama dengan filter tahunAkademik
        $query = DB::table('tbkelasmahasiswa as km')
            ->leftJoin('tb_transkrip as t', 'km.npm', '=', 't.npm')
            ->select(
                'km.npm',
                'km.nama',
                'km.prodi',
                'km.semester',
                't.id as id_transkrip',
                't.path_file',
                't.nama_file_asli',
                't.status_verifikasi',
                't.catatan',
                't.updated_at as tgl_upload'
            )
            ->where('km.tahunAkademik', $setting->ta_aktif ?? null) // Filter Tahun Akademik Aktif
            ->where('km.statusKeterangan', 'Lulus')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('km.prodi', '3050')->where('km.semester', 6);
                })->orWhere(function ($sub) {
                    $sub->where('km.prodi', '4051')->where('km.semester', 8);
                });
            });

        // Filter Prodi
        if ($request->filled('prodi')) {
            $query->where('km.prodi', $request->prodi);
        }

        // Filter Status Verifikasi
        if ($request->filled('status')) {
            if ($request->status == 'Belum Diperiksa') {
                $query->where(function ($q) {
                    $q->whereNull('t.status_verifikasi')
                      ->orWhere('t.status_verifikasi', 'Belum Diperiksa');
                });
            } else {
                $query->where('t.status_verifikasi', $request->status);
            }
        }

        // Pencarian NPM atau Nama
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('km.npm', 'like', "%{$search}%")
                  ->orWhere('km.nama', 'like', "%{$search}%");
            });
        }

        $transkrip = $query->orderBy('km.nama', 'asc')->paginate(15)->withQueryString();

        // Mengirim $transkrip dan $setting ke view jurusan.transkrip.admin_check
        return view('jurusan.transkrip.admin_check', compact('transkrip', 'setting'));
    }

    public function verifikasi(Request $request, $npm)
    {
        $request->validate([
            'status_verifikasi' => 'required|in:Valid,Invalid,Belum Diperiksa',
            'catatan'           => 'nullable|string',
        ]);

        DB::table('tb_transkrip')->updateOrInsert(
            ['npm' => $npm],
            [
                'status_verifikasi' => $request->status_verifikasi,
                'catatan'           => $request->catatan,
                'verified_at'       => now(),
                'updated_at'        => now(),
            ]
        );

        return redirect()->back()->with('success', 'Status verifikasi transkrip berhasil diperbarui.');
    }
}