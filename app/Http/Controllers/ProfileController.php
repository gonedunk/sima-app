<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        return view('superadmin.profile.index', [
            'user' => Auth::user()
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'foto'         => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'foto.image'            => 'Berkas harus berupa gambar.',
            'foto.mimes'            => 'Format foto harus jpeg, png, atau jpg.',
            'foto.max'              => 'Ukuran foto maksimal adalah 2MB.',
        ]);

        $user->nama_lengkap = $request->nama_lengkap;

        // Handle Proses Upload Foto
        if ($request->hasFile('foto')) {
            // 1. Hapus foto lama dari storage jika sebelumnya sudah ada foto yang tersimpan
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }

            // 2. Simpan file baru dengan nama unik berdasarkan timestamp di folder 'uploads/profile'
            $file = $request->file('foto');
            $fileName = time() . '_' . $user->username . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/profile', $fileName, 'public');
            
            // 3. Masukkan path file ke kolom 'foto' di database
            $user->foto = $path;
        }

        $user->save();

return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Password lama yang Anda masukkan salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Password berhasil diganti.');
    }
}