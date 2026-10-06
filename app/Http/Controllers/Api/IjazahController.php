<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class IjazahController extends Controller
{
    public function store(Request $request)
    {
        // Validasi data masukan dari Google Apps Script
        $request->validate([
            'npm'       => 'required',
            'file_url'  => 'required|url',
            'file_name' => 'required'
        ]);

        try {
            // 1. Cari data mahasiswa berdasarkan NPM menggunakan DB Facade
            $mahasiswa = DB::table('tbkelasmahasiswa')
                ->where('npm', $request->npm)
                ->first();

            if (!$mahasiswa) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'NPM ' . $request->npm . ' tidak ditemukan di database'
                ], 404);
            }

            // 2. Unduh berkas ijazah dari URL Google Drive
            $fileContent = Http::get($request->file_url)->body();

            // 3. Simpan berkas ke storage/app/public/ijazah
            $fileNameToSave = time() . '_' . $request->npm . '_' . $request->file_name;
            Storage::disk('public')->put('ijazah/' . $fileNameToSave, $fileContent);

            // 4. Update kolom statusKeterangan / path berkas menggunakan DB Facade
            DB::table('tbkelasmahasiswa')
                ->where('npm', $request->npm)
                ->update([
                    'statusKeterangan' => 'ijazah/' . $fileNameToSave
                ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Scan ijazah berhasil disimpan untuk NPM ' . $request->npm
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

  public function getMahasiswa()
{
    try {
        // Mengambil npm dan nama dari tbkelasmahasiswa
        $mahasiswa = DB::table('tbkelasmahasiswa')
            ->select('npm', 'nama')
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $mahasiswa
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}