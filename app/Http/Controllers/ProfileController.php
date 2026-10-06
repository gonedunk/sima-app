<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Menampilkan form edit profil.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('superadmin.profile.index', compact('user'));
    }

    /**
     * Memperbarui informasi profil dan foto.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi Input termasuk file foto
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'foto'     => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'], // Maks 2MB
        ], [
            'name.required'  => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email tersebut sudah digunakan oleh akun lain.',
            'foto.image'     => 'File yang diunggah harus berupa gambar.',
            'foto.mimes'     => 'Format gambar harus JPG, JPEG, atau PNG.',
            'foto.max'       => 'Ukuran foto maksimal 2MB.',
        ]);

        // Process Upload Foto Profil Baru
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Hapus foto lama dari storage jika ada
            if ($user->foto && Storage::disk('public')->exists('profile/' . $user->foto)) {
                Storage::disk('public')->delete('profile/' . $user->foto);
            }

            // Simpan foto baru ke folder storage/app/public/profile
            $file->storeAs('profile', $filename, 'public');
            $user->foto = $filename;
        }

        // Update Nama & Email
        $user->name = $request->name;
        $user->email = $request->email;

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profil Anda berhasil diperbarui!');
    }

    /**
     * Memperbarui kata sandi pengguna.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.min'              => 'Password baru minimal terdiri dari 6 karakter.',
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        // Redirect ke profile.edit (bukan profile.index)
        return redirect()->route('profile.edit')->with('success', 'Password berhasil diganti.');
    }
}