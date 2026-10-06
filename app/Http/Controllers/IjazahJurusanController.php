<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IjazahJurusanController extends Controller
{
    /**
     * Helper privat untuk mendapatkan daftar kodeProdi yang dikelola 
     * berdasarkan user yang sedang login.
     */
    private function getValidProdiList($user)
    {
        if ($user->level === 'superadmin') {
            return null;
        }

        $adminContext = DB::table('users')
            ->join('tbprodi', 'users.kode_prodi', '=', 'tbprodi.kodeProdi')
            ->join('tbjurusan', 'tbprodi.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
            ->where('users.id', $user->id)
            ->select('tbprodi.kodeJurusan')
            ->first();

        $kodeJurusanAdmin = $adminContext ? $adminContext->kodeJurusan : '62301';

        return DB::table('tbprodi')
            ->where('kodeJurusan', $kodeJurusanAdmin)
            ->pluck('kodeProdi')
            ->toArray();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $validProdiList = $this->getValidProdiList($user);

        // 1. Ambil opsi Tahun Akademik Genap (misal: 20242, 20252, 20262)
        $listTahunAkademik = DB::table('tbtahunakademik')
            ->where(DB::raw('LOWER(semesterAkademik)'), 'genap')
            ->orderBy('tahunAkademik', 'desc')
            ->pluck('tahunAkademik');

        $setting = DB::table('tbsetting')->first();
        $taAktif = $setting ? $setting->ta_aktif : null;

        // 2. Tentukan Tahun Akademik Target (5 digit persis: 20242 / 20252 / 20262)
        if ($request->filled('tahun_akademik')) {
            $taTarget = $request->tahun_akademik;
        } else {
            // Jika ta_aktif di tbsetting berakhiran 1 (Ganjil, misal 20261), 
            // ubah otomatis ke semester Genap tahun bersangkutan (20262)
            if ($taAktif && substr($taAktif, -1) === '1') {
                $taTarget = substr($taAktif, 0, 4) . '2';
            } else {
                $taTarget = $taAktif;
            }
        }

        // Semester akhir untuk D3 (6) dan D4 (8)
        $targetSemester = [6, 8];

        $npmSudahAdaIjazah = DB::table('tb_ijazah')->pluck('npm')->toArray();

        // Subquery NPM yang sudah diarsip ke arsip_alumni
        $subqueryArsip = function ($sub) {
            $sub->select('npm')->from('arsip_alumni');
        };

        // 3. Query Mahasiswa Belum Upload Ijazah
        $mahasiswaQuery = DB::table('tbkelasmahasiswa')
            ->select('npm', 'nama', 'kelas', 'prodi', 'tahunAkademik', 'semester', 'statusKeterangan')
            ->whereIn('semester', $targetSemester)
            ->where('statusKeterangan', 'Lulus')
            ->whereNotIn('npm', $npmSudahAdaIjazah)
            ->whereNotIn('npm', $subqueryArsip);

        // Filter persis sama dengan 5 digit tahunAkademik (misal: '20242')
        if ($taTarget) {
            $mahasiswaQuery->where('tahunAkademik', $taTarget);
        }

        $mahasiswaList = $mahasiswaQuery->distinct()->orderBy('nama', 'asc')->get();

        // 4. Subquery Mahasiswa untuk JOIN ke Ijazah Tersimpan
        $mhsSubquery = DB::table('tbkelasmahasiswa')
            ->select('npm', 'nama', 'prodi', 'tahunAkademik')
            ->when($taTarget, function ($q) use ($taTarget) {
                return $q->where('tahunAkademik', $taTarget);
            })
            ->groupBy('npm', 'nama', 'prodi', 'tahunAkademik');

        // 5. Query Ijazah Tersimpan
        $ijazahQuery = DB::table('tb_ijazah')
            ->joinSub($mhsSubquery, 'mhs', function ($join) {
                $join->on('tb_ijazah.npm', '=', 'mhs.npm');
            })
            ->select('tb_ijazah.*', 'mhs.nama', 'mhs.prodi', 'mhs.tahunAkademik')
            ->whereNotIn('tb_ijazah.npm', $subqueryArsip);

        if ($validProdiList !== null) {
            $mahasiswaQuery->whereIn('tbkelasmahasiswa.prodi', $validProdiList);
            $ijazahQuery->whereIn('mhs.prodi', $validProdiList);
        }

        // Filter Pencarian (Searchbox)
        $search = $request->get('search');
        if (!empty($search)) {
            $ijazahQuery->where(function ($q) use ($search) {
                $q->where('tb_ijazah.npm', 'like', "%{$search}%")
                  ->orWhere('mhs.nama', 'like', "%{$search}%")
                  ->orWhere('mhs.prodi', 'like', "%{$search}%")
                  ->orWhere('tb_ijazah.nama_file_asli', 'like', "%{$search}%");
            });
        }

        // Dynamic Page Length (Show Entries)
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25, 50, 100]) ? $perPage : 10;

        $ijazahList = $ijazahQuery->orderBy('tb_ijazah.created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->all());

        return view('jurusan.ijazah.index', compact('mahasiswaList', 'ijazahList', 'setting', 'search', 'perPage', 'listTahunAkademik'));
    }

    /**
     * Stream / Download Berkas Ijazah secara Aman
     */
    public function showFile($id)
    {
        $user = Auth::user();
        $validProdiList = $this->getValidProdiList($user);

        $ijazah = DB::table('tb_ijazah')
            ->join('tbkelasmahasiswa', 'tb_ijazah.npm', '=', 'tbkelasmahasiswa.npm')
            ->where('tb_ijazah.id', $id)
            ->select('tb_ijazah.*', 'tbkelasmahasiswa.prodi')
            ->first();

        if (!$ijazah) {
            abort(404, 'Data ijazah tidak ditemukan.');
        }

        if ($validProdiList !== null && !in_array($ijazah->prodi, $validProdiList)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat ijazah ini.');
        }

        if (!Storage::disk('public')->exists($ijazah->path_file)) {
            abort(404, 'Berkas fisik tidak ditemukan di server.');
        }

        return Storage::disk('public')->response($ijazah->path_file);
    }

    public function store(Request $request)
    {
        $request->validate([
            'npm'         => 'required',
            'file_ijazah' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            $user = Auth::user();
            $validProdiList = $this->getValidProdiList($user);

            if ($validProdiList !== null) {
                $mhsValid = DB::table('tbkelasmahasiswa')
                    ->where('npm', $request->npm)
                    ->whereIn('prodi', $validProdiList)
                    ->exists();

                if (!$mhsValid) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki otoritas mengunggah ijazah mahasiswa dari prodi/jurusan ini.');
                }
            }

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
                    'updated_at'     => now(),
                    'created_at'     => now()
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
            'file_ijazah' => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            $user = Auth::user();
            $validProdiList = $this->getValidProdiList($user);

            $ijazah = DB::table('tb_ijazah')->where('id', $id)->first();

            if (!$ijazah) {
                return redirect()->back()->with('error', 'Data ijazah tidak ditemukan.');
            }

            if ($validProdiList !== null) {
                $mhsValid = DB::table('tbkelasmahasiswa')
                    ->where('npm', $request->npm)
                    ->whereIn('prodi', $validProdiList)
                    ->exists();

                if (!$mhsValid) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki hak akses memperbarui ijazah mahasiswa dari prodi/jurusan ini.');
                }
            }

            $dataToUpdate = [
                'npm'        => $request->npm,
                'updated_at' => now(),
            ];

            if ($request->hasFile('file_ijazah')) {
                if ($ijazah->path_file && Storage::disk('public')->exists($ijazah->path_file)) {
                    Storage::disk('public')->delete($ijazah->path_file);
                }

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
            $user = Auth::user();
            $validProdiList = $this->getValidProdiList($user);

            $ijazah = DB::table('tb_ijazah')
                ->join('tbkelasmahasiswa', 'tb_ijazah.npm', '=', 'tbkelasmahasiswa.npm')
                ->where('tb_ijazah.id', $id)
                ->select('tb_ijazah.*', 'tbkelasmahasiswa.prodi')
                ->first();

            if (!$ijazah) {
                return redirect()->back()->with('error', 'Data ijazah tidak ditemukan.');
            }

            if ($validProdiList !== null && !in_array($ijazah->prodi, $validProdiList)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus ijazah ini.');
            }

            if ($ijazah->path_file && Storage::disk('public')->exists($ijazah->path_file)) {
                Storage::disk('public')->delete($ijazah->path_file);
            }

            DB::table('tb_ijazah')->where('id', $id)->delete();

            return redirect()->back()->with('success', 'Berkas ijazah berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus ijazah: ' . $e->getMessage());
        }
    }
}