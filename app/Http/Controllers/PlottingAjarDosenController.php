<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlottingAjarDosenController extends Controller
{
    public function index(Request $request)
    {
        // Ambil data user yang login dan tingkat levelnya
        $user = auth()->user();
        $prodiUser = $user->kode_prodi;
        
        // Cek apakah user adalah superadmin
        $isSuperAdmin = ($user->level === 'superadmin'); 

        // 1. Ambil tahun akademik aktif dari database
        $setting = DB::table('tbsetting')->first(); 
        $taAktif = $setting ? trim($setting->ta_aktif) : '20251';
        $semesterAktif = $setting ? trim($setting->semester_aktif) : 'Ganjil';
        $tahunAkademikAktif = $taAktif . ' ' . $semesterAktif;

        // ==========================================
        // TABEL 1: DATA MASTER PLOTTING
        // ==========================================
        $dataPlotting = DB::table('tbajardosen')
            ->leftJoin('tbdosen', 'tbajardosen.nip', '=', 'tbdosen.nip')
            ->leftJoin('tbpengelolajurusan', 'tbajardosen.nip', '=', 'tbpengelolajurusan.nip')
            ->leftJoin('tbkurikulum', 'tbajardosen.kodeMk', '=', 'tbkurikulum.kodeMk')
            ->leftJoin('tbkelas', 'tbajardosen.kelas', '=', 'tbkelas.namaKelas')
            ->leftJoin('tbprogram', 'tbkelas.kodeProgram', '=', 'tbprogram.kodeProgram')
            ->select(
                'tbajardosen.*', 
                'tbdosen.nama', 
                'tbdosen.statusPegawai', 
                'tbpengelolajurusan.jabatan as jabatan_pengelola',
                'tbkurikulum.namaMk', 
                'tbkurikulum.semester', 
                'tbkurikulum.total as sks', 
                'tbkurikulum.totalJamPerMinggu as jam',
                'tbkurikulum.sksProdiT',
                'tbkurikulum.sksProdiP',
                'tbprogram.namaProgram'
            )
            ->where('tbajardosen.tahunAkademik', $taAktif)
            ->when(!$isSuperAdmin, function ($query) use ($prodiUser) {
                return $query->where('tbkelas.kodeProdi', $prodiUser);
            })
            ->get();

        // ==========================================
        // TABEL 2: REKAPITULASI DOSEN (PAGINASI)
        // ==========================================
        $searchRekap = $request->get('search_rekap');

        $queryRekap = DB::table('tbajardosen')
            ->join('tbdosen', 'tbajardosen.nip', '=', 'tbdosen.nip')
            ->join('tbkelas', 'tbajardosen.kelas', '=', 'tbkelas.namaKelas') 
            ->leftJoin('tbpengelolajurusan', 'tbajardosen.nip', '=', 'tbpengelolajurusan.nip')
            ->where('tbajardosen.tahunAkademik', $taAktif)
            ->when(!$isSuperAdmin, function ($query) use ($prodiUser) {
                return $query->where('tbkelas.kodeProdi', $prodiUser);
            })
            ->select('tbajardosen.nip', 'tbdosen.nama', 'tbdosen.statusPegawai', 'tbpengelolajurusan.jabatan as jabatan_pengelola')
            ->groupBy('tbajardosen.nip', 'tbdosen.nama', 'tbdosen.statusPegawai', 'tbpengelolajurusan.jabatan')
            ->orderBy('tbdosen.nama', 'asc');

        if (!empty($searchRekap)) {
            $queryRekap->where(function ($q) use ($searchRekap) {
                $q->where('tbdosen.nama', 'LIKE', "%{$searchRekap}%")
                  ->orWhere('tbajardosen.nip', 'LIKE', "%{$searchRekap}%");
            });
        }

        $rekapDosenPaginator = $queryRekap->paginate(25, ['*'], 'page_rekap')->withQueryString();

        // Ambil data detail mengajar mentah
        $nipDosenDiHalamanIni = $rekapDosenPaginator->pluck('nip')->toArray();

        $detailPlottingRaw = DB::table('tbajardosen')
            ->join('tbdosen', 'tbajardosen.nip', '=', 'tbdosen.nip')
            ->join('tbkurikulum', 'tbajardosen.kodeMk', '=', 'tbkurikulum.kodeMk')
            ->join('tbkelas', 'tbajardosen.kelas', '=', 'tbkelas.namaKelas')
            ->join('tbprogram', 'tbkelas.kodeProgram', '=', 'tbprogram.kodeProgram')
            ->select(
                'tbajardosen.*',
                'tbkurikulum.total as sks',
                'tbkurikulum.totalJamPerMinggu as jam',
                'tbkurikulum.sksProdiT', 
                'tbkurikulum.sksProdiP',
                'tbprogram.namaProgram'
            )
            ->where('tbajardosen.tahunAkademik', $taAktif)
            ->whereIn('tbajardosen.nip', $nipDosenDiHalamanIni)
            ->when(!$isSuperAdmin, function ($query) use ($prodiUser) {
                return $query->where('tbkelas.kodeProdi', $prodiUser);
            })
            ->get();

        $detailPlottingGrup = $detailPlottingRaw->groupBy('nip');

        // ==========================================
        // DATA MASTER FORM COMPONENT (PILIHAN MODAL)
        // ==========================================
        $dosen = DB::table('tbdosen')->select('nip', 'nama')->orderBy('nama', 'asc')->get();
        
        $matakuliah = DB::table('tbkurikulum')
            ->where('statusKurikulum', 'A')
            ->when(!$isSuperAdmin, function ($query) use ($prodiUser) {
                return $query->where('prodi', $prodiUser); 
            })
            ->get();
            
        $masterKelas = DB::table('tbkelas')
            ->when(!$isSuperAdmin, function ($query) use ($prodiUser) {
                return $query->where('kodeProdi', $prodiUser);
            })
            ->select('id', 'namaKelas', 'kodeProgram')
            ->get();

        return view('admin.plottingkelasdosen.index', compact(
            'dataPlotting', 
            'dosen', 
            'matakuliah', 
            'masterKelas', 
            'tahunAkademikAktif',
            'rekapDosenPaginator',
            'detailPlottingGrup'
        ));
    }

    public function store(Request $request)
    {
        // Validasi input kelas
        if (!$request->has('kelas') || !is_array($request->kelas)) {
            return redirect()->back()->with('error', 'Silahkan pilih minimal satu kelas!');
        }

        // 1. Ambil data kurikulum untuk mengetahui totalJamPerMinggu mata kuliah tersebut
        $kurikulum = DB::table('tbkurikulum')
            ->where('kodeMk', $request->kodeMk)
            ->first();

        // Antisipasi jika data mata kuliah tidak ditemukan, default ke 1 jam
        $totalJam = $kurikulum ? (int) $kurikulum->totalJamPerMinggu : 1;

        // Jika di database tercatat 0 jam, berikan nilai fallback 1 jam agar tetap ter-insert
        if ($totalJam <= 0) {
            $totalJam = 1;
        }

        // Menggunakan Database Transaction untuk memastikan jika ada satu baris gagal, data di-rollback
        DB::beginTransaction();

        try {
            // Loop pertama untuk memproses setiap kelas yang dipilih
            foreach ($request->kelas as $k) {
                
                // Loop kedua untuk melakukan insert sebanyak jumlah jam per minggu
                for ($i = 1; $i <= $totalJam; $i++) {
                    DB::table('tbajardosen')->insert([
                        'nip' => $request->nip,
                        'kelas' => strtoupper(trim($k)),
                        'kodeMk' => $request->kodeMk,
                        'tahunAkademik' => $request->tahunAkademik,
                        'hari' => '-',
                        'jamAjar' => 0
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data alokasi berhasil disimpan sebanyak ' . $totalJam . ' jam per kelas!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $kelas = is_array($request->kelas) ? $request->kelas[0] : $request->kelas;

        DB::table('tbajardosen')->where('id', $id)->update([
            'nip' => $request->nip,
            'kelas' => strtoupper(trim($kelas)),
            'kodeMk' => $request->kodeMk,
            'tahunAkademik' => $request->tahunAkademik
        ]);

        return redirect()->back()->with('success', 'Data alokasi berhasil diubah!');
    }

    public function destroy($id)
    {
        DB::table('tbajardosen')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Data dihapus!');
    }
}