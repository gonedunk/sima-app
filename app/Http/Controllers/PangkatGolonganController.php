<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PangkatGolonganController extends Controller
{
    /**
     * Menampilkan semua data pangkat golongan
     */
    public function index()
    {
        // Mengambil seluruh data dari tabel tbpangkatgolongan
        $pangkatGolongan = DB::table('tbpangkatgolongan')
            ->orderBy('golonganRuang', 'asc')
            ->get();
    return view('superadmin.pangkatgolongan.index', compact('pangkatGolongan')); 
    }

    /**
     * Menyimpan data pangkat golongan baru (Insert)
     */
    public function store(Request $request)
    {
        $request->validate([
            'pangkat'          => 'required|string|max:100',
            'golonganRuang'    => 'required|string|max:50|unique:tbpangkatgolongan,golonganRuang',
            'jabatanAkademik'  => 'nullable|string|max:100',
            'kelasJabatan'     => 'nullable|integer',
            'akm'              => 'nullable|numeric',
        ], [
            'pangkat.required'       => 'Nama pangkat wajib diisi.',
            'golonganRuang.required' => 'Golongan Ruang wajib diisi.',
            'golonganRuang.unique'   => 'Golongan Ruang tersebut sudah terdaftar di sistem.',
        ]);

        try {
            DB::table('tbpangkatgolongan')->insert([
                'pangkat'         => $request->pangkat,
                'golonganRuang'   => $request->golonganRuang,
                'jabatanAkademik' => $request->jabatanAkademik,
                'kelasJabatan'    => $request->kelasJabatan,
                'akm'             => $request->akm,
            ]);

            return redirect()->back()->with('success', 'Data pangkat golongan baru berhasil ditambahkan!');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah data: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui data pangkat golongan yang dipilih (Update)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'pangkat'          => 'required|string|max:100',
            'golonganRuang'    => 'required|string|max:50|unique:tbpangkatgolongan,golonganRuang,' . $id,
            'jabatanAkademik'  => 'nullable|string|max:100',
            'kelasJabatan'     => 'nullable|integer',
            'akm'              => 'nullable|numeric',
        ], [
            'pangkat.required'       => 'Nama pangkat wajib diisi.',
            'golonganRuang.required' => 'Golongan Ruang wajib diisi.',
            'golonganRuang.unique'   => 'Golongan Ruang tersebut sudah terdaftar di sistem.',
        ]);

        try {
            DB::table('tbpangkatgolongan')
                ->where('id', $id)
                ->update([
                    'pangkat'         => $request->pangkat,
                    'golonganRuang'   => $request->golonganRuang,
                    'jabatanAkademik' => $request->jabatanAkademik,
                    'kelasJabatan'    => $request->kelasJabatan,
                    'akm'             => $request->akm,
                ]);

            return redirect()->back()->with('success', 'Data pangkat golongan berhasil diperbarui!');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus data pangkat golongan secara permanen (Delete)
     */
    public function destroy($id)
    {
        try {
            $deleted = DB::table('tbpangkatgolongan')
                ->where('id', $id)
                ->delete();

            if (!$deleted) {
                return redirect()->back()->with('error', 'Data gagal dihapus atau sudah tidak ada.');
            }

            return redirect()->back()->with('success', 'Data pangkat golongan berhasil dihapus!');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}