<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Spatie\SimpleExcel\SimpleExcelReader;

class AbsensiController extends Controller
{
    /**
     * Helper privat untuk mengambil rumpun prodi berdasarkan jurusan admin yang login
     */
    private function getAdminProdiList()
    {
        $userId = auth()->user()->id;

        // Tarik kodeJurusan admin melalui join bertingkat users -> tbprodi -> tbjurusan
        $adminContext = DB::table('users')
            ->join('tbprodi', 'users.kode_prodi', '=', 'tbprodi.kodeProdi')
            ->join('tbjurusan', 'tbprodi.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
            ->where('users.id', $userId)
            ->select('tbprodi.kodeJurusan')
            ->first();

        // Fallback default aman jika relasi bermasalah (misal Akuntansi: 62301)
        $kodeJurusanAdmin = $adminContext ? $adminContext->kodeJurusan : '62301';

        // Ambil semua daftar kodeProdi yang berada di bawah naungan jurusan tersebut
        return DB::table('tbprodi')
            ->where('kodeJurusan', $kodeJurusanAdmin)
            ->pluck('kodeProdi')
            ->toArray();
    }

    /**
     * 6. ABSENSI MAHASISWA (SUPERADMIN & ADMIN) - TERINTEGRASI FILTER STATUS SP/AMAN
     */
    public function absensiIndex(Request $request)
    {
        $role = auth()->user()->level;
        if ($role !== 'superadmin' && $role !== 'admin') { abort(403, 'Anda tidak memiliki hak akses.'); }

        // Batasi dropdown filter prodi berdasarkan tingkat jurusan si admin jika dia role 'admin'
        $prodisQuery = DB::table('tbprodi')->select('kodeProdi', 'namaProdi');
        if ($role === 'admin') {
            $listProdiJurusan = $this->getAdminProdiList();
            $prodisQuery->whereIn('kodeProdi', $listProdiJurusan);
        }
        $prodis = $prodisQuery->get();
        
        $tahunAkademiks = DB::table('tbtahunakademik')
            ->select('id', 'tahunAkademik', 'semesterAkademik')
            ->orderBy('tahunAkademik', 'desc')
            ->orderBy('semesterAkademik', 'desc')
            ->get();
            
        $setting = DB::table('tbsetting')->first();
        $taBawaanSistem = $setting ? $setting->ta_aktif : ''; 

        $taAktif = $request->filled('ta') ? $request->input('ta') : $taBawaanSistem;
        $statusFilter = $request->input('status'); 

        // Query Utama dengan fix Collation untuk database local
        $query = DB::table('tbabsensi')
            ->join('tbkelasmahasiswa', function($join) use ($taAktif) {
                $join->on(DB::raw('tbabsensi.npm COLLATE utf8mb4_general_ci'), '=', DB::raw('tbkelasmahasiswa.npm COLLATE utf8mb4_general_ci'))
                     ->where('tbkelasmahasiswa.tahunAkademik', '=', $taAktif);
            })
            ->select('tbabsensi.*', 'tbkelasmahasiswa.nama', 'tbkelasmahasiswa.kelas', 'tbkelasmahasiswa.prodi');

        // VALIDASI AKSES DATA: Jika yang login adalah admin, batasi record data absensi berdasarkan prodi se-jurusan
        if ($role === 'admin') {
            $listProdiJurusan = $this->getAdminProdiList();
            $query->whereIn('tbkelasmahasiswa.prodi', $listProdiJurusan);
        }

        // =========================================================================
        // ATURAN MUTLAK GLOBAL: APAPUN FILTERNYA, WAJIB TERLAMBAT > 0 ATAU ALPA > 0
        // (Mahasiswa dengan alpa=0 dan terlambat=0 otomatis terbuang dari semua lini filter)
        // =========================================================================
        $query->where(function($q) {
            $q->where('tbabsensi.terlambat', '>', 0)
              ->orWhere('tbabsensi.alpa', '>', 0);
        });

        // =========================================================================
        // LOGIKA KONDISIONAL BERDASARKAN FILTER PARAMETER STATUS
        // =========================================================================
        if (!empty($statusFilter)) {
            if ($statusFilter === 'Aman') {
                // Jika pilih filter Aman, tampilkan yang statusnya '-' tapi tetap terikat aturan mutlak di atas
                $query->where(function($q) {
                    $q->where('tbabsensi.statusAbsensi', '=', '-')
                      ->orWhereNull('tbabsensi.statusAbsensi')
                      ->orWhere('tbabsensi.statusAbsensi', '=', '');
                });
            } else {
                // Menampilkan tingkatan khusus seperti SP1, SP2, SP3, atau DO
                $query->where('tbabsensi.statusAbsensi', '=', $statusFilter);
            }
        }

        // Blok Pencarian Aktif 
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tbabsensi.npm', 'like', "%{$search}%")
                  ->orWhere('tbkelasmahasiswa.nama', 'like', "%{$search}%")
                  ->orWhere('tbkelasmahasiswa.kelas', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('prodi')) { 
            $query->where('tbkelasmahasiswa.prodi', $request->prodi); 
        }
        
        if (!empty($taAktif)) { 
            $query->where('tbabsensi.tahunAkademik', $taAktif); 
        }

        $absensis = $query->orderBy('tbkelasmahasiswa.kelas', 'asc')
                          ->orderBy('tbabsensi.npm', 'asc')
                          ->paginate(25)
                          ->withQueryString(); 

        return view('admin.absensi.index', compact('absensis', 'prodis', 'tahunAkademiks', 'setting', 'taAktif'));
    }

    public function absensiSync(Request $request)
    {
        $request->validate(['ta_target' => 'required|string']);
        $taTarget = $request->ta_target;
        $role = auth()->user()->level;

        try {
            $mhsQuery = DB::table('tbkelasmahasiswa')
                ->where('tahunAkademik', $taTarget)
                ->where('keterangan', 'A');

            // Saat sinkronisasi massal, admin prodi hanya bisa menarik mahasiswa dari rumpun jurusannya sendiri
            if ($role === 'admin') {
                $listProdiJurusan = $this->getAdminProdiList();
                $mhsQuery->whereIn('prodi', $listProdiJurusan);
            }

            $mhsAktif = $mhsQuery->get();

            $existingNpms = DB::table('tbabsensi')
                ->where('tahunAkademik', $taTarget)
                ->pluck('npm')
                ->toArray();

            $insertedCount = 0;

            foreach ($mhsAktif as $mhs) {
                if (!in_array($mhs->npm, $existingNpms)) {
                    DB::table('tbabsensi')->insert([
                        'npm' => $mhs->npm,
                        'terlambat' => 0,
                        'alpa' => 0,
                        'izin' => 0,
                        'sakit' => 0,
                        'dispensasi' => 0,
                        'statusAbsensi' => '-', 
                        'statusSurat' => 'Tidak Ada',
                        'tahunAkademik' => $taTarget
                    ]);
                    $insertedCount++;
                }
            }

            return redirect()->route('absensi.index', ['ta' => $taTarget])
                ->with('success', "Sinkronisasi absensi selesai! {$insertedCount} data mahasiswa berhasil dimuat ke TA {$taTarget}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
    }

    public function absensiUpdate(Request $request, $id)
    {
        $role = auth()->user()->level;
        if ($role !== 'superadmin' && $role !== 'admin') { abort(403); }

        $request->validate([
            'terlambat' => 'required|integer|min:0',
            'alpa' => 'required|integer|min:0',
            'izin' => 'required|integer|min:0',
            'sakit' => 'required|integer|min:0',
            'dispensasi' => 'required|integer|min:0',
        ]);

        $totalAlpa = intval($request->alpa);

        if ($totalAlpa >= 29) {
            $statusAbsensiBaru = 'DO';
        } elseif ($totalAlpa >= 24) {
            $statusAbsensiBaru = 'SP3';
        } elseif ($totalAlpa >= 18) {
            $statusAbsensiBaru = 'SP2';
        } elseif ($totalAlpa >= 12) {
            $statusAbsensiBaru = 'SP1';
        } else {
            $statusAbsensiBaru = '-'; 
        }

        $absensiLama = DB::table('tbabsensi')->where('id', $id)->first();
        $statusSuratBaru = $absensiLama ? $absensiLama->statusSurat : 'Belum dibuat';

        if ($absensiLama && $absensiLama->statusAbsensi !== $statusAbsensiBaru) {
            $statusSuratBaru = 'Belum dibuat';
        }

        DB::table('tbabsensi')->where('id', $id)->update([
            'terlambat' => $request->terlambat,
            'alpa' => $request->alpa,
            'izin' => $request->izin,
            'sakit' => $request->sakit,
            'dispensasi' => $request->dispensasi,
            'statusAbsensi' => $statusAbsensiBaru, 
            'statusSurat' => $statusSuratBaru,
        ]);

        return redirect()->back()->with('withQueryString')->with('success', 'Rekap data absensi mahasiswa berhasil diperbarui secara manual!');
    }
  
    public function absensiDelete($id)
    {
        $role = auth()->user()->level;
        if ($role !== 'superadmin' && $role !== 'admin') { abort(403); }

        DB::table('tbabsensi')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Data rekap absensi mahasiswa berhasil dihapus dari tabel!');
    }

    public function absensiExport(Request $request)
    {
        $setting = DB::table('tbsetting')->first();
        $taAktif = $request->input('ta', $setting->ta_aktif ?? '');
        $role = auth()->user()->level;

        $queryExport = DB::table('tbabsensi')
            ->select('tbabsensi.npm', 'tbabsensi.terlambat', 'tbabsensi.alpa', 'tbabsensi.izin', 'tbabsensi.sakit', 'tbabsensi.dispensasi', 'tbabsensi.tahunAkademik')
            ->where('tbabsensi.tahunAkademik', $taAktif);

        if ($role === 'admin') {
            $listProdiJurusan = $this->getAdminProdiList();
            $queryExport->join('tbkelasmahasiswa', 'tbabsensi.npm', '=', 'tbkelasmahasiswa.npm')
                        ->whereIn('tbkelasmahasiswa.prodi', $listProdiJurusan);
        }

        $dataAbsensi = $queryExport->limit(2)->get();

        $writer = SimpleExcelWriter::streamDownload("Template_Absensi_{$taAktif}.xlsx");

        if ($dataAbsensi->isNotEmpty()) {
            foreach ($dataAbsensi as $row) {
                $writer->addRow([
                    'NPM' => $row->npm,
                    'Terlambat (Menit)' => $row->terlambat,
                    'Alpa (Jam)' => $row->alpa,
                    'Izin (Jam)' => $row->izin,
                    'Sakit (Jam)' => $row->sakit,
                    'Dispensasi (Jam)' => $row->dispensasi,
                    'Tahun Academic' => $row->tahunAkademik,
                ]);
            }
        } else {
            $writer->addRow([
                'NPM' => '062130500001', 'Terlambat (Menit)' => '0', 'Alpa (Jam)' => '0', 'Izin (Jam)' => '0', 'Sakit (Jam)' => '0', 'Dispensasi (Jam)' => '0', 'Tahun Academic' => $taAktif,
            ]);
            $writer->addRow([
                'NPM' => '062130500002', 'Terlambat (Menit)' => '0', 'Alpa (Jam)' => '0', 'Izin (Jam)' => '0', 'Sakit (Jam)' => '0', 'Dispensasi (Jam)' => '0', 'Tahun Academic' => $taAktif,
            ]);
        }

        return $writer->toBrowser();
    }

    public function absensiImport(Request $request)
    {
        $request->validate(['file_excel' => 'required|file|max:5120']);
        
        $filePath = $request->file('file_excel')->getPathname();
        $reader = SimpleExcelReader::create($filePath, 'xlsx');
        $rows = $reader->getRows();

        $jumlahBerhasil = 0;

        foreach ($rows as $row) {
            $cleanRow = [];
            foreach ($row as $key => $val) {
                $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $key));
                $cleanRow[$cleanKey] = $val;
            }

            $npm = isset($cleanRow['npm']) ? trim($cleanRow['npm']) : null;
            if (empty($npm)) { continue; }

            $terlambat  = $cleanRow['terlambatmenit'] ?? $cleanRow['terlambat'] ?? 0;
            $alpa       = $cleanRow['alpajam'] ?? $cleanRow['alpa'] ?? 0;
            $izin       = $cleanRow['izinjam'] ?? $cleanRow['izin'] ?? 0;
            $sakit      = $cleanRow['sakitjam'] ?? $cleanRow['sakit'] ?? 0;
            $dispensasi = $cleanRow['dispensasijam'] ?? $cleanRow['dispensasi'] ?? 0;
            $taExcel    = isset($cleanRow['tahunakademik']) ? trim($cleanRow['tahunakademik']) : ($cleanRow['tahunacademic'] ?? null);
            
            if (empty($taExcel)) { continue; } 

            $statusBaru = '-';
            if ($alpa < 12) {
                $statusBaru = '-';
            } elseif ($alpa >= 12 && $alpa < 18) {
                $statusBaru = 'SP1';
            } elseif ($alpa >= 18 && $alpa < 24) {
                $statusBaru = 'SP2';
            } elseif ($alpa >= 24 && $alpa < 29) {
                $statusBaru = 'SP3';
            } elseif ($alpa >= 29) {
                $statusBaru = 'DO';
            }

            $dataLama = DB::table('tbabsensi')
                ->where('npm', $npm)
                ->where('tahunAkademik', $taExcel)
                ->orderBy('id', 'desc')
                ->first();

            if ($dataLama && trim($dataLama->statusAbsensi) === $statusBaru) {
                DB::table('tbabsensi')->where('id', $dataLama->id)->update([
                    'terlambat'   => $terlambat,
                    'alpa'        => $alpa,
                    'izin'        => $izin,
                    'sakit'       => $sakit,
                    'dispensasi'  => $dispensasi,
                    'statusSurat' => 'Belum dibuat', 
                ]);
            } else {
                DB::table('tbabsensi')->insert([
                    'npm'           => $npm,
                    'terlambat'     => $terlambat,
                    'alpa'          => $alpa,
                    'izin'          => $izin,
                    'sakit'         => $sakit,
                    'dispensasi'    => $dispensasi,
                    'statusAbsensi' => $statusBaru,   
                    'statusSurat'   => 'Belum dibuat', 
                    'tahunAkademik' => $taExcel,      
                ]);
            }

            $jumlahBerhasil++;
        }

        if ($jumlahBerhasil === 0) {
            return redirect()->back()->with('error', 'Gagal memproses file! Pastikan file Excel memiliki baris data dan kolom header bernama "NPM" serta "Tahun Akademik".');
        }

        return redirect()->back()->with('success', "Berhasil menyinkronkan {$jumlahBerhasil} data mahasiswa berdasarkan Tahun Akademik Excel!");
    }

    public function absensiBuatSurat(Request $request, $id)
    {
        $request->validate(['nomor_surat' => 'required|string|max:100']);

        $abs = DB::table('tbabsensi')->where('id', $id)->first();
        if (!$abs) { return redirect()->back()->with('error', 'Data mahasiswa tidak ditemukan.'); }

        $teksSurat = "{$abs->statusAbsensi} sudah dibuat dengan nomor: " . $request->nomor_surat;

        DB::table('tbabsensi')->where('id', $id)->update([
            'statusSurat' => $teksSurat
        ]);

        return redirect()->back()->with('success', 'Nomor usulan surat resmi instansi berhasil disimpan ke statusSurat!');
    }
}