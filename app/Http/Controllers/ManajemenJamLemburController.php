<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ManajemenJamLemburController extends Controller
{
    /**
     * Menampilkan daftar rekap lembur (Support Filter KJP2: NK & KS saja)
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $jenisJam = $request->input('jenis_jam', 'normal'); // Default: 'normal'

        $dataLembur = DB::table('tbrekaplembur')
            ->leftJoin('tbdosen', function($join) {
                $join->on(
                    DB::raw('TRIM(tbrekaplembur.nip)'), 
                    '=', 
                    DB::raw('TRIM(tbdosen.nip) COLLATE utf8mb4_general_ci')
                );
            })
            ->select('tbrekaplembur.*', 'tbdosen.nama as nama_dosen')
            
            // -------------------------------------------------------------
            // FILTER KHUSUS JAM KJP2 (Hanya Aturan NK & KS)
            // -------------------------------------------------------------
            ->when($jenisJam === 'kjp2', function($query) {
                return $query->whereIn('tbrekaplembur.aturan', ['NK', 'KS']);
            })
            
            // -------------------------------------------------------------
            // FILTER PENCARIAN (SEARCH)
            // -------------------------------------------------------------
            ->when($search, function($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('tbdosen.nama', 'LIKE', "%{$search}%")
                      ->orWhere('tbrekaplembur.nip', 'LIKE', "%{$search}%")
                      ->orWhere('tbrekaplembur.keterangan', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('tbrekaplembur.nip', 'asc')     
            ->orderBy('tbrekaplembur.tanggal', 'desc') 
            ->paginate(15) 
            ->withQueryString(); 

        // -----------------------------------------------------------------
        // PENANGANAN JAM LEMBUR KOSONG -> DEFAULT '00:00'
        // -----------------------------------------------------------------
        $dataLembur->getCollection()->transform(function ($item) {
            if (empty($item->jamLembur) || $item->jamLembur === '00:00:00') {
                $item->jamLembur = '00:00';
            } else {
                $item->jamLembur = substr($item->jamLembur, 0, 5);
            }
            return $item;
        });

        $daftarDosen = DB::table('tbdosen')
            ->where('level', '02')
            ->select('nip', 'nama')
            ->get();

        $daftarJamKerjaWajib = DB::table('tbjamkerjawajib')
            ->select('id', 'jamDatangWajib', 'jamPulangWajib', 'jamLemburMaks', 'kodeBulan')
            ->get();

        return view('admin.lembur.index', compact(
            'dataLembur', 
            'daftarDosen', 
            'daftarJamKerjaWajib', 
            'search',
            'jenisJam'
        ));
    }

    /**
     * ARSIP REKAP LEMBUR KJP2 KE HISTORY (Hanya NK & KS dalam Rentang Tanggal)
     * Mengarsipkan data dari Tabel 1 (tbrekaplembur) yang dicentang ke tbrekaplemburhistory
     */
    public function arsipKjp2ToHistory(Request $request)
    {
        // 1. Validasi input rentang tanggal & checkbox ID terpilih
        $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'selected_ids'    => 'required|array|min:1',
            'selected_ids.*'  => 'integer',
        ], [
            'tanggal_mulai.required'         => 'Tanggal mulai rentang wajib diisi!',
            'tanggal_selesai.required'       => 'Tanggal selesai rentang wajib diisi!',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai!',
            'selected_ids.required'          => 'Pilih minimal satu data KJP2 (checkbox) yang ingin diarsipkan!',
        ]);

        $selectedIds    = $request->input('selected_ids', []);
        $tanggalMulai   = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        // 2. Ambil data dari tbrekaplembur yang DICENTANG DAN Sesuai Rentang Tanggal
        $dataKjp2 = DB::table('tbrekaplembur')
            ->whereIn('id', $selectedIds)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->whereIn('aturan', ['NK', 'KS']) // Pastikan hanya NK dan KS
            ->get();

        if ($dataKjp2->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data KJP2 (NK/KS) tercentang pada rentang tanggal tersebut.');
        }

        // 3. Ambil data master pendukung
        $masterShift = DB::table('tbjamkerjawajib')->get();
        
        $tanggalLiburArray = DB::table('tbtglliburlembur')
            ->pluck('tanggal')
            ->map(fn($tgl) => Carbon::parse($tgl)->toDateString())
            ->toArray();

        DB::beginTransaction();

        try {
            $berhasilArsipCount = 0;

            foreach ($dataKjp2 as $row) {
                if (empty($row->tanggal) || empty($row->jamDatang) || empty($row->jamPulang)) {
                    continue;
                }

                $tanggalCarbon = Carbon::parse($row->tanggal);
                $dayIndex      = $tanggalCarbon->dayOfWeek; // 0 = Minggu, 6 = Sabtu

                // Hari Minggu (0) dilewati dari rekap KJP2
                if ($dayIndex === 0) {
                    continue;
                }

                // Determinasi Kode Shift Khusus KJP2
                $targetKode = ($dayIndex === 6) ? 'KS' : 'NK';

                // Ambil master shift khusus NK atau KS saja
                $shift = $masterShift->firstWhere('kodeBulan', $targetKode);

                if (!$shift) {
                    continue; 
                }

                $isLiburLembur = in_array($row->tanggal, $tanggalLiburArray);

                if ($isLiburLembur) {
                    $keteranganFinal   = 'bukan hari lembur/wfa';
                    $jamLemburFinal    = '00:00';
                    $jamKerjaFormated  = substr($shift->jamPulangWajib, 0, 5);
                    $jamPulangFormated = substr($row->jamPulang, 0, 5);
                } else {
                    $kalkulasi = $this->hitungKalkulasiLembur(
                        $row->jamDatang,
                        $shift->jamDatangWajib,
                        $shift->jamPulangWajib,
                        $row->jamPulang,
                        $shift->jamLemburMaks
                    );

                    $jamLemburFinal    = $kalkulasi['jamLembur'] ?: '00:00';
                    $jamKerjaFormated  = $kalkulasi['jamKerja'];
                    $jamPulangFormated = $kalkulasi['jamPulang'];
                    $keteranganFinal   = 'Arsip KJP2 (' . $targetKode . ')';
                }

                // SIMPAN / UPDATE KE TABEL tbrekaplemburhistory
                DB::table('tbrekaplemburhistory')->updateOrInsert(
                    [
                        'nip'     => $row->nip,
                        'tanggal' => $row->tanggal,
                        'aturan'  => $targetKode,
                    ],
                    [
                        'jamDatang'      => substr($row->jamDatang, 0, 5),
                        'jamWajibDatang' => substr($shift->jamDatangWajib, 0, 5),
                        'jamKerja'       => $jamKerjaFormated,
                        'jamPulang'      => $jamPulangFormated,
                        'jamLembur'      => $jamLemburFinal,
                        'aturan'         => $targetKode,
                        'keterangan'     => $keteranganFinal,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]
                );

                $berhasilArsipCount++;
            }

            DB::commit();

            return redirect()->back()->with('success', "Berhasil mengarsipkan {$berhasilArsipCount} data KJP2 (NK/KS) ke history untuk periode {$tanggalMulai} s.d {$tanggalSelesai}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengarsipkan data KJP2: ' . $e->getMessage());
        }
    }

    /**
     * EXPORT TEMPLATE EXCEL
     */
    public function exportTemplate()
    {
        $fileName = 'Template_Import_Jam_Lembur.xlsx';

        return SimpleExcelWriter::streamDownload($fileName)
            ->addRow([
                'tanggal'  => '2026-07-10',
                'nip'      => '199012312020011002',
                'jam_riil' => '19:30', 
            ])
            ->toBrowser();
    }

    /**
     * IMPORT EXCEL
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls|max:5120',
        ]);

        $file = $request->file('excel_file');
        $filePath = $file->getRealPath();

        $reader = SimpleExcelReader::create($filePath, 'xlsx');
        $headers = $reader->getHeaders();

        if (!in_array('tanggal', $headers) || !in_array('nip', $headers) || !in_array('jam_riil', $headers)) {
            return redirect()->back()->with('error', 'Struktur template salah! Kolom harus: tanggal, nip, jam_riil.');
        }

        $rows = $reader->getRows();
        $suksesCount = 0;
        $groupedData = [];

        foreach ($rows as $row) {
            if (($row['tanggal'] === '2026-07-10' && $row['nip'] === '199012312020011002') || $row['tanggal'] === 'tanggal') {
                continue;
            }

            if (empty($row['tanggal']) || empty($row['nip'])) {
                continue;
            }

            $tanggalRaw = $row['tanggal'];
            if ($tanggalRaw instanceof \DateTimeInterface) {
                $tanggalCarbon = Carbon::instance($tanggalRaw);
            } elseif (is_numeric($tanggalRaw)) {
                $tanggalCarbon = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalRaw));
            } else {
                $tanggalCarbon = Carbon::parse(str_replace('/', '-', (string)$tanggalRaw));
            }
            $tanggal = $tanggalCarbon->toDateString(); 
            
            $nip = trim((string)$row['nip']);

            $jamRiilRaw = $row['jam_riil'] ?? null;
            $jamRiil = null;

            if ($jamRiilRaw instanceof \DateTimeInterface) {
                $jamRiil = $jamRiilRaw->format('H:i');
            } elseif ($jamRiilRaw !== null && $jamRiilRaw !== '') {
                $valStr = trim((string)$jamRiilRaw);
                if ($valStr !== '' && $valStr !== '00:00:00' && $valStr !== '00:00') {
                    if (preg_match('/^\d{1,2}:\d{2}/', $valStr)) {
                        $jamRiil = Carbon::createFromFormat('H:i', substr($valStr, 0, 5))->format('H:i');
                    }
                }
            }

            if (!empty($jamRiil)) {
                $groupedData[$nip][$tanggal][] = $jamRiil;
            } else {
                if (!isset($groupedData[$nip][$tanggal])) {
                    $groupedData[$nip][$tanggal] = [];
                }
            }
        }

        $masterShift = DB::table('tbjamkerjawajib')->get();
        
        $tanggalLiburArray = DB::table('tbtglliburlembur')
            ->pluck('tanggal')
            ->map(fn($tgl) => Carbon::parse($tgl)->toDateString())
            ->toArray();

        $masterBulanPuasa = DB::table('tbbulanpuasa')->get()->map(function($p) {
            return [
                'dari'   => Carbon::parse(trim((string)$p->dariTanggal))->startOfDay(),
                'sampai' => Carbon::parse(trim((string)$p->sampaiTanggal))->endOfDay(),
            ];
        });

        DB::beginTransaction();

        try {
            foreach ($groupedData as $nip => $tanggalGroup) {
                foreach ($tanggalGroup as $tanggal => $jamList) {
                    
                    $tanggalCarbon = Carbon::parse($tanggal);
                    $dayIndex = $tanggalCarbon->dayOfWeek; // 0 = Minggu, 6 = Sabtu

                    if ($dayIndex === 0) {
                        continue; 
                    }

                    $validTimes = array_values(array_unique(array_filter($jamList)));
                    $totalValid = count($validTimes);

                    $jamDatangInput = null;
                    $jamPulangInput = null;
                    $keterangan = 'Import via Excel';
                    $jamLemburFinal = '00:00';
                    $jamKerjaFormated = '00:00';
                    $jamPulangFormated = '00:00';

                    $isBulanPuasa = $masterBulanPuasa->contains(function($range) use ($tanggalCarbon) {
                        return $tanggalCarbon->between($range['dari'], $range['sampai']);
                    });

                    $isLiburLembur = in_array($tanggal, $tanggalLiburArray);

                    // KONDISI A: TIDAK ADA JAM RIIL TERISI
                    if ($totalValid === 0) {
                        $jamDatangInput = '07:30';
                        $jamPulangInput = '16:00';
                        $keterangan = $isBulanPuasa ? 'Lupa Absen (Kosong Total - Bulan Puasa)' : 'Lupa Absen (Data Kosong Total)';
                        
                        $targetKode = $isBulanPuasa 
                            ? (($dayIndex >= 1 && $dayIndex <= 4) ? 'R' : (($dayIndex === 5) ? 'RJ' : 'RS'))
                            : (($dayIndex === 5) ? 'NJ' : (($dayIndex === 6) ? 'KS' : 'N'));

                        $shift = $masterShift->firstWhere('kodeBulan', $targetKode) ?? $masterShift->first();
                        $kodeBulan = $shift->kodeBulan;
                        $jamDatangWajibAsli = $shift->jamDatangWajib;
                        $jamKerjaFormated = '00:00';
                        $jamPulangFormated = '16:00';

                    // KONDISI B: HANYA 1 JAM RIIL
                    } else if ($totalValid === 1) {
                        $jamSingle = $validTimes[0];
                        $jamDatangInput = $jamSingle; 
                        $jamPulangInput = $jamSingle; 

                        $isPagi = Carbon::createFromFormat('H:i', $jamSingle)->between('00:01', '12:00');
                        $keterangan = $isPagi 
                            ? ($isBulanPuasa ? 'Lupa Absen Pulang (Bulan Puasa)' : 'Lupa Absen Pulang')
                            : ($isBulanPuasa ? 'Lupa Absen Datang (Bulan Puasa)' : 'Lupa Absen Datang');

                        $targetKode = $isBulanPuasa 
                            ? (($dayIndex >= 1 && $dayIndex <= 4) ? 'R' : (($dayIndex === 5) ? 'RJ' : 'RS'))
                            : (($dayIndex === 5) ? 'NJ' : (($dayIndex === 6) ? 'KS' : 'N'));

                        $shift = $masterShift->firstWhere('kodeBulan', $targetKode) ?? $masterShift->first();
                        
                        $kodeBulan          = $shift->kodeBulan;             
                        $jamDatangWajibAsli = $shift->jamDatangWajib; 
                        $jamKerjaFormated   = substr($shift->jamPulangWajib, 0, 5);  
                        $jamPulangFormated  = $jamSingle;
                        $jamLemburFinal     = '00:00';

                    // KONDISI C: ADA 2 ATAU LEBIH JAM RIIL VALID
                    } else {
                        $kumpulanTimeObj = collect($validTimes)->map(fn($j) => Carbon::createFromFormat('H:i', $j));
                        
                        $kumpulanJamDatang = [];
                        $kumpulanJamPulang = [];

                        foreach ($validTimes as $jam) {
                            $timeObj = Carbon::createFromFormat('H:i', $jam);
                            if ($timeObj->between('00:01', '12:00')) {
                                $kumpulanJamDatang[] = $timeObj;
                            }
                            if ($timeObj->between('12:01', '23:59')) {
                                $kumpulanJamPulang[] = $timeObj;
                            }
                        }

                        $jamDatangInput = !empty($kumpulanJamDatang) 
                            ? collect($kumpulanJamDatang)->min()->format('H:i') 
                            : $kumpulanTimeObj->min()->format('H:i');

                        $jamPulangInput = !empty($kumpulanJamPulang) 
                            ? collect($kumpulanJamPulang)->max()->format('H:i') 
                            : $kumpulanTimeObj->max()->format('H:i');

                        if ($isBulanPuasa) {
                            $targetKode = ($dayIndex >= 1 && $dayIndex <= 4) ? 'R' : (($dayIndex === 5) ? 'RJ' : 'RS');
                        } else {
                            $jamTerkecilObj = Carbon::createFromFormat('H:i', $jamDatangInput);
                            $batasNk        = Carbon::createFromFormat('H:i', '18:10');

                            if ($jamTerkecilObj->greaterThan($batasNk)) {
                                $targetKode = 'NK';
                            } elseif ($dayIndex === 6) {
                                $targetKode = 'KS';
                            } elseif ($dayIndex === 5) {
                                $targetKode = 'NJ';
                            } else {
                                $targetKode = 'N';
                            }
                        }

                        $shift = $masterShift->firstWhere('kodeBulan', $targetKode) ?? $masterShift->first();
                        
                        $kodeBulan          = $shift->kodeBulan;
                        $jamDatangWajibAsli = $shift->jamDatangWajib;
                        $jamPulangWajibAsli = $shift->jamPulangWajib;
                        $jamLemburMaksAsli  = $shift->jamLemburMaks;

                        if ($isLiburLembur) {
                            $keterangan        = 'bukan hari lembur/wfa';
                            $jamLemburFinal    = '00:00';
                            $jamKerjaFormated  = substr($jamPulangWajibAsli, 0, 5);
                            $jamPulangFormated = $jamPulangInput;
                        } else {
                            $keterangan = $isBulanPuasa ? 'Import via Excel (Bulan Puasa)' : 'Import via Excel';

                            $kalkulasi = $this->hitungKalkulasiLembur(
                                $jamDatangInput, 
                                $jamDatangWajibAsli, 
                                $jamPulangWajibAsli, 
                                $jamPulangInput, 
                                $jamLemburMaksAsli
                            );
                            
                            $jamLemburFinal    = $kalkulasi['jamLembur'] ?: '00:00';
                            $jamKerjaFormated  = $kalkulasi['jamKerja'];
                            $jamPulangFormated = $kalkulasi['jamPulang'];
                        }
                    }

                    DB::table('tbrekaplembur')->updateOrInsert(
                        [
                            'nip'     => $nip,
                            'tanggal' => $tanggal,
                        ],
                        [
                            'jamDatang'      => $jamDatangInput,
                            'jamWajibDatang' => $jamDatangWajibAsli,
                            'jamKerja'       => $jamKerjaFormated, 
                            'jamPulang'      => $jamPulangFormated,   
                            'jamLembur'      => $jamLemburFinal,                  
                            'aturan'         => $kodeBulan,
                            'keterangan'     => $keterangan,
                        ]
                    );

                    $suksesCount++;
                }
            }

            DB::commit();
            return redirect()->route('lembur.index')->with('success', "Berhasil memproses & mengimpor/memperbarui {$suksesCount} data rekap lembur.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses data Excel: ' . $e->getMessage());
        }
    }

    /**
     * Menyimpan data rekap lembur baru (Manual Input via UI)
     */
    public function store(Request $request)
    {
        $request->validate([
            'jam_kerja_wajib_id' => 'required|integer',
            'nip'                => 'required|string|max:50',
            'tanggal'            => 'required|date',
            'jamDatang'          => 'required',
            'jamWajibDatang'     => 'required',
            'jamPulang'          => 'required',
            'keterangan'         => 'nullable|string',
        ]);

        $shift = DB::table('tbjamkerjawajib')->where('id', $request->jam_kerja_wajib_id)->first();
        if (!$shift) {
            return redirect()->back()->withErrors(['jam_kerja_wajib_id' => 'Aturan shift tidak ditemukan.']);
        }

        $isLibur = DB::table('tbtglliburlembur')->where('tanggal', $request->tanggal)->exists();
        
        if ($isLibur) {
            $keteranganFinal   = 'bukan hari lembur/wfa';
            $jamLemburFinal    = '00:00';
            $jamKerjaFormated  = substr($shift->jamPulangWajib, 0, 5);
            $jamPulangFormated = Carbon::parse($request->jamPulang)->format('H:i');
        } else {
            $kalkulasi = $this->hitungKalkulasiLembur(
                $request->jamDatang,
                $request->jamWajibDatang,
                $shift->jamPulangWajib,
                $request->jamPulang,
                $shift->jamLemburMaks
            );

            $jamLemburFinal    = $kalkulasi['jamLembur'] ?: '00:00';
            $jamKerjaFormated  = $kalkulasi['jamKerja'];
            $jamPulangFormated = $kalkulasi['jamPulang'];
            $keteranganFinal   = $request->keterangan ?? 'Input Manual';
        }

        DB::table('tbrekaplembur')->updateOrInsert(
            [
                'nip'     => $request->nip,
                'tanggal' => $request->tanggal,
            ],
            [
                'jamDatang'      => Carbon::parse($request->jamDatang)->format('H:i'),
                'jamWajibDatang' => $request->jamWajibDatang,
                'jamKerja'       => $jamKerjaFormated, 
                'jamPulang'      => $jamPulangFormated,   
                'jamLembur'      => $jamLemburFinal,                  
                'aturan'         => $shift->kodeBulan,
                'keterangan'     => $keteranganFinal,      
            ]
        );

        return redirect()->route('lembur.index')->with('success', 'Data lembur berhasil disimpan/diperbarui!');
    }

    /**
     * Mengambil detail data lembur untuk modal edit AJAX
     */
    public function edit($id)
    {
        $lembur = DB::table('tbrekaplembur')->where('id', $id)->first();

        if (!$lembur) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $shiftCocok = DB::table('tbjamkerjawajib')
            ->where('jamDatangWajib', 'LIKE', substr($lembur->jamWajibDatang, 0, 5) . '%')
            ->where('kodeBulan', $lembur->aturan)
            ->first();

        $lembur->jam_kerja_wajib_id = $shiftCocok ? $shiftCocok->id : null;

        return response()->json($lembur);
    }

    /**
     * Memperbarui data rekap lembur
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'jam_kerja_wajib_id' => 'required|integer',
            'nip'                => 'required|string|max:50',
            'tanggal'            => 'required|date',
            'jamDatang'          => 'required',
            'jamWajibDatang'     => 'required',
            'jamPulang'          => 'required',
            'keterangan'         => 'nullable|string',
        ]);

        $shift = DB::table('tbjamkerjawajib')->where('id', $request->jam_kerja_wajib_id)->first();
        if (!$shift) {
            return redirect()->back()->withErrors(['jam_kerja_wajib_id' => 'Aturan shift tidak ditemukan.']);
        }

        $isLibur = DB::table('tbtglliburlembur')->where('tanggal', $request->tanggal)->exists();
        
        if ($isLibur) {
            $keteranganFinal   = 'bukan hari lembur/wfa';
            $jamLemburFinal    = '00:00';
            $jamKerjaFormated  = substr($shift->jamPulangWajib, 0, 5);
            $jamPulangFormated = Carbon::parse($request->jamPulang)->format('H:i');
        } else {
            $kalkulasi = $this->hitungKalkulasiLembur(
                $request->jamDatang,
                $request->jamWajibDatang,
                $shift->jamPulangWajib,
                $request->jamPulang,
                $shift->jamLemburMaks
            );

            $jamLemburFinal    = $kalkulasi['jamLembur'] ?: '00:00';
            $jamKerjaFormated  = $kalkulasi['jamKerja'];
            $jamPulangFormated = $kalkulasi['jamPulang'];
            $keteranganFinal   = $request->keterangan ?? 'Diperbarui Manual';
        }

        DB::table('tbrekaplembur')->where('id', $id)->update([
            'nip'            => $request->nip,
            'tanggal'        => $request->tanggal,
            'jamDatang'      => Carbon::parse($request->jamDatang)->format('H:i'),
            'jamWajibDatang' => $request->jamWajibDatang,
            'jamKerja'       => $jamKerjaFormated,
            'jamPulang'      => $jamPulangFormated,
            'jamLembur'      => $jamLemburFinal,
            'aturan'         => $shift->kodeBulan,
            'keterangan'     => $keteranganFinal,      
        ]);

        return redirect()->route('lembur.index')->with('success', 'Data lembur berhasil diperbarui!');
    }

    /**
     * Menghapus data rekap lembur
     */
    public function destroy($id)
    {
        DB::table('tbrekaplembur')->where('id', $id)->delete();
        return redirect()->route('lembur.index')->with('success', 'Data lembur berhasil dihapus!');
    }

    /**
     * Helper Method: Kalkulasi Jam Lembur & Jam Kerja Efektif
     */
    private function hitungKalkulasiLembur($jamDatang, $jamWajibDatang, $jamPulangWajib, $jamPulang, $jamLemburMaks)
    {
        $jamDatangRiil  = Carbon::parse($jamDatang);
        $jamWajibDatang = Carbon::parse($jamWajibDatang);
        $jamKerjaNormal = Carbon::parse($jamPulangWajib);
        $jamPulangRiil  = Carbon::parse($jamPulang);
        $jamLemburMaks  = Carbon::parse($jamLemburMaks);

        if ($jamKerjaNormal->lessThan($jamWajibDatang)) {
            $jamKerjaNormal->addDay();
        }
        if ($jamPulangRiil->lessThan($jamDatangRiil)) {
            $jamPulangRiil->addDay();
        }
        if ($jamLemburMaks->lessThan($jamWajibDatang)) {
            $jamLemburMaks->addDay();
        }

        $menitTerlambat = $jamWajibDatang->diffInMinutes($jamDatangRiil, false);
        $totalKerjaEfektif = clone $jamKerjaNormal;
        
        if ($menitTerlambat > 0) {
            $totalKerjaEfektif->addMinutes($menitTerlambat);
        }

        if ($jamPulangRiil->lessThanOrEqualTo($totalKerjaEfektif)) {
            $jamPulangFinalText = $jamPulangRiil->format('H:i');
            $menitLemburFinal   = 0;
        } elseif ($jamPulangRiil->greaterThan($jamLemburMaks)) {
            $jamPulangFinalText = $jamLemburMaks->format('H:i');
            $menitLemburFinal   = $totalKerjaEfektif->diffInMinutes($jamLemburMaks, false);
        } else {
            $jamPulangFinalText = $jamPulangRiil->format('H:i');
            $menitLemburFinal   = $totalKerjaEfektif->diffInMinutes($jamPulangRiil, false);
        }

        if ($menitLemburFinal < 0) {
            $menitLemburFinal = 0;
        }

        $hours   = floor($menitLemburFinal / 60);
        $minutes = $menitLemburFinal % 60;

        return [
            'jamLembur' => sprintf('%02d:%02d', $hours, $minutes),
            'jamKerja'  => $totalKerjaEfektif->format('H:i'),
            'jamPulang' => $jamPulangFinalText,
        ];
    }
}