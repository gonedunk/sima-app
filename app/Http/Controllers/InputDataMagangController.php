<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputDataMagangController extends Controller
{
    /**
     * Tampilkan halaman utama penempatan magang dengan pagination.
     */
    /**
     * Tampilkan halaman utama penempatan magang dengan pagination.
     */
    /**
     * Tampilkan halaman utama penempatan magang dengan pencarian & pagination.
     */
    public function index(Request $request) // <-- PERBAIKAN 1: Tambahkan parameter Request $request
    {
        // 1. Ambil Tahun Akademik Aktif dari tbsetting
        $setting = DB::table('tbsetting')->first();
        $taAktif = $setting ? $setting->ta_aktif : null;

        // PERBAIKAN 2: Tangkap input pencarian dari request query string
        $search = $request->get('search');

        // 3. Bangun Query data dari tbnilaiperusahaan berdasarkan tahunAkademik aktif
        $query = DB::table('tbnilaiperusahaan')
            ->select(
                'id',
                'npm',
                'nama',
                'prodi',
                'jurusan',        
                'namaPerusahaan', 
                'anakCabang',     
                'tahunAkademik',
                'tglMulai',
                'tglSelesai'
            )
            ->where('tahunAkademik', $taAktif);

        // PERBAIKAN 3: Jika ada keyword pencarian, filter datanya berdasarkan kolom terkait
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('npm', 'LIKE', "%$search%")
                  ->orWhere('nama', 'LIKE', "%$search%")
                  ->orWhere('namaPerusahaan', 'LIKE', "%$search%")
                  ->orWhere('anakCabang', 'LIKE', "%$search%")
                  ->orWhere('prodi', 'LIKE', "%$search%");
            });
        }

        // 4. Lakukan pengurutan kelompok (agar layout tabel di Blade tidak pecah) dan paginasi
        $daftarNilai = $query->orderBy('namaPerusahaan', 'asc')
            ->orderBy('anakCabang', 'asc')
            ->orderBy('npm', 'asc') 
            ->paginate(15); 

        return view('admin.datamagang.index', compact('daftarNilai', 'taAktif'));
    }

    /**
     * Tampilkan form create (Jika memuat view statis)
     */
    public function create()
    {
        // Karena di-load via AJAX, return view biasa
        return view('admin.datamagang.create');
    }

    /**
     * Menyimpan data penempatan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'npm' => 'required|array',
            'npm.*' => 'required|string',
            'namaPerusahaan' => 'required|string|max:255', 
            'anakCabang' => 'nullable|string|max:255',     
            'tglMulai' => 'required|date',
            'tglSelesai' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->npm as $key => $npm) {
                // Cek duplikasi untuk menghindari NPM yang sama diinput dua kali pada tahun akademik yang sama
                $exists = DB::table('tbnilaiperusahaan')
                    ->where('npm', $npm)
                    ->where('tahunAkademik', $request->tahunAkademik[$key] ?? '')
                    ->exists();

                if ($exists) {
                    continue; // Skip jika sudah ada
                }

                DB::table('tbnilaiperusahaan')->insert([
                    'npm' => $npm,
                    'nama' => $request->nama[$key] ?? '',
                    'prodi' => $request->prodi[$key] ?? '',
                    'jurusan' => $request->jurusan[$key] ?? '', 
                    'namaPerusahaan' => $request->namaPerusahaan,
                    'anakCabang' => $request->anakCabang,
                    'tglMulai' => $request->tglMulai,
                    'tglSelesai' => $request->tglSelesai,
                    'tahunAkademik' => $request->tahunAkademik[$key] ?? '',
                    
                    // Kolom nilai di-default 0
                    'etika' => 0,
                    'disiplin' => 0,
                    'percayaDiri' => 0,
                    'kerjaSama' => 0,
                    'motivasi' => 0,
                    'inisiatifKerja' => 0,
                    'loyalitas' => 0,
                    'tanggungJawab' => 0,
                    'pemahaman' => 0,
                    'PtigaK' => 0,
                    'totalNilai' => 0
                ]);
            }

            DB::commit();
            return redirect()->route('admin.data-magang.index')
                ->with('success', 'Data penempatan magang berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan data magang: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan sistem saat menyimpan data.');
        }
    }

    /**
     * Menarik detail data penempatan untuk modal edit.
     */
/**
 * Menarik detail data penempatan untuk modal edit (Return JSON murni untuk AJAX).
 */
