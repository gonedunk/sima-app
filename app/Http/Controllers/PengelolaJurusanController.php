<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PengelolaJurusanController extends Controller
{
    /**
     * TAMPILKAN: Mengambil semua data pengelola jurusan dan data dosennya.
     */
    public function index()
    {
        // Menggabungkan data dengan memprioritaskan kolom identitas dari tbdosen
        $pengelola = DB::table('tbpengelolajurusan')
            ->leftJoin('tbdosen', 'tbpengelolajurusan.nip', '=', 'tbdosen.nip')
            ->select(
                'tbpengelolajurusan.id',
                'tbpengelolajurusan.jabatan',
                'tbpengelolajurusan.tanggalMulai',
                'tbpengelolajurusan.tanggalSelesai',
                'tbdosen.nip', // Mengambil NIP murni dari tabel tbdosen sesuai instruksi
                'tbdosen.nama as nama_dosen'
            )
            ->orderBy('tbpengelolajurusan.tanggalMulai', 'desc')
            ->get();

        return view('superadmin.pengelolajurusan.index', compact('pengelola'));
    }

    /**
     * API AJAX FOR SELECT2: Melayani pencarian data dosen secara real-time.
     */
    public function apiDosen(Request $request)
    {
        $search = $request->get('q');

        $data = DB::table('tbdosen')
            ->select('nip as id', DB::raw("CONCAT(nip, ' - ', nama) as text"))
            ->where(function($query) use ($search) {
                if (!empty($search)) {
                    $query->where('nama', 'LIKE', "%$search%")
                          ->orWhere('nip', 'LIKE', "%$search%");
                }
            })
            ->orderBy('nama', 'asc')
            ->limit(20)
            ->get();

        return response()->json($data);
    }

    /**
     * SIMPAN: Menambahkan data pengelola jurusan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nip'            => 'required|string|max:50',
            'jabatan'        => 'required|string|max:100',
            'tanggalMulai'   => 'required|date',
            'tanggalSelesai' => 'nullable|date|after_or_equal:tanggalMulai',
        ]);

        try {
            DB::table('tbpengelolajurusan')->insert([
                'nip'            => $request->nip,
                'jabatan'        => $request->jabatan,
                'tanggalMulai'   => $request->tanggalMulai,
                'tanggalSelesai' => $request->tanggalSelesai,
            ]);

            return redirect()->back()->with('success', 'Data pengelola jurusan baru berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * UBAH: Memperbarui data pengelola jurusan yang sudah ada.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nip'            => 'required|string|max:50',
            'jabatan'        => 'required|string|max:100',
            'tanggalMulai'   => 'required|date',
            'tanggalSelesai' => 'nullable|date|after_or_equal:tanggalMulai',
        ]);

        try {
            DB::table('tbpengelolajurusan')
                ->where('id', $id)
                ->update([
                    'nip'            => $request->nip,
                    'jabatan'        => $request->jabatan,
                    'tanggalMulai'   => $request->tanggalMulai,
                    'tanggalSelesai' => $request->tanggalSelesai,
                ]);

            return redirect()->back()->with('success', 'Data pengelola jurusan berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * HAPUS: Menghapus data pengelola jurusan dari database.
     */
    public function destroy($id)
    {
        try {
            DB::table('tbpengelolajurusan')->where('id', $id)->delete();
            return redirect()->back()->with('success', 'Data pengelola jurusan berhasil deleted!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}