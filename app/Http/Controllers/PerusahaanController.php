<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerusahaanController extends Controller
{
    /**
     * Tampilkan data induk organisasi di view superadmin/perusahaan
     */
public function index(Request $request)
{
    $search = $request->input('search');

    // Query builder menggunakan DB Facade
    $query = DB::table('induk_organisasi');

    if (!empty($search)) {
        $query->where(function($q) use ($search) {
            $q->where('nama_induk', 'like', '%' . $search . '%')
              ->orWhere('kategori', 'like', '%' . $search . '%');
        });
    }

    // Ambil data dengan pagination (misal: 10 data per halaman)
    $induk_organisasi = $query->orderBy('nama_induk', 'asc')
                              ->paginate(10)
                              ->withQueryString(); // Menjaga parameter 'search' saat pindah halaman

    return view('superadmin.perusahaan.index', compact('induk_organisasi', 'search'));
}

    /**
     * Simpan data baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_induk' => 'required|string|max:150',
            'kategori'   => 'required|string|max:100',
        ]);

        DB::table('induk_organisasi')->insert([
            'nama_induk' => $request->nama_induk,
            'kategori'   => $request->kategori,
        ]);

        return redirect()->route('superadmin.perusahaan.index')->with('success', 'Data induk organisasi berhasil ditambahkan!');
    }

    /**
     * Update data
     */
    public function update(Request $request, $id_induk)
    {
        $request->validate([
            'nama_induk' => 'required|string|max:150',
            'kategori'   => 'required|string|max:100',
        ]);

        DB::table('induk_organisasi')
            ->where('id_induk', $id_induk)
            ->update([
                'nama_induk' => $request->nama_induk,
                'kategori'   => $request->kategori
            ]);

        return redirect()->route('superadmin.perusahaan.index')->with('success', 'Data induk organisasi berhasil diperbarui!');
    }

    /**
     * Hapus data
     */
    public function destroy($id_induk)
    {
        DB::table('induk_organisasi')
            ->where('id_induk', $id_induk)
            ->delete();

        return redirect()->route('superadmin.perusahaan.index')->with('success', 'Data induk organisasi berhasil dihapus!');
    }
}