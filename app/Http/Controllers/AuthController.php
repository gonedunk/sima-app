<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * 1. TAMPILKAN FORM LOGIN
     */
    public function showLogin()
    {
        // Jika sudah login, lempar sesuai dengan level akun
        if (Auth::check()) {
            if (Auth::user()->level === 'mahasiswa') {
                return redirect()->route('mahasiswa.transkrip.index');
            }
            return redirect('/index');
        }

        return view('login');
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
            // Amankan session id
            $request->session()->regenerate();

            // Ambil data user objek yang saat ini berhasil melakukan otentikasi
            $user = Auth::user();

            // PROSES INSERT KEDALAM TABEL log_login BERDASARKAN ID USER
            DB::table('log_login')->insert([
                'user_id'      => $user->id,
                'username'     => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'level'        => $user->level,
                'waktu_login'  => now(),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ]);

            // PENGARAHAN REDIRECT BERDASARKAN LEVEL USER
            if ($user->level === 'mahasiswa') {
                return redirect()->route('mahasiswa.transkrip.index')
                                 ->with('success', 'Selamat datang kembali, ' . $user->nama_lengkap . '!');
            }

            // Alihkan superadmin / admin / role lain ke dashboard utama (/index)
            return redirect()->intended('/index')
                             ->with('success', 'Selamat datang kembali, ' . $user->nama_lengkap . '!');
        }

        // Cek apakah username yang diinput ada di arsip alumni (untuk membantu alumni yang bingung karena akunnya sudah dibersihkan)
        $isAlumniArsip = DB::table('arsip_alumni')
            ->where('npm', trim($request->username))
            ->exists();

        if ($isAlumniArsip) {
            return back()->withErrors([
                'loginError' => 'Akun Anda telah dibersihkan dari sistem karena sudah lulus. Silakan unduh berkas melalui tombol Portal Download Alumni di bawah.',
            ])->onlyInput('username');
        }

        // Jika username/password biasa tidak cocok
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