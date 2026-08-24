<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahunAkademikController extends Controller
{
    // TAMPILKAN HALAMAN UTAMA (READ)
// TAMPILKAN HALAMAN UTAMA (READ)
public function index()
{
    $tahunAkademiks = DB::table('tbtahunakademik')
        ->orderBy('tahunAkademik', 'desc')
        ->orderBy('semesterAkademik', 'desc')
        ->get();

    // PERBAIKAN: Arahkan ke folder superadmin/tahunakademik/index.blade.php
    return view('superadmin.tahunakademik.index', compact('tahunAkademiks'));
}

    // PROSES SIMPAN DATA (STORE)
    public function store(Request $request)
    {
        $request->validate([
            'tahunAkademik'    => 'required|numeric|digits:5|unique:tbtahunakademik,tahunAkademik',
            'semesterAkademik' => 'required|string|max:100',
        ], [
            'tahunAkademik.unique' => 'Kode Tahun Akademik sudah terdaftar.',
            'tahunAkademik.digits' => 'Tahun Akademik harus berformat 5 digit angka.'
        ]);

        DB::table('tbtahunakademik')->insert([
            'tahunAkademik'    => $request->tahunAkademik,
            'semesterAkademik' => $request->semesterAkademik,
        ]);

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil ditambahkan.');
    }

    // PROSES UPDATE DATA (UPDATE)
    public function update(Request $request, $id)
    {
        $request->validate([
            'tahunAkademik'    => 'required|numeric|digits:5|unique:tbtahunakademik,tahunAkademik,' . $id,
            'semesterAkademik' => 'required|string|max:100',
        ], [
            'tahunAkademik.unique' => 'Kode Tahun Akademik sudah digunakan oleh data lain.',
            'tahunAkademik.digits' => 'Tahun Akademik harus berformat 5 digit angka.'
        ]);

        DB::table('tbtahunakademik')->where('id', $id)->update([
            'tahunAkademik'    => $request->tahunAkademik,
            'semesterAkademik' => $request->semesterAkademik,
        ]);

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil diperbarui.');
    }

    // PROSES HAPUS DATA (DELETE)
    public function destroy($id)
    {
        DB::table('tbtahunakademik')->where('id', $id)->delete();

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil dihapus.');
    }
}