public function edit($id)
{
    // 1. Ambil data baris tunggal secara langsung menggunakan Query Builder dari tbnilaiperusahaan
    $nilai = DB::table('tbnilaiperusahaan')->where('id', $id)->first();
    
    // 2. Validasi jika data tidak ditemukan agar tidak memicu null exception
    if (!$nilai) {
        return response()->json([
            'status' => 'error',
            'message' => 'Data penempatan magang tidak ditemukan.'
        ], 404);
    }

    // 3. KUNCI PERBAIKAN: Kembalikan data dalam bentuk JSON objek murni, BUKAN return view()!
    return response()->json($nilai);
}

    /**
     * Memperbarui informasi instansi penempatan magang.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'namaPerusahaan' => 'required|string|max:255',
            'anakCabang' => 'nullable|string|max:255',
            'tglMulai' => 'required|date',
            'tglSelesai' => 'required|date',
        ]);

        try {
            DB::table('tbnilaiperusahaan')
                ->where('id', $id)
                ->update([
                    'namaPerusahaan' => $request->namaPerusahaan,
                    'anakCabang' => $request->anakCabang,
                    'tglMulai' => $request->tglMulai,
                    'tglSelesai' => $request->tglSelesai,
                ]);

            return redirect()->route('admin.data-magang.index')
                ->with('success', 'Data penempatan magang berhasil diperbarui.');

        } catch (\Exception $e) {
            Log::error('Gagal memperbarui data magang ID ' . $id . ': ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data penempatan.');
        }
    }

    /**
     * Menghapus riwayat penempatan magang tertentu.
     */
    public function destroy($id)
    {
        try {
            DB::table('tbnilaiperusahaan')->where('id', $id)->delete();

            return redirect()->route('admin.data-magang.index')
                ->with('success', 'Data penempatan magang berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Gagal menghapus data magang ID ' . $id . ': ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal menghapus data penempatan.');
        }
    }

    /**
     * API PENCARIAN MAHASISWA AKTIF (DI-RENDER OLEH SELECT2)
     * PERBAIKAN UTAMA: Filter mahasiswa yang sudah ada di tbnilaiperusahaan
     */
    public function ajaxGetMahasiswa(Request $request)
    {
        $search = $request->get('search');
        $npmDikecualikan = $request->get('current_npm'); // Tangkap NPM saat edit jika dikirim dari JS

        $setting = DB::table('tbsetting')->first();
        $taAktif = $setting ? $setting->ta_aktif : null;

        // 1. Ambil list NPM yang sudah ada nilai perusahaan di Tahun Akademik aktif tersebut
        $querySudahDinilai = DB::table('tbnilaiperusahaan')
            ->where('tahunAkademik', $taAktif);

        // Jika dalam mode edit, NPM mahasiswa yang sedang diedit jangan ikut diblokir
        if ($npmDikecualikan) {
            $querySudahDinilai->where('npm', '!=', $npmDikecualikan);
        }

        $npmSudahDinilai = $querySudahDinilai->pluck('npm')->toArray();

        // 2. Query master data mahasiswa, singkirkan yang ada di list array di atas
        $query = DB::table('tbmahasiswa')
            ->join('tbkelasmahasiswa', 'tbmahasiswa.npm', '=', 'tbkelasmahasiswa.npm')
            ->select(
                'tbmahasiswa.npm as id',
                'tbkelasmahasiswa.nama', 
                'tbkelasmahasiswa.prodi', 
                'tbkelasmahasiswa.jurusan', 
                'tbkelasmahasiswa.kelas',
                'tbkelasmahasiswa.semester',
                'tbkelasmahasiswa.tahunAkademik'
            )
            ->where('tbkelasmahasiswa.semester', 5)
            ->whereNotIn('tbmahasiswa.npm', $npmSudahDinilai); // FILTER UTAMA DI SINI

        if ($taAktif) {
            $query->where('tbkelasmahasiswa.tahunAkademik', $taAktif);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('tbkelasmahasiswa.npm', 'LIKE', "%$search%")
                  ->orWhere('tbkelasmahasiswa.nama', 'LIKE', "%$search%");
            });
        }

        $mahasiswa = $query->limit(10)->get();

        $results = [];
        foreach ($mahasiswa as $mhs) {
            $results[] = [
                'id' => $mhs->id,
                'text' => $mhs->id . ' - ' . $mhs->nama . ' (' . $mhs->kelas . ')',
                'nama' => $mhs->nama,
                'prodi' => $mhs->prodi,
                'jurusan' => $mhs->jurusan, 
                'tahun_akademik' => $mhs->tahunAkademik
            ];
        }

        return response()->json($results);
    }

    /**
     * API pencarian perusahaan dari Tabel induk_organisasi (Select2)
     */
    public function ajaxGetPerusahaan(Request $request)
    {
        $search = $request->get('search');

        $perusahaan = DB::table('induk_organisasi')
            ->select('id_induk as id', 'nama_induk as text', 'kategori')
            ->where('nama_induk', 'LIKE', "%$search%")
            ->limit(15)
            ->get();

        return response()->json($perusahaan);
    }

    /**
     * Mengambil semua unit instansi berdasarkan ID Induk
     */
    public function ajaxGetAnakCabang(Request $request)
    {
        $idInduk = $request->get('id_induk');
        $search = $request->get('search');

        if (!$idInduk) {
            return response()->json([]);
        }

        $query = DB::table('unit_instansi')
            ->select('nama_unit as id', 'nama_unit as text') 
            ->where('id_induk', $idInduk);

        if (!empty($search)) {
            $query->where('nama_unit', 'LIKE', "%$search%");
        }

        $anakCabang = $query->get();

        return response()->json($anakCabang);
    }
}