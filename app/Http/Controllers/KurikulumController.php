<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Spatie\SimpleExcel\SimpleExcelReader;

class KurikulumController extends Controller
{
    // 1. Tampilkan List Data dengan Fitur Filter & Menyediakan Data untuk Modal
    public function index(Request $request)
    {
        $filterOptions['tahun'] = DB::table('tbkurikulum')
            ->whereNotNull('tahunKurikulum')
            ->where('tahunKurikulum', '!=', '')
            ->distinct()
            ->orderBy('tahunKurikulum', 'desc')
            ->pluck('tahunKurikulum')
            ->map(function ($date) {
                // Jika data di DB tersimpan YYYY-MM-DD, kita ambil tahunnya saja untuk opsi filter UI
                return strlen($date) >= 4 ? substr($date, 0, 4) : $date;
            })
            ->unique()
            ->values();

        $query = DB::table('tbkurikulum');

        if ($request->has('filter_tahun') && $request->filter_tahun != '') {
            // Pencarian filter disesuaikan dengan format DATE (Mencari yang diawali tahun tersebut)
            $query->where('tahunKurikulum', 'like', $request->filter_tahun . '%');
        }

        if ($request->has('filter_semester') && $request->filter_semester != '') {
            $query->where('semester', $request->filter_semester);
        }

        if ($request->has('filter_prodi') && $request->filter_prodi != '') {
            $query->where('prodi', $request->filter_prodi);
        }

        $kurikulum = $query->orderBy('kodeMk', 'asc')->paginate(15);

        $prodi = DB::table('tbprodi')->select('kodeProdi', 'namaProdi')->orderBy('namaProdi', 'asc')->get();
        $jurusan = DB::table('tbjurusan')->select('kodeJurusan', 'namaJurusan')->orderBy('namaJurusan', 'asc')->get();

        return view('superadmin.kurikulum.index', compact('kurikulum', 'prodi', 'jurusan', 'filterOptions'));
    }

    // 2. Proses Simpan Data Baru (Insert Manual via Form)
    public function store(Request $request)
    {
        $request->validate([
            'kodeMk' => 'required|unique:tbkurikulum,kodeMk',
            'namaMk' => 'required',
            'semester' => 'required|numeric',
            'prodi' => 'required',
            'jurusan' => 'required',
            'statusKurikulum' => 'required|in:A,NA',
        ]);

        // Proteksi Opsi A: Cek jika input tahun hanya 4 digit, ubah ke format DATE YYYY-MM-DD
        $tahunInput = $request->tahunKurikulum;
        if (strlen($tahunInput) === 4) {
            $tahunInput = $tahunInput . '-01-01';
        }

        DB::table('tbkurikulum')->insert([
            'kodeMk' => $request->kodeMk,
            'namaMk' => $request->namaMk,
            'namaMkInggris' => $request->namaMkInggris,
            'jenisMk' => $request->jenisMk,
            'sksProdiT' => $request->sksProdiT ?? 0,
            'sksProdiP' => $request->sksProdiP ?? 0,
            'sksLpT' => $request->sksLpT ?? 0,
            'sksLpP' => $request->sksLpP ?? 0,
            'total' => $request->total ?? 0,
            'semester' => $request->semester,
            'jamPerMingguT' => $request->jamPerMingguT ?? 0,
            'jamPerMingguP' => $request->jamPerMingguP ?? 0,
            'totalJamPerMinggu' => $request->totalJamPerMinggu ?? 0,
            'prodi' => $request->prodi,
            'jurusan' => $request->jurusan,
            'tahunKurikulum' => $tahunInput,
            'statusKurikulum' => $request->statusKurikulum,
        ]);

        return redirect()->route('superadmin.kurikulum.index')->with('success', 'Mata kuliah berhasil ditambahkan!');
    }

