<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class AnakPerusahaanController extends Controller
{
    /**
     * Tampilkan data induk organisasi dan unit instansi (dengan pengelompokan)
     */
    /**
     * Tampilkan data induk organisasi dan unit instansi (dengan pengelompokan & pencarian)
     */
    public function index(Request $request) // <-- Tambahkan parameter Request
    {
        // 1. Tangkap input pencarian dari view
        $search = $request->get('search');

        // 2. Bangun query dasar untuk induk organisasi
        $queryInduk = DB::table('induk_organisasi')
            ->select('id_induk', 'nama_induk', 'kategori');

        // 3. Jika ada kata kunci pencarian, filter induk organisasi
        // Serta cek juga apakah kata kunci tersebut cocok dengan nama anak perusahaan di dalamnya
        if (!empty($search)) {
            $queryInduk->where(function($q) use ($search) {
                $q->where('nama_induk', 'LIKE', "%$search%")
                  ->orWhere('kategori', 'LIKE', "%$search%")
                  // Sub-query pencarian: Jika keyword cocok dengan nama anak perusahaan, induknya juga ikut tampil
                  ->orWhereExists(function ($subQuery) use ($search) {
                      $subQuery->select(DB::raw(1))
                          ->from('unit_instansi')
                          ->whereColumn('unit_instansi.id_induk', 'induk_organisasi.id_induk')
                          ->where('nama_unit', 'LIKE', "%$search%");
                  });
            });
        }

        // 4. Eksekusi data induk organisasi dan petakan (map) anak perusahaannya
        $perusahaanInduk = $queryInduk->get()->map(function ($induk) use ($search) {
            
            // Bangun query untuk unit instansi (anak perusahaan)
            $queryAnak = DB::table('unit_instansi')
                ->where('id_induk', $induk->id_induk)
                ->select('id_unit', 'nama_unit', 'wilayah', 'sektor', 'alamat_lengkap');

            // Opsional: Jika sedang mencari, kita bisa highlight/filter anak perusahaan yang namanya sesuai saja
            // Namun jika ingin menampilkan semua anak perusahaan dari induk yang cocok, baris di bawah ini bisa dihapus/dikomentari.
            if (!empty($search)) {
                // Jika ingin anak perusahaan disaring juga saat pencarian dilakukan:
                $queryAnak->where(function($q) use ($search) {
                    $q->where('nama_unit', 'LIKE', "%$search%")
                      ->orWhere('wilayah', 'LIKE', "%$search%")
                      ->orWhere('sektor', 'LIKE', "%$search%");
                });
            }

            // Ambil anak perusahaan/unit yang berelasi
            $induk->anak_perusahaan = $queryAnak->get();
            
            return $induk;
        });

        // 5. Jika filter pencarian menyaring anak perusahaan secara ketat, singkirkan induk yang tidak memiliki anak perusahaan sama sekali (jika dicari berdasarkan anak)
        if (!empty($search)) {
            $perusahaanInduk = $perusahaanInduk->filter(function ($induk) use ($search) {
                // Jika induknya sendiri sudah cocok dengan keyword, biarkan tetap tampil walaupun tidak punya anak
                if (str_contains(strtolower($induk->nama_induk), strtolower($search)) || str_contains(strtolower($induk->kategori), strtolower($search))) {
                    return true;
                }
                // Jika tidak cocok, hanya tampilkan jika ada anak perusahaan yang cocok didalamnya
                return $induk->anak_perusahaan->count() > 0;
            })->values(); // Reset indeks array collection
        }

        return view('superadmin.anakperusahaan.index', compact('perusahaanInduk'));
    }

    /**
     * Simpan data unit instansi baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_induk'       => 'required|exists:induk_organisasi,id_induk',
            'nama_unit'      => 'required|string|max:255',
            'wilayah'        => 'required|string|max:255',
            'sektor'         => 'required|string|max:255',
            'alamat_lengkap' => 'required|string',
        ]);

        try {
            DB::table('unit_instansi')->insert([
                'id_induk'       => $request->id_induk,
                'nama_unit'      => $request->nama_unit,
                'wilayah'        => $request->wilayah,
                'sektor'         => $request->sektor,
                'alamat_lengkap' => $request->alamat_lengkap,
            ]);

            return redirect()->route('superadmin.unit.index')
                ->with('success', 'Data anak perusahaan berhasil ditambahkan!');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui data unit instansi
     */
    public function update(Request $request, $id_unit)
    {
        $request->validate([
            'id_induk'       => 'required|exists:induk_organisasi,id_induk',
            'nama_unit'      => 'required|string|max:255',
            'wilayah'        => 'required|string|max:255',
            'sektor'         => 'required|string|max:255',
            'alamat_lengkap' => 'required|string',
        ]);

        try {
            DB::table('unit_instansi')
                ->where('id_unit', $id_unit)
                ->update([
                    'id_induk'       => $request->id_induk,
                    'nama_unit'      => $request->nama_unit,
                    'wilayah'        => $request->wilayah,
                    'sektor'         => $request->sektor,
                    'alamat_lengkap' => $request->alamat_lengkap,
                ]);

            return redirect()->route('superadmin.unit.index')
                ->with('success', 'Data anak perusahaan berhasil diperbarui!');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Hapus data unit instansi
     */
    public function destroy($id_unit)
    {
        try {
            DB::table('unit_instansi')->where('id_unit', $id_unit)->delete();

            return redirect()->route('superadmin.unit.index')
                ->with('success', 'Data anak perusahaan berhasil dihapus!');
        } catch (Exception $e) {
            return redirect()->route('superadmin.unit.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}