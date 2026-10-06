<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManajemenKelasController extends Controller
{
    /**
     * Tampilkan data kelas ke halaman superadmin dengan relasi program, prodi, dan jurusan.
     */
    public function index(Request $request)
{
    // Tangkap data kata kunci dari input pencarian
    $cari = $request->input('cari');

    $query = DB::table('tbkelas')
        ->leftJoin('tbprogram', 'tbkelas.kodeProgram', '=', 'tbprogram.kodeProgram')
        ->leftJoin('tbprodi', 'tbkelas.kodeProdi', '=', 'tbprodi.kodeProdi')
        ->leftJoin('tbjurusan', 'tbkelas.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
        ->select(
            'tbkelas.*',
            'tbprogram.namaProgram',
            'tbprodi.namaProdi',
            'tbjurusan.namaJurusan'
        );

    // Jika user mengetik sesuatu di kolom pencarian
    if (!empty($cari)) {
        $query->where(function($q) use ($cari) {
            $q->where('tbkelas.namaKelas', 'like', "%" . $cari . "%")
              ->orWhere('tbprogram.namaProgram', 'like', "%" . $cari . "%")
              ->orWhere('tbprodi.namaProdi', 'like', "%" . $cari . "%")
              ->orWhere('tbjurusan.namaJurusan', 'like', "%" . $cari . "%");
        });
    }

    // Urutkan dan batasi 15 data per halaman, sertakan query string pencarian di link pagination
    $all_kelas = $query->orderBy('tbkelas.namaKelas', 'asc')->paginate(15)->withQueryString();

    $all_program = DB::table('tbprogram')->orderBy('namaProgram', 'asc')->get();
    $all_prodi   = DB::table('tbprodi')->orderBy('namaProdi', 'asc')->get();
    $all_jurusan = DB::table('tbjurusan')->orderBy('namaJurusan', 'asc')->get();
    
    return view('superadmin.manajemenkelas.index', compact(
        'all_kelas', 
        'all_program', 
        'all_prodi', 
        'all_jurusan',
        'cari' // Kirim kembali kata kunci ke view agar teks tidak hilang setelah reload
    ));
}

    /**
     * Menyimpan data kelas baru ke tbkelas.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'namaKelas'   => 'required|string|max:50',
            'kodeProgram' => 'required|string|max:20',
            'kodeProdi'   => 'nullable|string|max:20',
            'kodeJurusan' => 'nullable|string|max:20',
        ]);

        DB::table('tbkelas')->insert($validated);

        return redirect()->route('kelas.index')->with('success', 'Data kelas baru berhasil disimpan!');
    }


    /**
     * Menghapus data kelas dari tbkelas.
     */
    public function destroy(string $id)
    {
        DB::table('tbkelas')->where('id', $id)->delete();

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil dihapus!');
    }
}