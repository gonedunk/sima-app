<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TranskripMahasiswaController extends Controller
{
    /**
     * Helper privat untuk mengecek apakah Mahasiswa berada dalam lingkup jurusan yang sah
     */
    private function validateMahasiswaJurusan($user)
    {
        if ($user->level === 'superadmin') {
            return true;
        }

        $npm = $user->username ?? $user->npm ?? null;
        if (!$npm) {
            return false;
        }

        $mhs = DB::table('tbkelasmahasiswa')
            ->where('npm', $npm)
            ->first();

        if (!$mhs) {
            return false;
        }

        $adminContext = DB::table('users')
            ->join('tbprodi', 'users.kode_prodi', '=', 'tbprodi.kodeProdi')
            ->join('tbjurusan', 'tbprodi.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
            ->where('users.id', $user->id)
            ->select('tbprodi.kodeJurusan')
            ->first();

        $kodeJurusanAdmin = $adminContext ? $adminContext->kodeJurusan : '62301';

        $validProdiList = DB::table('tbprodi')
            ->where('kodeJurusan', $kodeJurusanAdmin)
            ->pluck('kodeProdi')
            ->toArray();

        return in_array($mhs->prodi, $validProdiList);
    }

    /**
     * Menampilkan halaman upload & status transkrip mahasiswa
     */
    public function index()
    {
        $user = Auth::user();
        $npm = $user->username ?? $user->npm ?? null;

        $transkrip = null;
        if ($npm) {
            $transkrip = DB::table('tb_transkrip')
                ->where('npm', $npm)
                ->first();
        }

        return view('mahasiswa.transkrip.index', compact('transkrip'));
    }

    /**
     * Menyimpan / Mengunggah berkas transkrip baru oleh Mahasiswa
     */
public function store(Request $request)
    {
        $user = Auth::user();
        $npm = $user->username ?? $user->npm;

        // 1. Validasi lingkup Jurusan
        if (!$this->validateMahasiswaJurusan($user)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengunggah transkrip di jurusan ini.');
        }

        // 2. KUNCI SERVER-SIDE: Cek apakah transkrip sudah divalidasi/disetujui oleh admin
        $transkripSaatIni = DB::table('tb_transkrip')
            ->where('npm', $npm)
            ->first();

        if ($transkripSaatIni && in_array(strtolower($transkripSaatIni->status_verifikasi), ['disetujui', 'valid', 'terverifikasi'])) {
            return redirect()->back()->with('error', 'Transkrip Anda telah divalidasi oleh Admin dan tidak dapat diunggah ulang.');
        }

        // 3. Validasi Berkas Input dengan Pesan Kustom (Batas Maksimal 5 MB)
        $request->validate([
            'file_transkrip' => 'required|mimes:pdf|max:5120',
        ], [
            'file_transkrip.required' => 'Silakan pilih berkas transkrip terlebih dahulu.',
            'file_transkrip.mimes'    => 'Berkas transkrip harus berformat PDF.',
            'file_transkrip.max'      => 'Ukuran berkas terlalu besar! Maksimal ukuran file yang dapat diunggah adalah 5 MB.',
        ]);

        if ($request->hasFile('file_transkrip')) {
            $file = $request->file('file_transkrip');
            $originalName = $file->getClientOriginalName();
            
            $fileName = 'transkrip_' . $npm . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('transkrip', $fileName, 'public');

            // Hapus file fisik lama jika mahasiswa mengunggah file baru/revisi
            if ($transkripSaatIni && !empty($transkripSaatIni->path_file) && Storage::disk('public')->exists($transkripSaatIni->path_file)) {
                Storage::disk('public')->delete($transkripSaatIni->path_file);
            }

            // 4. Simpan / Perbarui Record:
            // Opsi ini otomatis mengembalikan status_verifikasi ke 'Belum Diperiksa' dan mengosongkan catatan lama
            DB::table('tb_transkrip')->updateOrInsert(
                ['npm' => $npm],
                [
                    'path_file'         => $path,
                    'nama_file_asli'    => $originalName,
                    'status_verifikasi' => 'Belum Diperiksa', // Otomatis balik ke Belum Diperiksa
                    'catatan'           => null,               // Reset catatan koreksi admin lama
                    'diunggah_oleh'     => 'mahasiswa',
                    'updated_at'        => now(),
                    'created_at'        => $transkripSaatIni ? $transkripSaatIni->created_at : now(),
                ]
            );

            return redirect()->back()->with('success', 'Transkrip berhasil diunggah.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah transkrip.');
    }


    /**
     * Menampilkan/Stream berkas PDF transkrip
     */
    public function showFile()
    {
        $user = Auth::user();
        $npm = $user->username ?? $user->npm;

        $transkrip = DB::table('tb_transkrip')
            ->where('npm', $npm)
            ->first();

        if (!$transkrip || !$transkrip->path_file) {
            abort(404, 'File transkrip tidak ditemukan.');
        }

        $filePath = storage_path('app/public/' . $transkrip->path_file);

        if (!file_exists($filePath)) {
            abort(404, 'File fisik tidak ditemukan.');
        }

        return response()->file($filePath);
    }
}