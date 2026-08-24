<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjarDosenController extends Controller
{
    /**
     * Menampilkan halaman form input beban ajar dosen
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $prodiUser = $user->kode_prodi ?? $user->prodi ?? $user->id_prodi ?? null;
        $isSuperAdmin = ($user->level === 'superadmin' || $user->level === 'admin'); 

        $dosen = DB::table('tbdosen')
            ->select('nip', 'nama')
            ->orderBy('nama', 'asc')
            ->get();

        $setting = DB::table('tbsetting')->first();
        $tahunAkademikAktif = $setting ? substr(trim($setting->ta_aktif), 0, 5) : date('Y') . '1';
        $semesterSistem = substr($tahunAkademikAktif, 4, 1); 

        // Master Kelas
        $queryKelas = DB::table('tbkelas')
            ->join('tbprodi', 'tbkelas.kodeProdi', '=', 'tbprodi.kodeProdi')
            ->select('tbkelas.namaKelas', 'tbkelas.kodeProdi', 'tbprodi.namaProdi', 'tbkelas.kodeProgram');

        $queryKelas->where(function($q) use ($semesterSistem) {
            if ($semesterSistem == '1') {
                $q->whereRaw('SUBSTRING(tbkelas.namaKelas, 1, 1) IN ("1", "3", "5", "7")');
            } else {
                $q->whereRaw('SUBSTRING(tbkelas.namaKelas, 1, 1) IN ("2", "4", "6", "8")');
            }
        });

        if (!$isSuperAdmin && !empty($prodiUser)) {
            $queryKelas->where('tbkelas.kodeProdi', $prodiUser);
        }
        $kelas = $queryKelas->orderBy('tbkelas.namaKelas', 'asc')->get();

        // Master Mata Kuliah
        $queryMk = DB::table('tbkurikulum')
            ->select('kodeMk', 'namaMk', 'totalJamPerMinggu', 'semester', 'prodi') 
            ->where('statusKurikulum', 'A') 
            ->where(function($q) use ($semesterSistem) {
                if ($semesterSistem == '1') {
                    $q->whereRaw('MOD(semester, 2) <> 0');
                } else {
                    $q->whereRaw('MOD(semester, 2) = 0');
                }
            });

        if (!$isSuperAdmin && !empty($prodiUser)) {
            $queryMk->where('tbkurikulum.prodi', $prodiUser);
        }
        $matakuliah = $queryMk->orderBy('semester', 'asc')->orderBy('kodeMk', 'asc')->get();

        $listHariAjar = DB::table('tbjamajar')
            ->select('hari')
            ->groupBy('hari')
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
            ->get();

        // Pencarian Global Riwayat
        $search = $request->get('search');
        $detailJadwalRaw = DB::table('tbajardosen')
            ->join('tbdosen', 'tbajardosen.nip', '=', 'tbdosen.nip')
            ->join('tbkurikulum', 'tbajardosen.kodeMk', '=', 'tbkurikulum.kodeMk')
            ->join('tbkelas', 'tbajardosen.kelas', '=', 'tbkelas.namaKelas') 
            ->join('tbjamajar', function($join) {
                $join->on('tbajardosen.jamAjar', '=', 'tbjamajar.id')
                     ->on('tbajardosen.hari', '=', 'tbjamajar.hari');
            })
            ->select(
                'tbdosen.nama as namaDosen',
                'tbajardosen.kodeMk',
                'tbkurikulum.namaMk',
                'tbajardosen.kelas',
                'tbajardosen.hari',
                'tbajardosen.tahunAkademik',
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(tbjamajar.jamNormal, " (Rmd: ", tbjamajar.jamRamadan, ")") ORDER BY CAST(tbjamajar.jamNormal AS UNSIGNED) ASC SEPARATOR ", ") as daftarJam'),
                DB::raw('(
                    SELECT MIN(CAST(j2.jamNormal AS UNSIGNED)) 
                    FROM tbajardosen ad2
                    JOIN tbjamajar j2 ON ad2.jamAjar = j2.id AND ad2.hari = j2.hari
                    WHERE ad2.kelas = tbajardosen.kelas 
                      AND ad2.kodeMk = tbajardosen.kodeMk 
                      AND ad2.hari = tbajardosen.hari 
                      AND ad2.tahunAkademik = tbajardosen.tahunAkademik
                ) as jam_terkecil')
            )
            ->where('tbajardosen.tahunAkademik', $tahunAkademikAktif);

        if (!$isSuperAdmin && !empty($prodiUser)) {
            $detailJadwalRaw->where('tbkelas.kodeProdi', $prodiUser);
        }

        if (!empty($search)) {
            $detailJadwalRaw->where(function($q) use ($search) {
                $q->where('tbdosen.nama', 'LIKE', "%{$search}%")
                  ->orWhere('tbkurikulum.namaMk', 'LIKE', "%{$search}%")
                  ->orWhere('tbajardosen.kelas', 'LIKE', "%{$search}%")
                  ->orWhere('tbajardosen.hari', 'LIKE', "%{$search}%")
                  ->orWhere('tbajardosen.kodeMk', 'LIKE', "%{$search}%");
            });
        }

        $allDetailJadwal = $detailJadwalRaw->groupBy(
                'tbdosen.nama', 'tbajardosen.kodeMk', 'tbkurikulum.namaMk', 
                'tbajardosen.kelas', 'tbajardosen.hari', 'tbajardosen.tahunAkademik'
            )
            ->orderBy('tbdosen.nama', 'asc') 
            ->orderByRaw("FIELD(tbajardosen.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
            ->orderBy('jam_terkecil', 'asc')
            ->get();

        $riwayatAjarGrupDetail = $allDetailJadwal->groupBy('namaDosen');
        $riwayatAjarGrup = collect([]); 

        return view('admin.ajardosen.index', compact(
            'dosen', 'kelas', 'matakuliah', 'tahunAkademikAktif', 
            'listHariAjar', 'riwayatAjarGrup', 'riwayatAjarGrupDetail', 'search'
        ));
    }

    /**
     * Endpoint AJAX: Mengambil info prodi kelas & daftar mata kuliah
     */
    public function getTerisiMatakuliah(Request $request)
    {
        $namaKelas = $request->kelas;
        $setting = DB::table('tbsetting')->first();
        $tahunAkademik = $setting ? substr(trim($setting->ta_aktif), 0, 5) : date('Y') . '1';
        $semesterSistem = substr($tahunAkademik, 4, 1);

        $kelasInfo = DB::table('tbkelas')->where('namaKelas', $namaKelas)->first();
        $kodeProdiKelas = $kelasInfo ? $kelasInfo->kodeProdi : '';

        if (empty($kodeProdiKelas)) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan atau tidak memiliki prodi.']);
        }

        $masterMk = DB::table('tbkurikulum')
            ->select('kodeMk', 'namaMk', 'totalJamPerMinggu', 'semester')
            ->where('prodi', $kodeProdiKelas)
            ->where('statusKurikulum', 'A')
            ->where(function($q) use ($semesterSistem) {
                if ($semesterSistem == '1') {
                    $q->whereRaw('MOD(semester, 2) <> 0');
                } else {
                    $q->whereRaw('MOD(semester, 2) = 0');
                }
            })
            ->get();

        $terisiMk = DB::table('tbajardosen')
            ->where('kelas', $namaKelas)
            ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%')
            ->where('jamAjar', '>', 0)
            ->groupBy('kodeMk')
            ->pluck(DB::raw('COUNT(id) as total_terisi'), 'kodeMk')
            ->toArray();

        $availableMk = [];
        foreach ($masterMk as $mk) {
            $jamTerpakai = isset($terisiMk[$mk->kodeMk]) ? (int)$terisiMk[$mk->kodeMk] : 0;
            $maxJam = (int)$mk->totalJamPerMinggu;

            if ($jamTerpakai < $maxJam) {
                $availableMk[] = [
                    'kodeMk' => $mk->kodeMk,
                    'namaMk' => $mk->namaMk,
                    'sisa'   => ($maxJam - $jamTerpakai),
                    'text'   => $mk->kodeMk . ' - ' . $mk->namaMk . ' (Sisa: ' . ($maxJam - $jamTerpakai) . ' Jam)'
                ];
            }
        }

        return response()->json([
            'success'     => true,
            'kodeProdi'   => $kodeProdiKelas, 
            'terisiMk'    => $terisiMk,
            'availableMk' => $availableMk
        ]);
    }

    /**
     * Endpoint AJAX: Memproduksi HTML Checkbox Jam
     */
    public function getCheckboxJam(Request $request)
    {
        $namaKelas = $request->kelas;
        $hari      = $request->hari;
        $nip       = $request->nip;
        $kodeMk    = $request->kodeMk;

        $setting = DB::table('tbsetting')->first();
        $tahunAkademik = $setting ? substr(trim($setting->ta_aktif), 0, 5) : date('Y') . '1';

        $kelasInfo = DB::table('tbkelas')->where('namaKelas', $namaKelas)->first();

        if (!$kelasInfo || !$hari) {
            return response()->json([
                'success' => false, 
                'html' => '<span class="text-muted small">Pilih kelas dan hari terlebih dahulu...</span>'
            ]);
        }

        // PERBAIKAN LOGIKA: Selalu ambil nilai terbesar (MAX) agar tidak terkunci data parsial di database
        $maxAllowedJam = 0;
        if (!empty($nip) && !empty($kodeMk)) {
            $mkMaster = DB::table('tbkurikulum')->where('kodeMk', $kodeMk)->first();
            $totalJamKurikulum = $mkMaster ? (int)$mkMaster->totalJamPerMinggu : 0;

            $countPlottingTotal = DB::table('tbajardosen')
                ->where('nip', $nip)
                ->where('kelas', $namaKelas)
                ->where('kodeMk', $kodeMk)
                ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%')
                ->count();

            // Menggunakan max() menjamin beban utuh (misal: 5 jam) yang akan dipakai sebagai acuan utama
            $alokasiMaksimalUtuh = max($countPlottingTotal, $totalJamKurikulum);

            // Hitung jam yang sudah terisi di hari lain
            $jamTerisiHariLain = DB::table('tbajardosen')
                ->where('nip', $nip)
                ->where('kelas', $namaKelas)
                ->where('kodeMk', $kodeMk)
                ->where('hari', '!=', $hari)
                ->where('hari', '!=', '-')
                ->where('jamAjar', '>', 0)
                ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%')
                ->count();

            $maxAllowedJam = $alokasiMaksimalUtuh - $jamTerisiHariLain;
        }

        $listJam = DB::table('tbjamajar')
            ->where('hari', $hari)
            ->where('kodeProgram', $kelasInfo->kodeProgram)
            ->orderByRaw('CAST(jamNormal AS UNSIGNED) ASC')
            ->get();

        if ($listJam->isEmpty()) {
            return response()->json([
                'success' => false, 
                'html' => '<span class="text-danger small">Tidak ada master jam mengajar untuk kombinasi hari & program kelas ini.</span>'
            ]);
        }

        // Jadwal eksisting di hari dan kelas ini yang sudah terisi dosen lain
        $jadwalEksisting = DB::table('tbajardosen')
            ->join('tbdosen', 'tbajardosen.nip', '=', 'tbdosen.nip')
            ->where('tbajardosen.kelas', $namaKelas)
            ->where('tbajardosen.hari', $hari)
            ->where('tbajardosen.tahunAkademik', 'LIKE', $tahunAkademik . '%')
            ->where('tbajardosen.jamAjar', '>', 0)
            ->select('tbajardosen.jamAjar', 'tbdosen.nama as nama_dosen')
            ->get()
            ->pluck('nama_dosen', 'jamAjar')
            ->toArray();

        $infoLimit = $maxAllowedJam > 0 ? ' <span class="text-danger">(Maksimal pilih: ' . $maxAllowedJam . ' jam)</span>' : '';

        $html = '<div class="mb-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="checkAllJam" style="cursor:pointer;" ' . ($maxAllowedJam > 0 ? 'disabled' : '') . '>
                        <label class="form-check-label fw-bold text-primary small" style="cursor:pointer;" for="checkAllJam">Pilih Semua Jam Tersedia ' . $infoLimit . '</label>
                    </div>
                 </div>';
        $html .= '<div class="row" id="container-checkbox-jam" data-max-check="' . $maxAllowedJam . '">';
        
        foreach ($listJam as $j) {
            $isTerisi = array_key_exists($j->id, $jadwalEksisting);
            $namaDosenPemberat = $isTerisi ? $jadwalEksisting[$j->id] : '';
            $htmlId = 'jam_' . $j->id;

            $html .= '
            <div class="col-12 col-sm-6 col-md-4 mb-2">
                <div class="p-2 border rounded text-start shadow-xs shadow-sm ' . ($isTerisi ? 'bg-light-danger border-danger-subtle' : 'bg-white') . '" style="' . ($isTerisi ? 'background-color: #fff5f5;' : '') . '">
                    <div class="form-check mb-0">
                        <input class="form-check-input item-jam-checkbox" type="checkbox" 
                            name="jamAjar[]" 
                            value="' . $j->id . '" 
                            id="' . $htmlId . '" 
                            data-terisi="' . ($isTerisi ? '1' : '0') . '"
                            style="cursor:' . ($isTerisi ? 'not-allowed' : 'pointer') . ';"
                            ' . ($isTerisi ? 'disabled' : '') . '>
                        <label class="form-check-label small fw-semibold d-block" style="cursor:' . ($isTerisi ? 'not-allowed' : 'pointer') . ';" for="' . $htmlId . '">
                            Jam Ke- ' . $j->jamNormal . '
                            ' . ($isTerisi ? '<span class="d-block text-danger xsmall font-italic fw-normal text-muted mt-1" style="font-size: 11px;"><i class="fas fa-user-lock"></i> Terisi: ' . $namaDosenPemberat . '</span>' : '') . '
                        </label>
                    </div>
                </div>
            </div>';
        }
        $html .= '</div>';

        if ($maxAllowedJam > 0) {
            $html .= '
            <script>
                docReady(function() {
                    var maxAllowed = ' . $maxAllowedJam . ';
                    var container = document.getElementById("container-checkbox-jam");
                    if(container) {
                        var checkboxes = container.querySelectorAll(".item-jam-checkbox:not([disabled])");
                        checkboxes.forEach(function(cb) {
                            cb.addEventListener("change", function() {
                                var checkedCount = container.querySelectorAll(".item-jam-checkbox:checked").length;
                                if (checkedCount > maxAllowed) {
                                    this.checked = false;
                                    alert("Anda hanya boleh memilih " + maxAllowed + " jam sesuai beban mengajar mata kuliah ini!");
                                }
                            });
                        });
                    }
                });

                function docReady(fn) {
                    if (document.readyState === "complete" || document.readyState === "interactive") {
                        setTimeout(fn, 1);
                    } else {
                        document.addEventListener("DOMContentLoaded", fn);
                    }
                }
            </script>';
        }

        return response()->json(['success' => true, 'html' => $html]);
    }

    /**
     * Memproses penyimpanan data mengajar
     */
    public function store(Request $request)
    {
        $request->validate([
            'nip'           => 'required',
            'kelas'         => 'required|string|max:50',
            'kodeMk'        => 'required',
            'hari'          => 'required|string',
            'jamAjar'       => 'required|array|min:1', 
            'tahunAkademik' => 'required|string|max:50',
        ]);

        $nip           = $request->nip;
        $kelas         = strtoupper(trim($request->kelas));
        $kodeMk        = $request->kodeMk;
        $hari          = $request->hari;
        $arrayJam      = $request->jamAjar;
        $tahunAkademik = substr(trim($request->tahunAkademik), 0, 5);

        $mkInfo = DB::table('tbkurikulum')->where('kodeMk', $kodeMk)->first();
        if (!$mkInfo) {
            return redirect()->back()->withInput()->withErrors(['error' => 'Data Mata Kuliah tidak valid.']);
        }

        // =========================================================================
        // FIX UTAMA: Gunakan fungsi max() agar alokasi utuh 5 jam tetap terbaca resmi
        // =========================================================================
        $countPlottingTotal = DB::table('tbajardosen')
            ->where('nip', $nip)
            ->where('kelas', $kelas)
            ->where('kodeMk', $kodeMk)
            ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%')
            ->count();

        $totalJamKurikulum = $mkInfo ? (int)$mkInfo->totalJamPerMinggu : 0;
        $alokasiMaksimalUtuh = max($countPlottingTotal, $totalJamKurikulum);

        // Hitung beban yang sudah terplot di hari lain
        $jamTerisiHariLain = DB::table('tbajardosen')
            ->where('nip', $nip)
            ->where('kelas', $kelas)
            ->where('kodeMk', $kodeMk)
            ->where('hari', '!=', $hari)
            ->where('hari', '!=', '-')
            ->where('jamAjar', '>', 0)
            ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%')
            ->count();

        $limitMaksimalHariIni = $alokasiMaksimalUtuh - $jamTerisiHariLain;

        if (count($arrayJam) > $limitMaksimalHariIni) {
            return redirect()->back()->withInput()->withErrors([
                'jamAjar' => "Gagal menyimpan! Jam yang dicentang (" . count($arrayJam) . " jam) melebihi sisa batas alokasi mengajar hari ini (" . $limitMaksimalHariIni . " jam dari total beban " . $alokasiMaksimalUtuh . " jam)."
            ]);
        }

        // Ambil baris target tbajardosen yang masih berstatus default ('-') untuk ditimpa
        $barisDefaultTersedia = DB::table('tbajardosen')
            ->where('nip', $nip)
            ->where('kelas', $kelas)
            ->where('kodeMk', $kodeMk)
            ->where('hari', '-')
            ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%')
            ->orderBy('id', 'asc')
            ->get();

        // Validasi Bentrok Jadwal
        foreach ($arrayJam as $jamId) {
            $infoJamMaster = DB::table('tbjamajar')->where('id', $jamId)->first();
            $labelJam = $infoJamMaster ? $infoJamMaster->jamNormal : $jamId;

            // Bentrok Dosen
            $bentrokDosen = DB::table('tbajardosen')
                ->where('nip', $nip)
                ->where('hari', $hari)
                ->where('jamAjar', $jamId)
                ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%');
            
            if ($barisDefaultTersedia->count() > 0) {
                $bentrokDosen->whereNotIn('id', $barisDefaultTersedia->pluck('id')->toArray());
            }
            $resBentrokDosen = $bentrokDosen->select('kelas', 'kodeMk')->first();

            if ($resBentrokDosen) {
                return redirect()->back()->withInput()->withErrors([
                    'jamAjar' => "Batal simpan! Dosen bersangkutan sudah memiliki jadwal mengajar pada Hari {$hari}, Jam Ke-{$labelJam} di kelas {$resBentrokDosen->kelas}."
                ]);
            }

            // Bentrok Kelas
            $bentrokKelas = DB::table('tbajardosen')
                ->join('tbdosen', 'tbajardosen.nip', '=', 'tbdosen.nip')
                ->where('tbajardosen.kelas', $kelas)
                ->where('tbajardosen.hari', $hari)
                ->where('tbajardosen.jamAjar', $jamId)
                ->where('tahunAkademik', 'LIKE', $tahunAkademik . '%')
                ->where('tbajardosen.nip', '!=', $nip)
                ->select('tbdosen.nama', 'tbajardosen.kodeMk')
                ->first();

            if ($bentrokKelas) {
                return redirect()->back()->withInput()->withErrors([
                    'kelas' => "Batal simpan! Ruang Kelas {$kelas} sudah terisi oleh {$bentrokKelas->nama} pada Hari {$hari}, Jam Ke-{$labelJam}."
                ]);
            }
        }

        // Eksekusi Database
        DB::beginTransaction();
        try {
            foreach ($arrayJam as $index => $jamAjar) {
                if ($barisDefaultTersedia->count() > 0 && isset($barisDefaultTersedia[$index])) {
                    // Jika baris default masih ada, overwrite datanya
                    $idTarget = $barisDefaultTersedia[$index]->id;
                    DB::table('tbajardosen')->where('id', $idTarget)->update([
                        'hari'    => $hari,
                        'jamAjar' => $jamAjar
                    ]);
                } else {
                    // Jika baris default habis (karena di DB baru terbuat 2 dari total 5 beban), buat baris BARU
                    DB::table('tbajardosen')->insert([
                        'nip'           => $nip,
                        'kelas'         => $kelas,
                        'kodeMk'        => $kodeMk,
                        'hari'          => $hari,
                        'jamAjar'       => $jamAjar,
                        'tahunAkademik' => $request->tahunAkademik,
                    ]);
                }
            }
            DB::commit();
            return redirect()->back()->with('success', 'Penyusunan jadwal jam mengajar dosen berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * Mengembalikan sekelompok jam mengajar kembali ke status default (reset jadwal)
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'kelas'         => 'required|string',
            'kodeMk'        => 'required|string',
            'hari'          => 'required|string',
            'tahunAkademik' => 'required|string'
        ]);

        try {
            DB::table('tbajardosen')
                ->where('kelas', $request->kelas)
                ->where('kodeMk', $request->kodeMk)
                ->where('hari', $request->hari)
                ->where('tahunAkademik', 'LIKE', substr(trim($request->tahunAkademik), 0, 5) . '%')
                ->update([
                    'hari' => '-',
                    'jamAjar' => 0
                ]);

            return redirect()->back()->with('success', 'Jadwal mengajar kelompok baris tersebut berhasil direset ke status default!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal mereset data: ' . $e->getMessage()]);
        }
    }
}