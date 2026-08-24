<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Menggunakan DB Facade untuk query log

class AuthController extends Controller
{
    /**
     * 1. TAMPILKAN FORM LOGIN
     */
    public function showLogin()
    {
        // Jika sudah login, jangan tampilkan form login lagi, langsung lempar ke dashboard
        if (Auth::check()) {
            return redirect('/index');
        }

        return view('login'); // Sesuaikan dengan lokasi view login Anda (misal: resources/views/auth/login.blade.php)
    }

    /**
     * 2. PROSES OTENTIKASI & PENCATATAN LOG LOGIN
     */
    public function login(Request $request)
    {
        // Validasi input awal form login
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('username', 'password');

        // Lakukan pengecekan kredensial ke tabel users
        if (Auth::attempt($credentials)) {
            // Amankan session id agar terhindar dari session fixation attack
            $request->session()->regenerate();

            // Ambil data user objek yang saat ini berhasil melakukan otentikasi
            $user = Auth::user();

            // PROSES INSERT KEDALAM TABEL log_login BERDASARKAN ID USER
            DB::table('log_login')->insert([
                'user_id'      => $user->id,
                'username'     => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'level'        => $user->level,
                'waktu_login'  => now(),                    // Mengambil timestamp waktu saat ini
                'ip_address'   => $request->ip(),          // Menangkap IP Address pengakses (misal: ::1 atau 127.0.0.1)
                'user_agent'   => $request->userAgent(),   // Menangkap jenis Browser & OS perangkat pengakses
            ]);

            // Alihkan user secara aman ke halaman dashboard utama panel superadmin (/index)
            return redirect()->intended('/index')->with('success', 'Selamat datang kembali, ' . $user->nama_lengkap . '!');
        }

        // Jika username atau password tidak cocok, kembalikan dengan pesan galat
        return back()->withErrors([
            'loginError' => 'Username atau password yang Anda masukkan salah.',
        ])->onlyInput('username');
    }

    /**
     * 3. PROSES KELUAR SISTEM (LOGOUT)
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Bersihkan data session yang tersisa
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Kembalikan ke halaman depan / login
        return redirect('/login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}