    // 3. Proses Update Data (Melalui Modal Edit Manual)
    public function update(Request $request, $id)
    {
        $request->validate([
            'kodeMk' => 'required|unique:tbkurikulum,kodeMk,'.$id,
            'namaMk' => 'required',
            'semester' => 'required|numeric',
            'prodi' => 'required',
            'jurusan' => 'required',
            'statusKurikulum' => 'required|in:A,NA',
        ]);

        // Proteksi Opsi A: Cek jika input tahun hanya 4 digit, ubah ke format DATE YYYY-MM-DD
        $tahunInput = $request->tahunKurikulum;
        if (strlen($tahunInput) === 4) {
            $tahunInput = $tahunInput . '-01-01';
        }

        DB::table('tbkurikulum')->where('id', $id)->update([
            'kodeMk' => $request->kodeMk,
            'namaMk' => $request->namaMk,
            'namaMkInggris' => $request->namaMkInggris,
            'jenisMk' => $request->jenisMk,
            'sksProdiT' => $request->sksProdiT ?? 0,
            'sksProdiP' => $request->sksProdiP ?? 0,
            'sksLpT' => $request->sksLpT ?? 0,
            'sksLpP' => $request->sksLpP ?? 0,
            'total' => $request->total ?? 0,
            'semester' => $request->semester,
            'jamPerMingguT' => $request->jamPerMingguT ?? 0,
            'jamPerMingguP' => $request->jamPerMingguP ?? 0,
            'totalJamPerMinggu' => $request->totalJamPerMinggu ?? 0,
            'prodi' => $request->prodi,
            'jurusan' => $request->jurusan,
            'tahunKurikulum' => $tahunInput,
            'statusKurikulum' => $request->statusKurikulum,
        ]);

        return redirect()->route('superadmin.kurikulum.index')->with('success', 'Mata kuliah berhasil diperbarui!');
    }

