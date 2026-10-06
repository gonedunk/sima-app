<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class DokumenController extends Controller
{
    public function getMahasiswa()
    {
        try {
            $mahasiswa = DB::table('tbkelasmahasiswa')
                ->select('npm', 'nama', 'kelas', 'prodi')
                ->whereIn('semester', [6, 8])
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

    public function storeTranskrip(Request $request)
    {
        $request->validate([
            'npm'       => 'required',
            'file_url'  => 'required|url',
            'file_name' => 'required'
        ]);

        try {
            $mahasiswaExists = DB::table('tbkelasmahasiswa')
                ->where('npm', $request->npm)
                ->exists();

            if (!$mahasiswaExists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'NPM ' . $request->npm . ' tidak ditemukan'
                ], 404);
            }

            // 1. Ubah URL Google Drive ke Direct Download URL
            $downloadUrl = $this->convertDriveUrl($request->file_url);

            // 2. Unduh konten file
            $response = Http::get($downloadUrl);

            if (!$response->successful()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Gagal mengunduh berkas dari URL yang diberikan'
                ], 400);
            }

            $fileContent = $response->body();
            $fileNameToSave = time() . '_' . $request->npm . '_' . sanitize_filename($request->file_name);
            $path = 'transkrip/' . $fileNameToSave;

            // 3. Simpan ke storage public lokal
            Storage::disk('public')->put($path, $fileContent);

            // 4. Cek keberadaan record untuk mempertahankan created_at
            $existing = DB::table('tb_transkrip')->where('npm', $request->npm)->first();

            DB::table('tb_transkrip')->updateOrInsert(
                ['npm' => $request->npm],
                [
                    'path_file'      => $path,
                    'nama_file_asli' => $request->file_name,
                    'diunggah_oleh'  => 'mahasiswa',
                    'created_at'     => $existing ? $existing->created_at : now(),
                    'updated_at'     => now()
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Transkrip berhasil disimpan untuk NPM ' . $request->npm
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Konversi URL View Google Drive menjadi Direct Download Link
     */
    private function convertDriveUrl($url)
    {
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://drive.google.com/uc?export=download&id=' . $matches[1];
        }
        return $url;
    }
}

/**
 * Helper pembersih nama file
 */
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
}