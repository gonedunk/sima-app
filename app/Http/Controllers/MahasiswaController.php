<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\MahasiswaExport;
use Spatie\SimpleExcel\SimpleExcelReader;
use Barryvdh\DomPDF\Facade\Pdf;

class MahasiswaController extends Controller 
{
    /**
     * HELPER: Mengambil daftar kodeProdi yang dinaungi oleh jurusan Admin yang sedang login
     */
    private function getAdminProdiList(): array
    {
        $userId = auth()->user()->id;

        $adminContext = DB::table('users')
            ->join('tbprodi', 'users.kode_prodi', '=', 'tbprodi.kodeProdi')
            ->join('tbjurusan', 'tbprodi.kodeJurusan', '=', 'tbjurusan.kodeJurusan')
            ->where('users.id', $userId)
            ->select('tbprodi.kodeJurusan')
            ->first();

        $kodeJurusanAdmin = $adminContext ? $adminContext->kodeJurusan : '62301';

        return DB::table('tbprodi')
            ->where('kodeJurusan', $kodeJurusanAdmin)
            ->pluck('kodeProdi')
            ->toArray();
    }

    /* =========================================================================
     *  1. MASTER MAHASISWA (Pendaftaran & Biodata)
     * ========================================================================= */

    public function mahasiswaIndex(Request $request)
    {
        $role = auth()->user()->level;

        // Dropdown Prodi Filter
        $prodisQuery = DB::table('tbprodi')->select('kodeProdi', 'namaProdi');
        if ($role === 'admin') {
            $prodisQuery->whereIn('kodeProdi', $this->getAdminProdiList());
        }
        $prodis = $prodisQuery->get();

        $tahunAkademiks = DB::table('tbmahasiswa')
            ->select('tahunAkademik')
            ->distinct()
            ->orderBy('tahunAkademik', 'desc')
            ->get();

        $query = DB::table('tbmahasiswa')
            ->leftJoin('tbprogram', 'tbmahasiswa.program', '=', 'tbprogram.kodeProgram')
            ->leftJoin('tbprodi', 'tbmahasiswa.kodeProdi', '=', 'tbprodi.kodeProdi')
            ->leftJoin('tbagama', 'tbmahasiswa.agama', '=', 'tbagama.kodeAgama')
            ->leftJoin('tbjalur', 'tbmahasiswa.jalur', '=', 'tbjalur.kodeJalur')
            ->select('tbmahasiswa.*', 'tbprogram.namaProgram', 'tbprodi.namaProdi', 'tbagama.namaAgama', 'tbjalur.namaJalur');

        // Batasi data jika Login sebagai Admin biasa
        if ($role === 'admin') {
            $query->whereIn('tbmahasiswa.kodeProdi', $this->getAdminProdiList());
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('tbmahasiswa.npm', 'like', "%{$request->search}%")
                  ->orWhere('tbmahasiswa.nama', 'like', "%{$request->search}%");
            });
        }
        if ($request->filled('prodi')) { $query->where('tbmahasiswa.kodeProdi', $request->prodi); }
        if ($request->filled('ta')) { $query->where('tbmahasiswa.tahunAkademik', $request->ta); }

        $mahasiswas = $query->orderBy('tbmahasiswa.npm', 'asc')->paginate(25);
        
        $settingAktif = DB::table('tbsetting')->first();
        $allPrograms  = DB::table('tbprogram')->get();
        $allAgamas    = DB::table('tbagama')->get();
        $allJalurs    = DB::table('tbjalur')->get();

        return view('admin.mahasiswa.index', compact(
            'mahasiswas', 'prodis', 'tahunAkademiks', 'settingAktif', 
            'allPrograms', 'allAgamas', 'allJalurs'
        ));
    }

    public function mahasiswaStore(Request $request)
    {
        $request->validate([
            'npm'       => 'required|unique:tbmahasiswa,npm', 
            'nama'      => 'required', 
            'kodeProdi' => 'required'
        ]);

        $prodiInfo = DB::table('tbprodi')->where('kodeProdi', $request->kodeProdi)->first();
        $kodeJurusan = $prodiInfo ? $prodiInfo->kodeJurusan : '62301';

        DB::table('tbmahasiswa')->insert([
            'noRegistrasi' => $request->noRegistrasi ?? '', 
            'npm'          => $request->npm, 
            'nama'         => $request->nama, 
            'kelas'        => $request->kelas ?? '',
            'kodeProdi'    => $request->kodeProdi, 
            'program'      => $request->program ?? '', 
            'jalur'        => $request->jalur ?? '', 
            'email'        => $request->email ?? '',
            'telpon'       => $request->telpon ?? '', 
            'hp'           => $request->hp ?? '', 
            'agama'        => $request->agama ?? '', 
            'kip'          => $request->kip ?? 'Tidak',
            'jenisKelamin' => $request->jenisKelamin ?? 'L', 
            'kodeJurusan'  => $kodeJurusan, 
            'keterangan'   => 'A', 
            'tahunAkademik' => $request->tahunAkademik ?? date('Y')
        ]);

        return redirect()->route('mahasiswa.index')->with('success', 'Data mahasiswa berhasil ditambahkan!');
    }

    public function mahasiswaUpdate(Request $request, $id)
    {
        $request->validate([
            'npm'       => 'required|unique:tbmahasiswa,npm,'.$id, 
            'nama'      => 'required', 
            'kodeProdi' => 'required'
        ]);

        $prodiInfo = DB::table('tbprodi')->where('kodeProdi', $request->kodeProdi)->first();
        $kodeJurusan = $prodiInfo ? $prodiInfo->kodeJurusan : '62301';

        DB::table('tbmahasiswa')->where('id', $id)->update([
            'noRegistrasi' => $request->noRegistrasi ?? '', 
            'npm'          => $request->npm, 
            'nama'         => $request->nama, 
            'kelas'        => $request->kelas ?? '',
            'kodeProdi'    => $request->kodeProdi, 
            'program'      => $request->program ?? '', 
            'jalur'        => $request->jalur ?? '', 
            'email'        => $request->email ?? '',
            'telpon'       => $request->telpon ?? '', 
            'hp'           => $request->hp ?? '', 
            'agama'        => $request->agama ?? '', 
            'kip'          => $request->kip ?? 'Tidak',
            'jenisKelamin' => $request->jenisKelamin ?? 'L', 
            'kodeJurusan'  => $kodeJurusan, 
            'keterangan'   => 'A', 
            'tahunAkademik' => $request->tahunAkademik ?? date('Y')
        ]);

        return redirect()->route('mahasiswa.index')->with('success', 'Perubahan biodata mahasiswa berhasil disimpan!');
    }

    public function mahasiswaDestroy($id)
    {
        DB::table('tbmahasiswa')->where('id', $id)->delete();
        return redirect()->route('mahasiswa.index')->with('success', 'Data mahasiswa berhasil dihapus!');
    }


    /* =========================================================================
     *  2. PLOTTING KELAS MAHASISWA
     * ========================================================================= */

    public function kelasMhsIndex(Request $request)
    {
        $role = auth()->user()->level;

        $prodisQuery = DB::table('tbprodi')->select('kodeProdi', 'namaProdi');
        if ($role === 'admin') {
            $prodisQuery->whereIn('kodeProdi', $this->getAdminProdiList());
        }
        $prodis = $prodisQuery->get();

        $tahunAkademiks = DB::table('tbtahunakademik')
            ->select('id', 'tahunAkademik', 'semesterAkademik')
            ->orderBy('tahunAkademik', 'desc')
            ->orderBy('semesterAkademik', 'desc')
            ->get();

        $setting = DB::table('tbsetting')->first();
        $taAktif = $request->get('ta', $setting->ta_aktif ?? '');

        // QUERY 1: Mahasiswa Aktif & Non-Aktif di TA Aktif
        $query = DB::table('tbkelasmahasiswa')->whereIn('keterangan', ['A', 'NA']);

        if ($role === 'admin') {
            $query->whereIn('prodi', $this->getAdminProdiList());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('npm', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('kelas', 'like', "%{$search}%");
            });
        }
        if ($request->filled('prodi')) { $query->where('prodi', $request->prodi); }
        if ($taAktif) { $query->where('tahunAkademik', $taAktif); }

        $kelasMahasiswas = $query->orderBy('kelas', 'asc')
            ->orderBy('npm', 'asc')
            ->paginate(25);

        // QUERY 2: Mahasiswa Kategori Khusus (DO / Undur Diri / SO)
        $queryNa = DB::table('tbkelasmahasiswa');

        if ($role === 'admin') {
            $queryNa->whereIn('prodi', $this->getAdminProdiList());
        }

        if ($taAktif) {
            $queryNa->where(function($q) use ($taAktif) {
                $q->where(function($subQ) use ($taAktif) {
                    $subQ->where('keterangan', 'NA')
                         ->where('tahunAkademik', $taAktif)
                         ->whereIn('statusKeterangan', ['DO', 'Undur Diri', 'Mengulang 1 Tahun', 'Menunggu Ujian']);
                });

                $taSatuTahunLalu = (int)$taAktif - 10;
                $q->orWhere(function($subQ) use ($taSatuTahunLalu) {
                    $subQ->where('keterangan', 'NA')
                         ->where('statusKeterangan', 'SO')
                         ->where('tahunAkademik', $taSatuTahunLalu);
                });
            });
        } else {
            $queryNa->whereRaw('1 = 0');
        }

        $mahasiswaNonAktif = $queryNa->orderBy('tahunAkademik', 'desc')
            ->orderBy('npm', 'asc')
            ->get();

        return view('admin.kelas.index', compact(
            'kelasMahasiswas', 'prodis', 'tahunAkademiks', 'setting', 'taAktif', 'mahasiswaNonAktif'
        ));
    }

    public function kelasMhsSync()
    {
        try {
            $role = auth()->user()->level;
            $queryMhs = DB::table('tbmahasiswa')->where('keterangan', 'A');

            if ($role === 'admin') {
                $queryMhs->whereIn('kodeProdi', $this->getAdminProdiList());
            }

            $allMahasiswa = $queryMhs->get();
            $existingNpms = DB::table('tbkelasmahasiswa')->pluck('npm')->toArray();
            $insertedCount = 0;

            foreach ($allMahasiswa as $mhs) {
                if (!in_array($mhs->npm, $existingNpms)) {
                    $prodiInfo = DB::table('tbprodi')->where('kodeProdi', $mhs->kodeProdi)->first();
                    $singkatanJurusan = $prodiInfo ? str_replace('62', '', $prodiInfo->kodeJurusan) : 'AK';

                    DB::table('tbkelasmahasiswa')->insert([
                        'npm'              => $mhs->npm, 
                        'nama'             => $mhs->nama, 
                        'kelas'            => $mhs->kelas ?? '1 AA', 
                        'semester'         => 1,
                        'prodi'            => $mhs->kodeProdi ?? '', 
                        'jurusan'          => $singkatanJurusan, 
                        'keterangan'       => 'A', 
                        'statusKeterangan' => 'Mahasiswa Baru',
                        'tahunAkademik'    => $mhs->tahunAkademik ?? date('Y'), 
                        'tahunMasuk'       => date('Y'),
                    ]);
                    $insertedCount++;
                }
            }

            return redirect()->route('kelas-mahasiswa.index')
                ->with('success', "Sinkronisasi selesai! {$insertedCount} data baru berhasil ditambahkan.");
        } catch (\Exception $e) {
            return redirect()->route('kelas-mahasiswa.index')
                ->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    public function kelasMhsUpdate(Request $request, $id)
    {
        $request->validate(['kelas' => 'required', 'semester' => 'required', 'tahunAkademik' => 'required']);

        $prodiInfo = DB::table('tbprodi')->where('kodeProdi', $request->prodi)->first();
        $singkatanJurusan = $prodiInfo ? str_replace('62', '', $prodiInfo->kodeJurusan) : 'AK';

        DB::table('tbkelasmahasiswa')->where('id', $id)->update([
            'kelas'            => $request->kelas, 
            'semester'         => $request->semester, 
            'tahunAkademik'    => $request->tahunAkademik,
            'keterangan'       => $request->keterangan ?? 'A', 
            'statusKeterangan' => $request->keterangan == 'A' ? 'Aktif' : $request->statusKeterangan,
            'npm'              => $request->npm, 
            'nama'             => $request->nama, 
            'prodi'            => $request->prodi ?? '', 
            'jurusan'          => $singkatanJurusan, 
            'tahunMasuk'       => $request->tahunMasuk ?? date('Y')
        ]);

        return redirect()->route('kelas-mahasiswa.index')->with('success', 'Data plotting kelas berhasil diperbarui!');
    }

    public function kelasMhsDestroy($id)
    {
        DB::table('tbkelasmahasiswa')->where('id', $id)->delete();
        return redirect()->route('kelas-mahasiswa.index')->with('success', 'Data plotting kelas berhasil dihapus!');
    }

    public function kelasMhsPromosiMassal(Request $request)
    {
        $request->validate([
            'ids'               => 'required|array', 
            'tahunAkademikBaru' => 'required|string'
        ]);

        $role = auth()->user()->level;
        $ids = $request->ids;
        $tahunBaru = $request->tahunAkademikBaru;
        $suksesCount = 0;
        $skipCount = 0;

        foreach ($ids as $id) {
            $queryMhs = DB::table('tbkelasmahasiswa')->where('id', $id)->where('keterangan', 'A');
            
            // Validasi kepemilikan data untuk Admin
            if ($role === 'admin') {
                $queryMhs->whereIn('prodi', $this->getAdminProdiList());
            }

            $mhsKelas = $queryMhs->first();

            if ($mhsKelas) {
                $isExist = DB::table('tbkelasmahasiswa')
                    ->where('npm', $mhsKelas->npm)
                    ->where('tahunAkademik', $tahunBaru)
                    ->exists();

                if ($isExist) {
                    $skipCount++;
                    continue;
                }

                $semesterBaru = (int)$mhsKelas->semester + 1;
                $kelasLama = $mhsKelas->kelas;
                $kelasBaru = preg_replace_callback('/\d+/', fn($matches) => (int)$matches[0] + 1, $kelasLama);

                DB::table('tbkelasmahasiswa')->insert([
                    'npm'              => $mhsKelas->npm,
                    'nama'             => $mhsKelas->nama,
                    'kelas'            => $kelasBaru,
                    'semester'         => $semesterBaru,
                    'prodi'            => $mhsKelas->prodi,
                    'jurusan'          => $mhsKelas->jurusan ?? 'AK',
                    'keterangan'       => 'A',
                    'statusKeterangan' => 'Aktif',
                    'tahunAkademik'    => $tahunBaru,
                    'tahunMasuk'       => $mhsKelas->tahunMasuk ?? date('Y')
                ]);

                $suksesCount++;
            }
        }

        $pesan = "Kenaikan kelas massal berhasil disimpan sebagai data baru untuk {$suksesCount} mahasiswa.";
        if ($skipCount > 0) {
            $pesan .= " ({$skipCount} mahasiswa dilewati karena sudah terdaftar di TA {$tahunBaru}).";
        }

        return redirect()->back()->with('success', $pesan);
    }

    public function lulusMassal(Request $request)
    {
        $request->validate([
            'statusKeterangan' => 'required|string',
        ]);

        $ids = $request->input('ids', []);
        $statusKeterangan = $request->input('statusKeterangan');

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada mahasiswa yang dipilih.');
        }

        try {
            DB::table('tbkelasmahasiswa')
                ->whereIn('id', $ids)
                ->update([
                    'keterangan' => 'NA',
                    'statusKeterangan' => $statusKeterangan
                ]);

            return redirect()->back()->with('success', count($ids) . " mahasiswa berhasil diubah statusnya menjadi Non-Aktif ({$statusKeterangan}).");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses data: ' . $e->getMessage());
        }
    }


    /* =========================================================================
     *  3. EXPORT / IMPORT EXCEL & CETAK PDF
     * ========================================================================= */

    public function mahasiswaExport() 
    { 
        return MahasiswaExport::downloadTemplate(); 
    }

    public function mahasiswaImport(Request $request)
    {
        $request->validate(['file_excel' => 'required|mimes:xlsx,xls,csv|max:4096']);

        try {
            $filePath = $request->file('file_excel')->getRealPath();
            $reader = SimpleExcelReader::create($filePath, 'xlsx');

            $prodiMap = DB::table('tbprodi')->pluck('kodeJurusan', 'kodeProdi')->toArray();
            $batchData = [];
            $chunkSize = 500;

            DB::transaction(function () use ($reader, $prodiMap, &$batchData, $chunkSize) {
                $reader->getRows()->each(function (array $row) use ($prodiMap, &$batchData, $chunkSize) {
                    $npm = trim($row['NPM'] ?? '');
                    $nama = $row['Nama Lengkap'] ?? null;

                    if (!empty($npm) && !empty($nama)) {
                        $programInput = strtolower(trim($row['Program'] ?? ''));
                        $kodeProgram = match ($programInput) {
                            'pagi', 'reguler' => '01',
                            'sore'            => '02',
                            'malam'           => '03',
                            default           => '01',
                        };

                        $prodiInput = $row['Prodi'] ?? '';
                        $kodeJurusan = $prodiMap[$prodiInput] ?? '62401';

                        $noHp = $row['No HP'] ?? null;
                        $noTelp = $row['No Telepon'] ?? $row['Telpon'] ?? $noHp ?? '';

                        $batchData[] = [
                            'npm'           => $npm,
                            'noRegistrasi'  => $row['No Registrasi'] ?? null,
                            'nama'          => $nama,
                            'jenisKelamin'  => strtoupper(trim($row['Jenis Kelamin (L/P)'] ?? 'L')),
                            'kodeProdi'     => $prodiInput,
                            'program'       => $kodeProgram,
                            'kelas'         => $row['Kelas'] ?? '',
                            'tahunAkademik' => $row['Tahun Akademik'] ?? date('Y'),
                            'jalur'         => $row['Jalur Masuk'] ?? '',
                            'agama'         => $row['Agama'] ?? '',
                            'kip'           => trim($row['Penerima KIP (Ya/Tidak)'] ?? 'Tidak'),
                            'hp'            => $noHp,
                            'telpon'        => $noTelp,
                            'email'         => $row['Email'] ?? null,
                            'kodeJurusan'   => $kodeJurusan,
                            'keterangan'    => $row['Keterangan'] ?? 'A',
                        ];

                        if (count($batchData) >= $chunkSize) {
                            $this->processUpsert($batchData);
                            $batchData = [];
                        }
                    }
                });

                if (!empty($batchData)) {
                    $this->processUpsert($batchData);
                }
            });

            return redirect()->back()->with('success', 'Mass-import data mahasiswa baru berhasil diproses!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

  public function cetakSerahTerimaId($id)
{
    $mahasiswa = \DB::table('tbkelasmahasiswa as km')
        ->leftJoin('tbprodi as p', 'km.prodi', '=', 'p.kodeProdi')
        ->leftJoin('tbjurusan as j', 'p.kodeJurusan', '=', 'j.kodeJurusan')
        ->select(
            'km.*',
            'p.namaProdi',
            'j.namaJurusan'
        )
        ->where('km.id', $id)
        ->first();

    if (!$mahasiswa) {
        return redirect()->back()->with('error', 'Data mahasiswa tidak ditemukan.');
    }

    // Set ukuran kertas A4 Portrait
    $pdf = Pdf::loadView('pdf.buktiserahterimaijazah', compact('mahasiswa'))
              ->setPaper('a4', 'portrait');

    return $pdf->stream('Bukti_Serah_Terima_Ijazah_' . $mahasiswa->npm . '.pdf');
}
  
}