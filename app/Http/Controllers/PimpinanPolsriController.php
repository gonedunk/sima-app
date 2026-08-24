<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PimpinanPolsriController extends Controller
{
    // TAMPILKAN DATA (READ)
    public function index()
    {
        $pimpinan = DB::table('tbpimpinanpolsri')
            ->orderBy('tanggalMulai', 'desc')
            ->get();

        return view('superadmin.pimpinan.index', compact('pimpinan'));
    }

    // SIMPAN DATA BARU (STORE)
    public function store(Request $request)
    {
        $request->validate([
            'nip'            => 'required|string|max:50|unique:tbpimpinanpolsri,nip',
            'nama'           => 'required|string|max:150',
            'jabatan'        => 'required|string|max:100',
            'tanggalMulai'   => 'required|date',
            'tanggalSelesai' => 'nullable|date|after_or_equal:tanggalMulai',
        ], [
            'nip.unique'                  => 'NIP sudah terdaftar.',
            'tanggalSelesai.after_or_equal' => 'Tanggal selesai tidak boleh mendahului tanggal mulai.'
        ]);

        DB::table('tbpimpinanpolsri')->insert([
            'nip'            => $request->nip,
            'nama'           => $request->nama,
            'jabatan'        => $request->jabatan,
            'tanggalMulai'   => $request->tanggalMulai,
            'tanggalSelesai' => $request->tanggalSelesai,
        ]);

        return redirect()->route('pimpinan.index')
            ->with('success', 'Data pimpinan baru berhasil ditambahkan.');
    }

    // UPDATE DATA (UPDATE)
    public function update(Request $request, $id)
    {
        $request->validate([
            'nip'            => 'required|string|max:50|unique:tbpimpinanpolsri,nip,' . $id,
            'nama'           => 'required|string|max:150',
            'jabatan'        => 'required|string|max:100',
            'tanggalMulai'   => 'required|date',
            'tanggalSelesai' => 'nullable|date|after_or_equal:tanggalMulai',
        ], [
            'nip.unique'                  => 'NIP sudah digunakan oleh data lain.',
            'tanggalSelesai.after_or_equal' => 'Tanggal selesai tidak boleh mendahului tanggal mulai.'
        ]);

        DB::table('tbpimpinanpolsri')->where('id', $id)->update([
            'nip'            => $request->nip,
            'nama'           => $request->nama,
            'jabatan'        => $request->jabatan,
            'tanggalMulai'   => $request->tanggalMulai,
            'tanggalSelesai' => $request->tanggalSelesai,
        ]);

        return redirect()->route('pimpinan.index')
            ->with('success', 'Data pimpinan berhasil diperbarui.');
    }

    // HAPUS DATA (DESTROY)
    public function destroy($id)
    {
        DB::table('tbpimpinanpolsri')->where('id', $id)->delete();

        return redirect()->route('pimpinan.index')
            ->with('success', 'Data pimpinan berhasil dihapus.');
    }
}