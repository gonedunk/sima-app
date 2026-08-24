<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IjazahJurusanController extends Controller
{
    public function index()
    {
        // 1. Ambil data setting aktif
        $setting = DB::table('tbsetting')->first();

        // 2. Ambil daftar mahasiswa berdasarkan tahunAkademik dan semester akhir (6 & 8)
        $mahasiswaList = DB::table('tbkelasmahasiswa')
            ->select('npm', 'nama', 'kelas', 'prodi')
            ->where('tahunAkademik', $setting->ta_aktif ?? null)
            ->whereIn('semester', [6, 8])
            ->orderBy('nama', 'asc')
            ->get();

        // 3. Ambil daftar ijazah tersimpan berdasarkan tahunAkademik aktif
        $ijazahList = DB::table('tb_ijazah')
            ->join('tbkelasmahasiswa', 'tb_ijazah.npm', '=', 'tbkelasmahasiswa.npm')
            ->select('tb_ijazah.*', 'tbkelasmahasiswa.nama', 'tbkelasmahasiswa.prodi')
            ->where('tbkelasmahasiswa.tahunAkademik', $setting->ta_aktif ?? null)
            ->orderBy('tb_ijazah.created_at', 'desc')
            ->get();

        return view('jurusan.ijazah.index', compact('mahasiswaList', 'ijazahList', 'setting'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'npm'         => 'required',
            'file_ijazah' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            $file = $request->file('file_ijazah');
            $originalName = $file->getClientOriginalName();
            
            $fileNameToSave = time() . '_' . $request->npm . '_' . str_replace(' ', '_', $originalName);
            $path = $file->storeAs('ijazah', $fileNameToSave, 'public');

            DB::table('tb_ijazah')->updateOrInsert(
                ['npm' => $request->npm],
                [
                    'path_file'      => $path,
                    'nama_file_asli' => $originalName,
                    'diunggah_oleh'  => 'admin_jurusan',
                    'created_at'     => now(),
                    'updated_at'     => now()
                ]
            );

            return redirect()->back()->with('success', 'Berkas ijazah berhasil diunggah.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunggah ijazah: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'npm'         => 'required',
            'file_ijazah' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            $ijazah = DB::table('tb_ijazah')->where('id', $id)->first();

            if (!$ijazah) {
                return redirect()->back()->with('error', 'Data ijazah tidak ditemukan.');
            }

            $dataToUpdate = [
                'npm'        => $request->npm,
                'updated_at' => now(),
            ];

            // Jika ada file baru yang diunggah
            if ($request->hasFile('file_ijazah')) {
                // Hapus berkas lama dari storage jika ada
                if ($ijazah->path_file && Storage::disk('public')->exists($ijazah->path_file)) {
                    Storage::disk('public')->delete($ijazah->path_file);
                }

                // Simpan berkas baru
                $file = $request->file('file_ijazah');
                $originalName = $file->getClientOriginalName();
                $fileNameToSave = time() . '_' . $request->npm . '_' . str_replace(' ', '_', $originalName);

                $dataToUpdate['path_file']      = $file->storeAs('ijazah', $fileNameToSave, 'public');
                $dataToUpdate['nama_file_asli'] = $originalName;
            }

            DB::table('tb_ijazah')->where('id', $id)->update($dataToUpdate);

            return redirect()->back()->with('success', 'Data ijazah berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui ijazah: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $ijazah = DB::table('tb_ijazah')->where('id', $id)->first();

            if (!$ijazah) {
                return redirect()->back()->with('error', 'Data ijazah tidak ditemukan.');
            }

            // Hapus berkas fisik dari storage/app/public/ijazah
            if ($ijazah->path_file && Storage::disk('public')->exists($ijazah->path_file)) {
                Storage::disk('public')->delete($ijazah->path_file);
            }

            // Hapus record dari database
            DB::table('tb_ijazah')->where('id', $id)->delete();

            return redirect()->back()->with('success', 'Berkas ijazah berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus ijazah: ' . $e->getMessage());
        }
    }
}