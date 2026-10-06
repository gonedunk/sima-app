<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArsipAlumniController extends Controller
{
    /**
     * 1. Menampilkan halaman pencarian Portal Alumni.
     */
    public function index()
    {
        return view('alumni.index');
    }

    /**
     * 2. Memproses pencarian berkas berdasarkan NPM.
     */
    public function cariArsip(Request $request)
    {
        $request->validate([
            'npm' => 'required|string',
        ], [
            'npm.required' => 'Nomor Pokok Mahasiswa (NPM) wajib diisi.',
        ]);

        $npm = trim($request->npm);

        // Cari data di tabel arsip_alumni
        $alumni = DB::table('arsip_alumni')->where('npm', $npm)->first();

        if (!$alumni) {
            // Mengarahkan kembali ke halaman portal-alumni jika data tidak ditemukan
            return redirect('/portal-alumni')
                ->withInput()
                ->with('error', 'Data berkas alumni dengan NPM "' . $npm . '" tidak ditemukan. Pastikan NPM yang Anda masukkan sudah benar.');
        }

        return view('alumni.index', compact('alumni'));
    }

    /**
     * 3. Menangani pengunduhan / pratinjau berkas langsung dari Google Drive.
     */
    public function downloadFile(Request $request, $npm, $jenis)
    {
        $alumni = DB::table('arsip_alumni')->where('npm', $npm)->first();

        if (!$alumni) {
            // Mengarahkan kembali ke halaman portal-alumni jika data alumni tidak ada
            return redirect('/portal-alumni')
                ->with('error', 'Data berkas alumni dengan NPM "' . $npm . '" tidak ditemukan. Pastikan NPM yang Anda masukkan sudah benar.');
        }

        $fileName = null;

        // Ambil nama file berdasarkan jenis berkas dari kolom tabel arsip_alumni
        if ($jenis === 'ijazah') {
            $fileName = $alumni->ijazah_gdrive_id ?? null;
        } elseif ($jenis === 'transkrip') {
            $fileName = $alumni->transkrip_gdrive_id ?? null;
        }

        if (empty($fileName)) {
            return back()->with('error', 'Berkas ' . ucfirst($jenis) . ' belum tersedia.');
        }

        // Ambil tahun dari request URL, fallback ke kolom tahun_lulus database
        $tahunLulus = trim($request->get('tahun_lulus', $alumni->tahun_lulus ?? ''));

        // Susun daftar kemungkinan lokasi file di Google Drive
        $possiblePaths = [];

        if (!empty($tahunLulus)) {
            // 1. Path persis sesuai nilai (misal: Arsip-SIMA PRO/20242/file.pdf)
            $possiblePaths[] = 'Arsip-SIMA PRO/' . $tahunLulus . '/' . $fileName;

            // 2. Jika nilai 4 digit (misal: 2024), uji opsi semester 1 & 2 (20241, 20242)
            if (strlen($tahunLulus) === 4) {
                $possiblePaths[] = 'Arsip-SIMA PRO/' . $tahunLulus . '1/' . $fileName;
                $possiblePaths[] = 'Arsip-SIMA PRO/' . $tahunLulus . '2/' . $fileName;
            }
        }

        // 3. Fallback jika file berada di root folder Arsip-SIMA PRO
        $possiblePaths[] = 'Arsip-SIMA PRO/' . $fileName;

        try {
            // Cek keberadaan berkas pada tiap kemungkinan path secara berurutan
            foreach ($possiblePaths as $path) {
                if (Storage::disk('google')->exists($path)) {
                    return Storage::disk('google')->response($path);
                }
            }

            return back()->with('error', 'Berkas ' . ucfirst($jenis) . ' tidak ditemukan di server Google Drive.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengambil berkas dari Google Drive: ' . $e->getMessage());
        }
    }
}