<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MASTER DATA PEGAWAI (DOSEN & TENDIK)
 * Terhubung langsung ke tbdosen, tbagama, tbpangkatgolongan, dan tbuniversitas
 */
class DosenController extends Controller
{
    public function dosenIndex(Request $request)
    {
        // Validasi Hak Akses Admin / Superadmin
        $role = auth()->user()->level;
        if ($role !== 'superadmin' && $role !== 'admin') { 
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.'); 
        }

        // Ambil Data Master untuk Form Dropdown Select2 dari Tabel Lokal Anda
        $agamas = DB::table('tbagama')->select('kodeAgama', 'namaAgama')->get();
        $golongans = DB::table('tbpangkatgolongan')->select('golonganRuang', 'jabatanAkademik')->get();
        $universitases = DB::table('tbuniversitas')->select('namaPt')->orderBy('namaPt', 'asc')->get();

        // Query Utama tbdosen dengan Left Join ke tbagama
        $query = DB::table('tbdosen')
            ->leftJoin('tbagama', 'tbdosen.agama', '=', 'tbagama.kodeAgama')
            ->select('tbdosen.*', 'tbagama.namaAgama');

        // Filter Pencarian (NIP, NIDN, atau Nama)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('nidn', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        // Filter Kategori Level (01 = Dosen, 02 = Tendik)
        if ($request->filled('level')) {
            $query->where('tbdosen.level', $request->level);
        }

        // Filter Status Kepegawaian (LB, PPPK, CPNS, PNS)
        if ($request->filled('statusPegawai')) {
            $query->where('tbdosen.statusPegawai', $request->statusPegawai);
        }

        // Pagination 25 Data per Halaman dengan mempertahankan Query String Filter
        $pegawais = $query->orderBy('tbdosen.level', 'asc')
                          ->orderBy('tbdosen.nama', 'asc')
                          ->paginate(25)
                          ->withQueryString();

        return view('admin.dosen.index', compact('pegawais', 'agamas', 'golongans', 'universitases'));
    }

    /**
     * PROSES SIMPAN PEGAWAI BARU
     */
    public function dosenStore(Request $request)
    {
        $request->validate([
            'nama'  => 'required|string|max:150',
            'level' => 'required|in:01,02',
            'nip'   => 'nullable',
            'nidn'  => 'nullable',
        ]);

        try {
            DB::table('tbdosen')->insert([
                'nip'           => $request->nip ? trim($request->nip) : null,
                'nidn'          => $request->nidn ? trim($request->nidn) : null,
                'nama'          => $request->nama,
                'tmt_cpns'      => $request->tmt_cpns,
                'golongan'      => $request->golongan,
                'tmtGolongan'   => $request->tmtGolongan,
                'statusPegawai' => $request->statusPegawai,
                'pendidikan'    => $request->pendidikan,
                'universitas'   => $request->universitas,
                'jenisKelamin'  => $request->jenisKelamin ?? 'L',
                'agama'         => $request->agama, 
                'namaJabatan'   => $request->namaJabatan, 
                'tmtJabatan'    => $request->tmtJabatan,
                'level'         => $request->level
            ]);

            return redirect()->route('dosen.index')->with('success', 'Data pegawai baru berhasil disimpan ke database!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * PROSES UPDATE DATA PEGAWAI
     */
    public function dosenUpdate(Request $request, $id)
    {
        $request->validate([
            'nama'  => 'required|string|max:150',
            'level' => 'required|in:01,02',
        ]);

        try {
            DB::table('tbdosen')->where('id', $id)->update([
                'nip'           => $request->nip ? trim($request->nip) : null,
                'nidn'          => $request->nidn ? trim($request->nidn) : null,
                'nama'          => $request->nama,
                'tmt_cpns'      => $request->tmt_cpns,
                'golongan'      => $request->golongan,
                'tmtGolongan'   => $request->tmtGolongan,
                'statusPegawai' => $request->statusPegawai,
                'pendidikan'    => $request->pendidikan,
                'universitas'   => $request->universitas,
                'jenisKelamin'  => $request->jenisKelamin ?? 'L',
                'agama'         => $request->agama,
                'namaJabatan'   => $request->namaJabatan,
                'tmtJabatan'    => $request->tmtJabatan,
                'level'         => $request->level
            ]);

            return redirect()->route('dosen.index')->with('success', 'Perubahan data profil pegawai berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * PROSES HAPUS PEGAWAI
     */
    public function dosenDestroy($id)
    {
        $role = auth()->user()->level;
        if ($role !== 'superadmin' && $role !== 'admin') { 
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data.'); 
        }

        DB::table('tbdosen')->where('id', $id)->delete();
        return redirect()->route('dosen.index')->with('success', 'Data pegawai berhasil dihapus dari arsip database!');
    }
}