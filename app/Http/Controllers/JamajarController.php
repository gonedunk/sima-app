<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JamajarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function Jamajarindex()
    {
        if (auth()->user()->level !== 'superadmin') {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        // Gabungkan tbjamajar dengan tbprogram berdasarkan kodeProgram
        $jamajar = DB::table('tbjamajar')
            ->join('tbprogram', 'tbjamajar.kodeProgram', '=', 'tbprogram.kodeProgram')
            ->select('tbjamajar.*', 'tbprogram.namaProgram')
            ->get();

        // Ambil semua data program untuk isi Pilihan (Dropdown) di Form
        $program = DB::table('tbprogram')->get();

        return view('superadmin.jamajar.index', compact('jamajar', 'program'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (auth()->user()->level !== 'superadmin') { abort(403); }

        $request->validate([
            'hari' => 'required',
            'jamNormal' => 'required',
            'jamRamadan' => 'required',
            'kodeProgram' => 'required',
        ]);

        DB::table('tbjamajar')->insert([
            'hari' => $request->hari,
            'jamNormal' => $request->jamNormal,
            'jamRamadan' => $request->jamRamadan,
            'kodeProgram' => $request->kodeProgram, // Menyimpan kodeProgram
        ]);

        return redirect()->back()->with('success', 'Data jam ajar berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (auth()->user()->level !== 'superadmin') { abort(403); }

        $request->validate([
            'hari' => 'required',
            'jamNormal' => 'required',
            'jamRamadan' => 'required',
            'kodeProgram' => 'required',
        ]);

        DB::table('tbjamajar')->where('id', $id)->update([
            'hari' => $request->hari,
            'jamNormal' => $request->jamNormal,
            'jamRamadan' => $request->jamRamadan,
            'kodeProgram' => $request->kodeProgram, // Mengubah kodeProgram
        ]);

        return redirect()->back()->with('success', 'Data jam ajar berhasil diubah!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (auth()->user()->level !== 'superadmin') { abort(403); }

        DB::table('tbjamajar')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Data jam ajar berhasil dihapus!');
    }
}