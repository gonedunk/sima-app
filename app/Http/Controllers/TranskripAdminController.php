<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class TranskripAdminController extends Controller
{    /**
     * Helper privat untuk mengambil Tahun Akademik Aktif dari tbsetting.
     * Mengambil 4 digit pertama agar valid sebagai format tahun (contoh: '20242' menjadi '2024').
     */
    private function getTaAktif()
    {
        $setting = DB::table('tbsetting')->first();
        $ta = $setting ? (string) $setting->ta_aktif : (string) date('Y');

        return strlen($ta) >= 4 ? substr($ta, 0, 4) : $ta;
    }

    /**
     * Helper privat untuk membatasi query berdasarkan jurusan milik Admin yang login
     * serta mencocokkan Tahun Akademik.
     */
    private function applyProdiFilterByRole($query, $request = null)
    {
        $user = Auth::user();

        // Ambil 4 digit tahun akademik dari request dropdown jika ada, 
        // jika tidak ada, gunakan default dari getTaAktif()
        if ($request && method_exists($request, 'filled') && $request->filled('tahun_akademik')) {
            $taTarget = substr($request->tahun_akademik, 0, 4);
        } else {
            $taTarget = $this->getTaAktif();
        }

        // Filter Mahasiswa Semester Akhir (D3 = Sem 6, D4 = Sem 8)
        $filterSemesterAkhir = function ($q) use ($taTarget) {
            $q->where('km.tahunAkademik', 'like', $taTarget . '%')
              ->where(function ($sub) {
                  $sub->where(function ($d3) {
                      $d3->where('km.prodi', '3050')->where('km.semester', 6);
                  })->orWhere(function ($d4) {
                      $d4->where('km.prodi', '4051')->where('km.semester', 8);
                  });
              });
        };

        // Jika Superadmin
        if ($user->level === 'superadmin') {
            return $query->where($filterSemesterAkhir);
        }

        // Ambil kodeJurusan Admin dari relasi tbprodi & tbjurusan
        $adminContext = DB::table('users')
            ->join('tbprodi', 'users.kode_prodi', '=', 'tbprodi.kodeProdi')
            ->join('tbjurusan', 'tbprodi.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
            ->where('users.id', $user->id)
            ->select('tbprodi.kodeJurusan')
            ->first();

        $kodeJurusanAdmin = $adminContext ? $adminContext->kodeJurusan : '62301';

        $listProdiJurusan = DB::table('tbprodi')
            ->where('kodeJurusan', $kodeJurusanAdmin)
            ->pluck('kodeProdi')
            ->toArray();

        return $query->whereIn('km.prodi', $listProdiJurusan)
                     ->where($filterSemesterAkhir);
    }

    /**
     * Menampilkan Halaman Pengecekan Transkrip Nilai (Sisi Admin)
     */

public function index(Request $request)
    {
        // 1. Ambil opsi Tahun Akademik yang semesterAkademik-nya 'Genap'
        $listTahunAkademik = DB::table('tbtahunakademik')
            ->where(DB::raw('LOWER(semesterAkademik)'), 'genap')
            ->orderBy('tahunAkademik', 'desc')
            ->pluck('tahunAkademik');

        $query = DB::table('tbkelasmahasiswa as km')
            ->leftJoin('tb_transkrip as t', 'km.npm', '=', 't.npm')
            ->select(
                'km.npm',
                'km.nama',
                'km.prodi',
                'km.semester',
                'km.tahunAkademik',
                't.id as id_transkrip',
                't.path_file',
                't.nama_file_asli',
                't.status_verifikasi',
                't.catatan',
                't.updated_at as tgl_upload'
            )
            ->where('km.statusKeterangan', 'Lulus')
            // Sembunyikan mahasiswa yang NPM-nya sudah diarsip ke tabel arsip_alumni
            ->whereNotIn('km.npm', function ($sub) {
                $sub->select('npm')->from('arsip_alumni');
            });

        // 2. Batasi data berdasarkan Jurusan Admin / Superadmin & TA (Kirimkan $request)
        $this->applyProdiFilterByRole($query, $request);

        // Filter Spesifik Prodi dari input select/filter
        if ($request->filled('prodi')) {
            $query->where('km.prodi', $request->prodi);
        }

        // Filter Status Verifikasi
        if ($request->filled('status')) {
            if ($request->status == 'Belum Diperiksa') {
                $query->where(function ($q) {
                    $q->whereNull('t.status_verifikasi')
                      ->orWhere('t.status_verifikasi', 'Belum Diperiksa');
                });
            } else {
                $query->where('t.status_verifikasi', $request->status);
            }
        }
        
        // Filter Upload Transkrip
        if ($request->filled('upload_status')) {
            if ($request->upload_status === 'sudah') {
                $query->whereNotNull('t.path_file')->where('t.path_file', '!=', '');
            } elseif ($request->upload_status === 'belum') {
                $query->where(function($q) {
                    $q->whereNull('t.path_file')->orWhere('t.path_file', '');
                });
            }
        }

        // Pencarian NPM atau Nama Mahasiswa
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('km.npm', 'like', "%{$search}%")
                  ->orWhere('km.nama', 'like', "%{$search}%");
            });
        }

        $transkrip = $query->orderBy('km.nama', 'asc')->paginate(15)->withQueryString();

        return view('jurusan.transkrip.admin_check', compact('transkrip', 'listTahunAkademik'));
    }

    /**
     * Memperbarui Status Verifikasi dan Catatan Transkrip Nilai
     */
    public function verifikasi(Request $request, $npm)
    {
        $request->validate([
            'status_verifikasi' => 'required|in:Valid,Invalid,Belum Diperiksa',
            'catatan'           => 'nullable|string',
        ]);

        DB::table('tb_transkrip')->updateOrInsert(
            ['npm' => $npm],
            [
                'status_verifikasi' => $request->status_verifikasi,
                'catatan'           => $request->catatan,
                'verified_at'       => now(),
                'updated_at'        => now(),
            ]
        );

        return redirect()->back()->with('success', 'Status verifikasi transkrip berhasil diperbarui.');
    }

    /**
     * Import Data Mahasiswa Semester Akhir (Membuat Akun di `users`) via Request AJAX
     */
    public function import(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:5048',
        ]);

        try {
            $file = $request->file('file_excel');
            $extension = $file->getClientOriginalExtension();

            $reader = SimpleExcelReader::create($file->getRealPath(), $extension);
            $rows = $reader->getRows();
            $totalRows = count($rows);

            if ($totalRows === 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'File Excel kosong atau tidak dapat dibaca.'
                ], 422);
            }

            $insertedCount = 0;

            foreach ($rows as $row) {
                $cleanRow = [];
                foreach ($row as $key => $value) {
                    $cleanKey = strtolower(trim($key));
                    $cleanRow[$cleanKey] = trim((string)$value);
                }

                $npm = $cleanRow['npm / nim'] ?? $cleanRow['npm'] ?? $cleanRow['nim'] ?? '';
                
                if (empty($npm)) {
                    continue;
                }

                $nama  = $cleanRow['nama mahasiswa'] ?? $cleanRow['nama'] ?? $cleanRow['nama_lengkap'] ?? '';
                $email = (!empty($cleanRow['email'])) ? $cleanRow['email'] : $npm . '@student.polsri.ac.id';
                
                $program = $cleanRow['program'] ?? '';
                $prodi   = '3050'; // Default D3
                if (str_contains(strtolower($program), 'd4') || str_contains($program, '4051')) {
                    $prodi = '4051'; // D4
                }

                DB::table('users')->updateOrInsert(
                    ['username' => (string)$npm],
                    [
                        'email'        => $email,
                        'password'     => Hash::make((string)$npm),
                        'nama_lengkap' => $nama,
                        'level'        => 'mahasiswa',
                        'kode_prodi'   => $prodi,
                    ]
                );

                $insertedCount++;
            }

            return response()->json([
                'status'  => 'success',
                'message' => "Berhasil mengimpor {$insertedCount} data mahasiswa ke tabel users!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengimpor file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Rekapitulasi Data Mahasiswa Semester Akhir & Status Transkrip ke Excel
     */
    public function export(Request $request)
    {
        $query = DB::table('tbkelasmahasiswa as km')
            ->leftJoin('tb_transkrip as t', 'km.npm', '=', 't.npm')
            ->leftJoin('tbprodi as p', 'km.prodi', '=', 'p.kodeProdi')
            ->select(
                'km.npm',
                'km.nama',
                'p.namaProdi',
                'km.prodi',
                'km.semester',
                'km.tahunAkademik',
                't.path_file',
                't.status_verifikasi',
                't.catatan'
            )
            ->where('km.statusKeterangan', 'Lulus')
            // Sembunyikan mahasiswa yang sudah diarsip
            ->whereNotIn('km.npm', function ($sub) {
                $sub->select('npm')->from('arsip_alumni');
            });

        // Batasi data berdasarkan Jurusan Admin / Superadmin & TA Aktif
        $this->applyProdiFilterByRole($query);

        if ($request->filled('prodi')) {
            $query->where('km.prodi', $request->prodi);
        }

        $data = $query->orderBy('km.nama', 'asc')->lazy();

        $fileName = 'Rekap_Transkrip_Mahasiswa_Akhir_' . date('Ymd_His') . '.xlsx';
        $writer = SimpleExcelWriter::streamDownload($fileName);

        foreach ($data as $row) {
            // Cek ketersediaan file fisik di storage
            $hasFile = (!empty($row->path_file) && Storage::disk('public')->exists($row->path_file));

            $writer->addRow([
                'NPM / NIM'         => $row->npm,
                'Nama Mahasiswa'    => $row->nama,
                'Program'           => $row->namaProdi ?? $row->prodi,
                'Semester'          => $row->semester,
                'Tahun Akademik'    => $row->tahunAkademik,
                'Status Upload'     => $hasFile ? 'Sudah Upload' : 'Belum Upload',
                'Status Verifikasi' => $row->status_verifikasi ?? 'Belum Diperiksa',
                'Catatan Admin'     => $row->catatan ?? '-',
            ]);
        }

        return $writer->toBrowser();
    }

  public function viewFile($filename)
    {
        // 1. Susun path lokasi file di dalam folder storage/app/public/
        // Jika file di-upload ke folder 'transkrip', gunakan 'transkrip/' . $filename
        $path = 'transkrip/' . $filename;

        // 2. Jika tidak ketemu di folder 'transkrip', coba cek langsung nama file-nya
        if (!Storage::disk('public')->exists($path)) {
            $path = $filename;
        }

        // 3. Cek apakah file benar-benar ada di storage publik
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Berkas transkrip tidak ditemukan pada server.');
        }

        // 4. Stream/tampilkan file PDF langsung di browser
        return Storage::disk('public')->response($path);
    }
    

    /**
     * Memindahkan berkas (Ijazah & Transkrip) ke Google Drive, 
     * mencatat ke tabel `arsip_alumni`, lalu membersihkan akun `users` milik mahasiswa lulus.
     * KETENTUAN: Hanya memproses mahasiswa jika berkas (Ijazah & Transkrip) sudah LENGKAP dan status Transkrip "Valid".
     */
            public function cleanupUsers()
    {
        try {
            // Ambil Tahun Akademik Aktif dari tbsetting (sebagai fallback jika tahunAkademik kosong)
            $tahunLulus = $this->getTaAktif();

            // Ambil data alumni yang berstatus Lulus DAN transkripnya bernilai Valid oleh Admin
            $alumniLulus = DB::table('tbkelasmahasiswa as km')
                ->join('tb_transkrip as t', DB::raw('km.npm COLLATE utf8mb4_unicode_ci'), '=', DB::raw('t.npm COLLATE utf8mb4_unicode_ci'))
                ->leftJoin('tb_ijazah as i', DB::raw('km.npm COLLATE utf8mb4_unicode_ci'), '=', DB::raw('i.npm COLLATE utf8mb4_unicode_ci'))
                ->select(
                    'km.npm',
                    'km.nama',
                    'km.prodi',
                    'km.tahunAkademik', // Mengambil Tahun Akademik dari mahasiswa
                    't.path_file as path_transkrip',
                    'i.path_file as path_ijazah'
                )
                ->where('km.statusKeterangan', 'Lulus')
                ->where('t.status_verifikasi', 'Valid') // HANYA PROSES YANG VALID
                ->whereNotIn('km.npm', function ($sub) {
                    $sub->select('npm')->from('arsip_alumni'); // ABAIKAN YANG SUDAH DIARSIP
                })
                ->get();

            if ($alumniLulus->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data alumni dengan verifikasi transkrip "Valid" yang siap diproses.');
            }

            $successCount = 0;
            $failedCount  = 0;
            $failedDetail = [];

            foreach ($alumniLulus as $mhs) {
                // Periksa keberadaan fisik kedua file di storage lokal
                $hasTranskrip = !empty($mhs->path_transkrip) && Storage::disk('public')->exists($mhs->path_transkrip);
                $hasIjazah    = !empty($mhs->path_ijazah) && Storage::disk('public')->exists($mhs->path_ijazah);

                // Jika salah satu atau kedua berkas belum ada, batalkan pemindahan dan simpan detail kegagalan
                if (!$hasTranskrip || !$hasIjazah) {
                    $failedCount++;
                    
                    $missing = [];
                    if (!$hasTranskrip) $missing[] = 'Transkrip';
                    if (!$hasIjazah) $missing[] = 'Ijazah';
                    
                    $failedDetail[] = "{$mhs->nama} ({$mhs->npm}) [Belum: " . implode(', ', $missing) . "]";
                    continue;
                }

                // Tentukan Tahun Akademik (misal: 20242)
                $folderTa = !empty($mhs->tahunAkademik) ? trim($mhs->tahunAkademik) : $tahunLulus;

                // 1. Pemindahan Transkrip ke Google Drive berdasarkan Tahun Akademik
                $namaFileTranskrip = basename($mhs->path_transkrip);
                $fileContentTranskrip = Storage::disk('public')->get($mhs->path_transkrip);
                
                // Simpan ke path: Arsip-SIMA PRO/{tahunAkademik}/nama_file
                Storage::disk('google')->put('Arsip-SIMA PRO/' . $folderTa . '/' . $namaFileTranskrip, $fileContentTranskrip);
                Storage::disk('public')->delete($mhs->path_transkrip);
                $transkripGdriveId = $namaFileTranskrip;

                DB::table('tb_transkrip')->where('npm', $mhs->npm)->delete();

                // 2. Pemindahan Ijazah ke Google Drive berdasarkan Tahun Akademik
                $namaFileIjazah = basename($mhs->path_ijazah);
                $fileContentIjazah = Storage::disk('public')->get($mhs->path_ijazah);
                
                // Simpan ke path: Arsip-SIMA PRO/{tahunAkademik}/nama_file
                Storage::disk('google')->put('Arsip-SIMA PRO/' . $folderTa . '/' . $namaFileIjazah, $fileContentIjazah);
                Storage::disk('public')->delete($mhs->path_ijazah);
                $ijazahGdriveId = $namaFileIjazah;

                if (Schema::hasTable('tb_ijazah')) {
                    DB::table('tb_ijazah')->where('npm', $mhs->npm)->delete();
                }

                // 3. Catat ke tabel arsip_alumni (Simpan $folderTa / Tahun Akademik)
                DB::table('arsip_alumni')->updateOrInsert(
                    ['npm' => $mhs->npm],
                    [
                        'nama'                => $mhs->nama,
                        'program_studi'       => $mhs->prodi,
                        'tahun_lulus'         => $folderTa, // <-- DIUBAH DI SINI (Menyimpan 20242)
                        'ijazah_gdrive_id'    => $ijazahGdriveId,
                        'transkrip_gdrive_id' => $transkripGdriveId,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]
                );

                // 4. Hapus Akun User Mahasiswa (Hanya jika berkas lengkap)
                DB::table('users')
                    ->where('level', 'mahasiswa')
                    ->where('username', $mhs->npm)
                    ->delete();

                $successCount++;
            }

            // Susun Pesan Notifikasi Berdasarkan Hasil Proses
            if ($successCount > 0 && $failedCount > 0) {
                $detailText = implode('; ', array_slice($failedDetail, 0, 5));
                if (count($failedDetail) > 5) {
                    $detailText .= '... dan ' . (count($failedDetail) - 5) . ' lainnya.';
                }

                return redirect()->back()->with('warning', "Proses Selesai! Berhasil dipindahkan: {$successCount} data. Gagal/Dilewati: {$failedCount} data karena berkas belum lengkap. Detail: {$detailText}");
            } elseif ($successCount > 0) {
                return redirect()->back()->with('success', "Proses Selesai! Seluruh berkas dari {$successCount} alumni berhasil dipindahkan ke Google Drive dan akun pengguna telah dibersihkan.");
            } else {
                $detailText = implode('; ', array_slice($failedDetail, 0, 5));
                if (count($failedDetail) > 5) {
                    $detailText .= '... dan ' . (count($failedDetail) - 5) . ' lainnya.';
                }

                return redirect()->back()->with('error', "Gagal memproses pemindahan! Sebanyak {$failedCount} alumni tidak dapat dipindahkan karena berkas belum lengkap. Detail: {$detailText}");
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pemindahan berkas: ' . $e->getMessage());
        }
    }
}