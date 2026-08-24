<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * 1. DASHBOARD UTAMA
     */
    public function dashboard()
    {
        $settingAktif = DB::table('tbsetting')->first();
        $totalUser  = DB::table('users')->count();
        $totalProdi = DB::table('tbprodi')->count();
        $roleUser = auth()->user()->level;

        switch ($roleUser) {
            case 'superadmin':
                $logLogins = DB::table('log_login')->orderBy('waktu_login', 'desc')->limit(10)->get();
                return view('superadmin.index', compact('settingAktif', 'totalUser', 'totalProdi', 'logLogins'));

          
            case 'admin':
                $taBerjalan = $settingAktif->ta_aktif ?? '';
                
                // 1. Ambil ID Admin yang sedang login untuk keamanan query
                $userId = auth()->user()->id;

                // 2. GABUNGAN UTAMA: Ambil kodeJurusan berdasarkan relasi users.kode_prodi -> tbprodi.kodeProdi -> tbjurusan.kodeJurusan
                $adminContext = DB::table('users')
                    ->join('tbprodi', 'users.kode_prodi', '=', 'tbprodi.kodeProdi')
                    ->join('tbjurusan', 'tbprodi.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
                    ->where('users.id', $userId)
                    ->select('tbprodi.kodeJurusan', 'tbjurusan.namaJurusan')
                    ->first();

                // Fallback Guard: Jika data relasi tidak ditemukan, default ke Jurusan Akuntansi (62301)
                $kodeJurusanAdmin = $adminContext ? $adminContext->kodeJurusan : '62301';

                // 3. Ambil daftar SELURUH prodi yang memiliki kodeJurusan yang sama dengan jurusan admin tersebut
                $listProdiJurusan = DB::table('tbprodi')
                    ->where('kodeJurusan', $kodeJurusanAdmin)
                    ->pluck('kodeProdi')
                    ->toArray();

                // 4. HITUNG MAHASISWA AKTIF (Diakumulasikan untuk semua prodi dalam satu jurusan admin)
                $mhsAktif = DB::table('tbkelasmahasiswa')
                    ->where('tahunAkademik', $taBerjalan)
                    ->where('keterangan', 'A')
                    ->whereIn('prodi', $listProdiJurusan)
                    ->count();

                // 5. HITUNG MAHASISWA NON-AKTIF (Diakumulasikan untuk semua prodi dalam satu jurusan admin)
                $mhsNonAktif = DB::table('tbkelasmahasiswa')
                    ->where('tahunAkademik', $taBerjalan)
                    ->where('keterangan', 'NA')
                    ->whereIn('prodi', $listProdiJurusan)
                    ->count();

                // 6. QUERY SEBARAN MAHASISWA: Hanya memunculkan prodi-prodi yang satu rumpun jurusan dengan admin
                $sebaranMhsProdi = DB::table('tbprodi')
                    ->join('tbjurusan', 'tbprodi.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
                    ->leftJoin('tbkelasmahasiswa', function($join) use ($taBerjalan) {
                        $join->on('tbprodi.kodeProdi', '=', 'tbkelasmahasiswa.prodi')
                             ->where('tbkelasmahasiswa.tahunAkademik', '=', $taBerjalan)
                             ->where('tbkelasmahasiswa.keterangan', '=', 'A');
                    })
                    ->select(
                        'tbprodi.namaProdi', 
                        'tbprodi.kodeProdi', 
                        'tbjurusan.namaJurusan',
                        DB::raw('COUNT(tbkelasmahasiswa.id) as total_aktif')
                    )
                    ->where('tbprodi.kodeJurusan', $kodeJurusanAdmin) // Mengunci tampilan data berdasarkan kodeJurusan hasil gabungan
                    ->groupBy('tbprodi.kodeProdi', 'tbprodi.namaProdi', 'tbjurusan.namaJurusan')
                    ->get();

                return view('admin.index', compact('settingAktif', 'totalUser', 'totalProdi', 'mhsAktif', 'mhsNonAktif', 'sebaranMhsProdi', 'adminContext'));  


            case 'dosen':
                return view('dosen.index', compact('settingAktif'));
            case 'pustakawan':
                return view('pustakawan.index', compact('settingAktif'));
            case 'mahasiswa':
                return view('mahasiswa.index', compact('settingAktif'));
            default:
                auth()->logout();
                return redirect('/login')->withErrors(['loginError' => 'Hak akses Anda tidak dikenali sistem.']);
        }
    }

    /**
     * 2. CRUD USER
     */
    public function index()
    {
        $users = DB::table('users')->leftJoin('tbprodi', 'users.kode_prodi', '=', 'tbprodi.kodeProdi')->select('users.*', 'tbprodi.namaProdi')->get();
        $prodis = DB::table('tbprodi')->get();
        return view('superadmin.user.index', compact('users', 'prodis'));
    }

    public function store(Request $request)
    {
        $request->validate(['username' => 'required|unique:users,username', 'password' => 'required', 'nama_lengkap' => 'required']);
        DB::table('users')->insert([
            'nama_lengkap' => $request->nama_lengkap, 'username' => $request->username, 'email' => $request->email,
            'password' => Hash::make($request->password), 'level' => $request->level, 'kode_prodi' => $request->kode_prodi, 'created_at' => now()
        ]);
        return redirect()->route('user.index')->with('success', 'User baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['username' => 'required|unique:users,username,'.$id, 'nama_lengkap' => 'required']);
        $data = ['nama_lengkap' => $request->nama_lengkap, 'username' => $request->username, 'email' => $request->email, 'level' => $request->level, 'kode_prodi' => $request->kode_prodi];
        if ($request->filled('password')) { $data['password'] = Hash::make($request->password); }
        DB::table('users')->where('id', $id)->update($data);
        return redirect()->route('user.index')->with('success', 'Data user berhasil diperbarui!');
    }

    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        return redirect()->route('user.index')->with('success', 'User berhasil dihapus dari sistem!');
    }
}