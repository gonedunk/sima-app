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
     * Helper privat untuk menentukan status absensi (SP1, SP2, SP3, DO, atau -) berdasarkan alpa
     */
    private function hitungStatusAbsensi($alpa)
    {
        $totalAlpa = intval($alpa);

        if ($totalAlpa >= 29) {
            return 'DO';
        } elseif ($totalAlpa >= 24) {
            return 'SP3';
        } elseif ($totalAlpa >= 18) {
            return 'SP2';
        } elseif ($totalAlpa >= 12) {
            return 'SP1';
        }

        return '-';
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

        // ATURAN MUTLAK GLOBAL: WAJIB TERLAMBAT > 0 ATAU ALPA > 0
        $query->where(function($q) {
            $q->where('tbabsensi.terlambat', '>', 0)
              ->orWhere('tbabsensi.alpa', '>', 0);
        });

        // LOGIKA KONDISIONAL BERDASARKAN FILTER PARAMETER STATUS
        if (!empty($statusFilter)) {
            if ($statusFilter === 'Aman') {
                $query->where(function($q) {
                    $q->where('tbabsensi.statusAbsensi', '=', '-')
                      ->orWhereNull('tbabsensi.statusAbsensi')
                      ->orWhere('tbabsensi.statusAbsensi', '=', '');
                });
            } else {
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

/**
 * AJAX Search Mahasiswa untuk Select2
 */
public function searchMahasiswa(Request $request)
{
    $term = trim($request->q);
    $ta   = $request->ta;
    $role = auth()->user()->level;

    if (empty($term) || empty($ta)) {
        return response()->json([]);
    }

    $query = DB::table('tbkelasmahasiswa')
        ->where('tahunAkademik', $ta)
        ->where(function($q) use ($term) {
            $q->where('npm', 'LIKE', "%{$term}%")
              ->orWhere('nama', 'LIKE', "%{$term}%")
              ->orWhere('kelas', 'LIKE', "%{$term}%");
        });

    if ($role === 'admin') {
        $listProdiJurusan = $this->getAdminProdiList();
        $query->whereIn('prodi', $listProdiJurusan);
    }

    $data = $query->select('npm', 'nama', 'kelas', 'prodi')
        ->limit(20)
        ->get();

    $results = $data->map(function($mhs) {
        return [
            'id'   => $mhs->npm,
            'text' => "{$mhs->npm} - {$mhs->nama} ({$mhs->kelas})"
        ];
    });

    return response()->json($results);
}

/**
 * SIMPAN / OVERWRITE DATA ABSENSI
 */
public function absensiStore(Request $request)
{
    $role = auth()->user()->level;
    if ($role !== 'superadmin' && $role !== 'admin') { abort(403, 'Anda tidak memiliki hak akses.'); }

    $request->validate([
        'npm'            => 'required|string',
        'tahunAkademik'  => 'required|string',
        'terlambat'      => 'required|integer|min:0',
        'alpa'           => 'required|integer|min:0',
        'izin'           => 'required|integer|min:0',
        'sakit'          => 'required|integer|min:0',
        'dispensasi'     => 'required|integer|min:0',
    ]);

    $npm = trim($request->npm);
    $ta  = $request->tahunAkademik;

    // Join / Cek Mahasiswa di tbkelasmahasiswa
    $mhsQuery = DB::table('tbkelasmahasiswa')
        ->where('npm', $npm)
        ->where('tahunAkademik', $ta);

    if ($role === 'admin') {
        $listProdiJurusan = $this->getAdminProdiList();
        $mhsQuery->whereIn('prodi', $listProdiJurusan);
    }

    $mhs = $mhsQuery->first();
    if (!$mhs) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Mahasiswa tidak ditemukan di kelas/prodi pada Tahun Akademik terpilih.');
    }

    $statusAbsensi = $this->hitungStatusAbsensi($request->alpa);

    $dataLama = DB::table('tbabsensi')
        ->where('npm', $npm)
        ->where('tahunAkademik', $ta)
        ->first();

    if ($dataLama) {
        $statusSurat = ($dataLama->statusAbsensi !== $statusAbsensi) ? 'Belum dibuat' : $dataLama->statusSurat;

        DB::table('tbabsensi')->where('id', $dataLama->id)->update([
            'terlambat'     => $request->terlambat,
            'alpa'          => $request->alpa,
            'izin'          => $request->izin,
            'sakit'         => $request->sakit,
            'dispensasi'    => $request->dispensasi,
            'statusAbsensi' => $statusAbsensi,
            'statusSurat'   => $statusSurat
        ]);

        $pesan = 'Data rekap absensi mahasiswa berhasil diperbarui!';
    } else {
        DB::table('tbabsensi')->insert([
            'npm'           => $npm,
            'terlambat'     => $request->terlambat,
            'alpa'          => $request->alpa,
            'izin'          => $request->izin,
            'sakit'         => $request->sakit,
            'dispensasi'    => $request->dispensasi,
            'statusAbsensi' => $statusAbsensi,
            'statusSurat'   => 'Belum dibuat',
            'tahunAkademik' => $ta
        ]);

        $pesan = 'Data rekap absensi mahasiswa berhasil ditambahkan!';
    }

    return redirect()->route('absensi.index', ['ta' => $ta])
        ->with('success', $pesan);
}

    /**
     * UPDATE REKAP ABSENSI MANUAL (EDIT)
     */
    public function absensiUpdate(Request $request, $id)
    {
        $role = auth()->user()->level;
        if ($role !== 'superadmin' && $role !== 'admin') { abort(403, 'Anda tidak memiliki hak akses.'); }

        $request->validate([
            'terlambat'  => 'required|integer|min:0',
            'alpa'       => 'required|integer|min:0',
            'izin'       => 'required|integer|min:0',
            'sakit'      => 'required|integer|min:0',
            'dispensasi' => 'required|integer|min:0',
        ]);

        $absensiLama = DB::table('tbabsensi')->where('id', $id)->first();
        if (!$absensiLama) {
            return redirect()->back()->with('error', 'Data absensi tidak ditemukan.');
        }

        // Validasi Otorisasi Admin
        if ($role === 'admin') {
            $mhs = DB::table('tbkelasmahasiswa')
                ->where('npm', $absensiLama->npm)
                ->where('tahunAkademik', $absensiLama->tahunAkademik)
                ->first();

            $listProdiJurusan = $this->getAdminProdiList();
            if (!$mhs || !in_array($mhs->prodi, $listProdiJurusan)) {
                abort(403, 'Anda tidak memiliki akses untuk mengubah data mahasiswa ini.');
            }
        }

        $statusAbsensiBaru = $this->hitungStatusAbsensi($request->alpa);
        $statusSuratBaru   = $absensiLama->statusSurat ?? 'Belum dibuat';

        // Jika status SP/DO berubah, reset status surat menjadi 'Belum dibuat'
        if ($absensiLama->statusAbsensi !== $statusAbsensiBaru) {
            $statusSuratBaru = 'Belum dibuat';
        }

        DB::table('tbabsensi')->where('id', $id)->update([
            'terlambat'     => $request->terlambat,
            'alpa'          => $request->alpa,
            'izin'          => $request->izin,
            'sakit'         => $request->sakit,
            'dispensasi'    => $request->dispensasi,
            'statusAbsensi' => $statusAbsensiBaru, 
            'statusSurat'   => $statusSuratBaru
        ]);

        return redirect()->back()->with('success', 'Rekap data absensi mahasiswa berhasil diperbarui!');
    }
  
    /**
     * HAPUS REKAP ABSENSI MANUAL (DELETE)
     */
    public function absensiDelete($id)
    {
        $role = auth()->user()->level;
        if ($role !== 'superadmin' && $role !== 'admin') { abort(403, 'Anda tidak memiliki hak akses.'); }

        $absensi = DB::table('tbabsensi')->where('id', $id)->first();
        if (!$absensi) {
            return redirect()->back()->with('error', 'Data absensi tidak ditemukan.');
        }

        // Validasi Otorisasi Admin
        if ($role === 'admin') {
            $mhs = DB::table('tbkelasmahasiswa')
                ->where('npm', $absensi->npm)
                ->where('tahunAkademik', $absensi->tahunAkademik)
                ->first();

            $listProdiJurusan = $this->getAdminProdiList();
            if (!$mhs || !in_array($mhs->prodi, $listProdiJurusan)) {
                abort(403, 'Anda tidak memiliki akses untuk menghapus data mahasiswa ini.');
            }
        }

        DB::table('tbabsensi')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Data rekap absensi mahasiswa berhasil dihapus!');
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
                        'npm'           => $mhs->npm,
                        'terlambat'     => 0,
                        'alpa'          => 0,
                        'izin'          => 0,
                        'sakit'         => 0,
                        'dispensasi'    => 0,
                        'statusAbsensi' => '-', 
                        'statusSurat'   => 'Tidak Ada',
                        'tahunAkademik' => $taTarget,
                        'created_at'    => now(),
                        'updated_at'    => now()
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
                    'NPM'              => $row->npm,
                    'Terlambat (Menit)'=> $row->terlambat,
                    'Alpa (Jam)'       => $row->alpa,
                    'Izin (Jam)'       => $row->izin,
                    'Sakit (Jam)'      => $row->sakit,
                    'Dispensasi (Jam)' => $row->dispensasi,
                    'Tahun Academic'   => $row->tahunAkademik,
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

            $statusBaru = $this->hitungStatusAbsensi($alpa);

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
                    'updated_at'  => now()
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
                    'created_at'    => now(),
                    'updated_at'    => now()
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
            'statusSurat' => $teksSurat,
            'updated_at'  => now()
        ]);

        return redirect()->back()->with('success', 'Nomor usulan surat resmi instansi berhasil disimpan ke statusSurat!');
    }
}