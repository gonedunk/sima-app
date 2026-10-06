<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminArsipAlumniController extends Controller
{
    /**
     * Menampilkan daftar alumni dengan filter & pencarian.
     */
    public function index(Request $request)
    {
        // Option dropdown Tahun Lulus unik
        $tahunOptions = DB::table('arsip_alumni')
            ->whereNotNull('tahun_lulus')
            ->distinct()
            ->orderBy('tahun_lulus', 'desc')
            ->pluck('tahun_lulus');

        $selectedTahun = $request->input('tahun_lulus');
        $search = $request->input('search');

        $query = DB::table('arsip_alumni');

        // Filter Tahun Lulus
        if (!empty($selectedTahun)) {
            $query->where('tahun_lulus', $selectedTahun);
        }

        // Filter Pencarian
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('npm', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('program_studi', 'like', "%{$search}%");
            });
        }

        $alumniList = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.alumni.index', compact('alumniList', 'tahunOptions', 'selectedTahun', 'search'));
    }

    /**
     * Menyimpan data alumni baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'npm' => 'required|string|max:255|unique:arsip_alumni,npm',
            'nama' => 'required|string|max:255',
            'program_studi' => 'nullable|string|max:255',
            'tahun_lulus' => 'nullable|string|max:10',
            'ijazah_gdrive_id' => 'nullable|string|max:255',
            'transkrip_gdrive_id' => 'nullable|string|max:255',
        ]);

        DB::table('arsip_alumni')->insert([
            'npm' => $request->npm,
            'nama' => $request->nama,
            'program_studi' => $request->program_studi,
            'tahun_lulus' => $request->tahun_lulus,
            'ijazah_gdrive_id' => $request->ijazah_gdrive_id,
            'transkrip_gdrive_id' => $request->transkrip_gdrive_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Data alumni berhasil ditambahkan.');
    }

    /**
     * Memperbarui data alumni.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'npm' => 'required|string|max:255|unique:arsip_alumni,npm,' . $id,
            'nama' => 'required|string|max:255',
            'program_studi' => 'nullable|string|max:255',
            'tahun_lulus' => 'nullable|string|max:10',
            'ijazah_gdrive_id' => 'nullable|string|max:255',
            'transkrip_gdrive_id' => 'nullable|string|max:255',
        ]);

        DB::table('arsip_alumni')
            ->where('id', $id)
            ->update([
                'npm' => $request->npm,
                'nama' => $request->nama,
                'program_studi' => $request->program_studi,
                'tahun_lulus' => $request->tahun_lulus,
                'ijazah_gdrive_id' => $request->ijazah_gdrive_id,
                'transkrip_gdrive_id' => $request->transkrip_gdrive_id,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Data alumni berhasil diperbarui.');
    }

    /**
     * Menghapus data alumni.
     */
    public function destroy($id)
    {
        DB::table('arsip_alumni')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Data alumni berhasil dihapus.');
    }

    /**
     * Stream / Download berkas langsung dari Google Drive.
     */
    public function download(Request $request, $npm, $jenis)
    {
        $alumni = DB::table('arsip_alumni')->where('npm', $npm)->first();

        if (!$alumni) {
            return back()->with('error', 'Data alumni tidak ditemukan.');
        }

        $fileName = null;
        if ($jenis === 'ijazah') {
            $fileName = $alumni->ijazah_gdrive_id ?? null;
        } elseif ($jenis === 'transkrip') {
            $fileName = $alumni->transkrip_gdrive_id ?? null;
        }

        if (empty($fileName)) {
            return back()->with('error', 'Berkas ' . ucfirst($jenis) . ' untuk alumni ' . $alumni->nama . ' belum diunggah.');
        }

        // Ambil tahun dari request URL, fallback ke kolom tahun_lulus database
        $tahunLulus = trim($request->get('tahun_lulus', $alumni->tahun_lulus ?? ''));

        // Susun opsi kemungkinan path folder di Google Drive
        $possiblePaths = [];

        if (!empty($tahunLulus)) {
            // 1. Path persis sesuai nilai (misal: Arsip-SIMA PRO/20242/file.pdf)
            $possiblePaths[] = 'Arsip-SIMA PRO/' . $tahunLulus . '/' . $fileName;

            // 2. Jika nilai di database 4 digit (misal: 2024), uji juga opsi semester 1 & 2 (20241, 20242)
            if (strlen($tahunLulus) === 4) {
                $possiblePaths[] = 'Arsip-SIMA PRO/' . $tahunLulus . '1/' . $fileName;
                $possiblePaths[] = 'Arsip-SIMA PRO/' . $tahunLulus . '2/' . $fileName;
            }
        }

        // 3. Fallback jika file berada di root folder Arsip-SIMA PRO
        $possiblePaths[] = 'Arsip-SIMA PRO/' . $fileName;

        try {
            // Cari lokasi fisik berkas di Google Drive
            foreach ($possiblePaths as $path) {
                if (Storage::disk('google')->exists($path)) {
                    return Storage::disk('google')->response($path);
                }
            }

            return back()->with('error', 'Berkas tidak ditemukan pada server Google Drive.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengakses Google Drive: ' . $e->getMessage());
        }
    }
}