    // 4. Proses Ubah Status Aktif / Non-Aktif Secara Massal (Bulk Update)
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'numeric',
            'status_action' => 'required|in:A,NA'
        ]);

        DB::table('tbkurikulum')
            ->whereIn('id', $request->ids)
            ->update([
                'statusKurikulum' => $request->status_action
            ]);

        $pesan = $request->status_action === 'A' ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('superadmin.kurikulum.index')->with('success', count($request->ids) . " mata kuliah berhasil {$pesan} secara massal!");
    }

    // 5. Proses Hapus Data
    public function destroy($id)
    {
        DB::table('tbkurikulum')->where('id', $id)->delete();
        return redirect()->route('superadmin.kurikulum.index')->with('success', 'Mata kuliah berhasil dihapus!');
    }

    // 6. Eksport Data ke Excel (Spatie Simple Excel) - Kolom Disederhanakan
    public function exportExcel()
    {
        $columns = [
            'id', 'kodeMk', 'namaMk', 'jenisMk', 
            'sksProdiT', 'sksProdiP', 'sksLpT', 'sksLpP', 
            'jamPerMingguT', 'jamPerMingguP', 'tahunKurikulum'
        ];

        $rows = DB::table('tbkurikulum')
            ->select($columns)
            ->limit(2) 
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();

        $writer = SimpleExcelWriter::streamDownload('data_kurikulum.xlsx');

        if (!empty($rows)) {
            $writer->addRows($rows);
        } else {
            $writer->addRow($columns);
        }

        return $writer->toBrowser();
    }

    // 7. Import Data dari Excel (Dengan Aturan Ekstraksi KodeMK & Opsi A Format DATE)
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file_excel');
        
        $extension = $file->getClientOriginalExtension();
        $reader = SimpleExcelReader::create($file->getPathname(), $extension);

        $insertCount = 0;
        $updateCount = 0;
        $skippedCount = 0;

        $reader->getRows()->each(function (array $row) use (&$insertCount, &$updateCount, &$skippedCount) {
            $kodeMk = trim($row['kodeMk'] ?? '');
            
            // Validasi aturan dasar kodeMk harus tepat 8 karakter
            if (strlen($kodeMk) !== 8 || empty($row['namaMk'])) {
                $skippedCount++;
                return;
            }

            // --- 1. Parsing kodeMk berdasarkan susunan karakter ---
            $charProdi = strtoupper(substr($kodeMk, 0, 2)); // 2 Karakter pertama (AK, AS, AR)
            $charTahun = substr($kodeMk, 2, 2);            // 2 Karakter berikutnya (Tahun)
            $charJenis = substr($kodeMk, 4, 1);            // 1 Karakter berikutnya (Jenis MK)
            $charSemester = (int) substr($kodeMk, 5, 1);       // 1 Karakter berikutnya (Semester)

            // --- 2. Konversi jenisMk ---
            $jenisMkMap = [
                '0' => 'SIKAP',
                '1' => 'PENGETAHUAN',
                '2' => 'KETERAMPILAN UMUM',
                '3' => 'KETERAMPILAN KHUSUS',
            ];
            $jenisMk = $jenisMkMap[$charJenis] ?? 'SIKAP';

            // --- 3. Aturan Pemetaan Khusus / Statis ---
            $staticMap = [
                'AK' => ['prodi' => '3050', 'jurusan' => '62401'],
                'AS' => ['prodi' => '4051', 'jurusan' => '62301'],
                'AR' => ['prodi' => '4055', 'jurusan' => '62301'],
            ];

            $prodiId = null;
            $jurusanId = null;

            if (array_key_exists($charProdi, $staticMap)) {
                $prodiId = $staticMap[$charProdi]['prodi'];
                $jurusanId = $staticMap[$charProdi]['jurusan'];
            } else {
                // Fallback database jika di luar AK/AS/AR
                $prodiData = DB::table('tbprodi')->where('kodeProdi', $charProdi)->first();
                if ($prodiData) {
                    $prodiId = $prodiData->kodeProdi;
                    $jurusanData = DB::table('tbjurusan')->where('kodeJurusan', $prodiData->kodeJurusan)->first();
                    if ($jurusanData) {
                        $jurusanId = $jurusanData->kodeJurusan;
                    }
                }
            }

            // Validasi Relasi Kosong
            if (is_null($prodiId) || is_null($jurusanId)) {
                $skippedCount++;
                return;
            }

            // --- 4. Kalkulasi Nilai Numerik SKS & Jam ---
            $sksProdiT = (int) ($row['sksProdiT'] ?? 0);
            $sksProdiP = (int) ($row['sksProdiP'] ?? 0);
            $sksLpT = (int) ($row['sksLpT'] ?? 0);
            $sksLpP = (int) ($row['sksLpP'] ?? 0);
            $totalSks = $sksProdiT + $sksProdiP + $sksLpT + $sksLpP;

            $jamPerMingguT = (int) ($row['jamPerMingguT'] ?? 0);
            $jamPerMingguP = (int) ($row['jamPerMingguP'] ?? 0);
            $totalJam = $jamPerMingguT + $jamPerMingguP;

            // --- 5. IMPLEMENTASI OPSI A: Konversi tahunKurikulum menjadi format DATE (YYYY-MM-DD) ---
            $tahunKurikulum = '20' . $charTahun . '-01-01'; 

            // --- 6. Satukan Data ke Array ---
            $data = [
                'namaMk' => $row['namaMk'],
                'namaMkInggris' => $row['namaMkInggris'] ?? '', // <-- TAMBAHKAN BARIS INI
                'jenisMk' => $jenisMk,
                'sksProdiT' => $sksProdiT,
                'sksProdiP' => $sksProdiP,
                'sksLpT' => $sksLpT,
                'sksLpP' => $sksLpP,
                'total' => $totalSks,
                'semester' => $charSemester,
                'jamPerMingguT' => $jamPerMingguT,
                'jamPerMingguP' => $jamPerMingguP,
                'totalJamPerMinggu' => $totalJam,
                'prodi' => $prodiId,
                'jurusan' => $jurusanId,
                'tahunKurikulum' => $tahunKurikulum,
                'statusKurikulum' => 'A',
            ];

            // --- 7. Simpan atau Perbarui Data (Upsert) ---
            $exists = DB::table('tbkurikulum')->where('kodeMk', $kodeMk)->exists();

            if ($exists) {
                DB::table('tbkurikulum')->where('kodeMk', $kodeMk)->update($data);
                $updateCount++;
            } else {
                $data['kodeMk'] = $kodeMk;
                DB::table('tbkurikulum')->insert($data);
                $insertCount++;
            }
        });

        $reader->close();

        // Rekomendasi: Clear cache setelah operasi database massal selesai
        try {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
        } catch (\Exception $e) {
            // Abaikan jika Termux melarang eksekusi artisan via web
        }

        $pesanInfo = "Import selesai! {$insertCount} data baru ditambahkan, {$updateCount} data diperbarui.";
        if ($skippedCount > 0) {
            $pesanInfo .= " ({$skippedCount} baris dilewati karena format tidak valid atau prodi tidak terdaftar).";
        }

        return redirect()->route('superadmin.kurikulum.index')->with('success', $pesanInfo);
    }
}