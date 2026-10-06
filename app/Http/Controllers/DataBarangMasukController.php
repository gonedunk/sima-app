<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Spatie\SimpleExcel\SimpleExcelReader;

class DataBarangMasukController extends Controller
{
    public function index()
    {
        $dosenPenerima = DB::table('tbdosen')
            ->where('level', '02')
            ->orderBy('nama', 'asc')
            ->get();

        // Subquery: Ambil ID transaksi TERAKHIR per idAnak
        $latestTransaksiSub = DB::table('tbtransaksibarangmasuk as t1')
            ->select('t1.idAnak', DB::raw('MAX(t1.id) as latest_id'))
            ->groupBy('t1.idAnak');

        // Master & Anak Barang + Status & Tanggal Terakhir
        $masterBarang = DB::table('tbanakbarang')
            ->join('tbmasterbarang', 'tbanakbarang.idMaster', '=', 'tbmasterbarang.id')
            ->leftJoinSub($latestTransaksiSub, 'latest_transaksi', function($join) {
                $join->on('tbanakbarang.id', '=', 'latest_transaksi.idAnak');
            })
            ->leftJoin('tbtransaksibarangmasuk', 'latest_transaksi.latest_id', '=', 'tbtransaksibarangmasuk.id')
            ->select(
                'tbanakbarang.id',
                'tbanakbarang.merkBarang',
                'tbanakbarang.spesifikasi',
                'tbmasterbarang.namaBarang as nama_master',
                'tbtransaksibarangmasuk.keterangan as keterangan_terakhir',
                'tbtransaksibarangmasuk.tglMasuk as tgl_terakhir'
            )
            ->orderBy('tbmasterbarang.namaBarang', 'asc')
            ->orderBy('tbanakbarang.merkBarang', 'asc')
            ->get();

        // Histori Transaksi
        $barangMasuk = DB::table('tbtransaksibarangmasuk')
            ->join('tbanakbarang', 'tbtransaksibarangmasuk.idAnak', '=', 'tbanakbarang.id')
            ->leftJoin('tbdosen', function($join) {
                $join->on('tbtransaksibarangmasuk.penerima', '=', DB::raw('tbdosen.nip COLLATE utf8mb4_general_ci'));
            })
            ->select(
                'tbtransaksibarangmasuk.id',
                'tbtransaksibarangmasuk.idAnak', 
                'tbtransaksibarangmasuk.tglMasuk',
                'tbanakbarang.merkBarang',
                'tbanakbarang.spesifikasi',
                'tbtransaksibarangmasuk.namaSupplier',
                'tbtransaksibarangmasuk.penerima as nip_penerima',
                'tbdosen.nama as nama_dosen',
                'tbtransaksibarangmasuk.jumlah',
                'tbtransaksibarangmasuk.keterangan'
            )
            ->orderBy('tbtransaksibarangmasuk.tglMasuk', 'desc')
            ->orderBy('tbtransaksibarangmasuk.id', 'desc')
            ->get();

        return view('admin.databarangmasuk.index', compact('dosenPenerima', 'masterBarang', 'barangMasuk'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tglMasuk'     => 'required|date',
            'namaSupplier' => 'required|string|max:255',
            'penerima'     => 'required|string',
            'items'        => 'required|array'
        ]);

        $tglMasuk     = $request->input('tglMasuk');
        $namaSupplier = $request->input('namaSupplier');
        $penerima     = $request->input('penerima');
        $items        = $request->input('items');

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                if (isset($item['selected']) && $item['selected'] == '1') {
                    $idAnak      = $item['idAnak'];
                    $jumlahInput = (int) ($item['jumlah'] ?? 0);
                    $statusForm  = $item['status_kirim'] ?? 'Cukup';

                    // 1. PENGECEKAN UTANG SEBELUMNYA & VALIDASI WAKTU
                    $lastTx = DB::table('tbtransaksibarangmasuk')
                        ->where('idAnak', $idAnak)
                        ->orderBy('tglMasuk', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();

                    $ketTerakhir = $lastTx ? $lastTx->keterangan : 'Cukup';
                    $isHutangSebelumnya = str_contains($ketTerakhir, 'Kurang') || str_starts_with($ketTerakhir, '-');
                    $hutangAwal = $isHutangSebelumnya ? (int) preg_replace('/[^\d]/', '', $ketTerakhir) : 0;

                    if ($lastTx && $tglMasuk < $lastTx->tglMasuk) {
                        DB::rollBack();
                        return redirect()->back()
                            ->withInput()
                            ->with('error', "Transaksi Dibatalkan! Tanggal masuk ({$tglMasuk}) lebih kecil dari tanggal transaksi terakhir barang ({$lastTx->tglMasuk}).");
                    }

                    // 2. OLAH KETERANGAN UTANG / CICILAN
                    if ($hutangAwal > 0) {
                        if ($jumlahInput > $hutangAwal) {
                            $kelebihan = $jumlahInput - $hutangAwal;
                            $barangObj = DB::table('tbanakbarang')->where('id', $idAnak)->first();
                            $namaMerk  = $barangObj ? $barangObj->merkBarang : 'Barang';

                            DB::rollBack();
                            return redirect()->back()
                                ->withInput()
                                ->with('error', "Transaksi dibatalkan! Pada item '{$namaMerk}': Barang yang datang melebihi hutang sejumlah {$kelebihan} Pcs. Sisa hutang barang adalah {$hutangAwal} Pcs.");
                        }

                        $sisaHutangBaru  = $hutangAwal - $jumlahInput;
                        $keteranganFinal = ($sisaHutangBaru == 0) ? 'Cukup' : ('-' . $sisaHutangBaru);
                    } else {
                        if ($statusForm === 'Kurang') {
                            $jumlahKurangBaru = (int) ($item['jumlah_kurang_input'] ?? 0);
                            $keteranganFinal  = ($jumlahKurangBaru > 0) ? ('-' . $jumlahKurangBaru) : 'Kurang';
                        } else {
                            $keteranganFinal  = 'Cukup';
                        }
                    }

                    // 3. INSERT KE TABEL tbtransaksibarangmasuk
                    DB::table('tbtransaksibarangmasuk')->insert([
                        'tglMasuk'     => $tglMasuk,
                        'idAnak'       => $idAnak,
                        'namaSupplier' => $namaSupplier,
                        'penerima'     => $penerima,
                        'jumlah'       => $jumlahInput,
                        'keterangan'   => $keteranganFinal,
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Transaksi barang masuk berhasil disimpan dan stok opname diperbarui otomatis oleh sistem.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $transaksi = DB::table('tbtransaksibarangmasuk')->where('id', $id)->first();
            
            if (!$transaksi) {
                return redirect()->back()->with('error', 'Data transaksi barang masuk tidak ditemukan.');
            }

            DB::table('tbtransaksibarangmasuk')->where('id', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Histori transaksi barang masuk berhasil dihapus dan stok otomatis disesuaikan.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * EXPORT DATA BARANG MASUK KE EXCEL
     */
    public function exportExcel()
    {
        $fileName = 'data_barang_masuk_' . date('Y-m-d_H-i-s') . '.xlsx';

        $data = DB::table('tbtransaksibarangmasuk')
            ->join('tbanakbarang', 'tbtransaksibarangmasuk.idAnak', '=', 'tbanakbarang.id')
            ->select(
                'tbtransaksibarangmasuk.tglMasuk',
                'tbanakbarang.merkBarang',
                'tbtransaksibarangmasuk.jumlah',
                'tbtransaksibarangmasuk.namaSupplier',
                'tbtransaksibarangmasuk.penerima'
            )
            ->orderBy('tbtransaksibarangmasuk.tglMasuk', 'desc')
            ->get();

        $writer = SimpleExcelWriter::streamDownload($fileName);

        foreach ($data as $row) {
            $writer->addRow([
                'tglMasuk'     => $row->tglMasuk,
                'merkBarang'   => $row->merkBarang,
                'jumlah'       => $row->jumlah,
                'namaSupplier' => $row->namaSupplier,
                'penerima'     => $row->penerima,
            ]);
        }

        return $writer->toBrowser();
    }

    /**
     * IMPORT DATA BARANG MASUK DARI EXCEL (3-Tier Hybrid Matching: Exact -> Token -> Strict Fuzzy)
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file_excel.required' => 'Harap pilih file terlebih dahulu.',
            'file_excel.mimes'    => 'Format file harus berupa .xlsx, .xls, atau .csv.',
            'file_excel.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        $file = $request->file('file_excel');
        $filePath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        // Helper 1: Pembersihan String Alfanumerik
        $cleanString = function ($str) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $str)));
        };

        // Helper 2: Pecah String Menjadi Token Kata Unik
        $getTokenArray = function ($str) {
            $clean = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $str));
            $words = array_filter(explode(' ', $clean));
            return array_values(array_unique($words));
        };

        // 1. Pre-fetch seluruh data tbanakbarang
        $anakBarangList = DB::table('tbanakbarang')->select('id', 'merkBarang')->get();

        DB::beginTransaction();
        try {
            $reader = SimpleExcelReader::create($filePath, $extension);
            $rows = $reader->getRows();

            $insertData = [];
            $skippedCount = 0;
            
            // Ambil data pending di session
            $unregisteredItems = session('unregistered_items', []);

            foreach ($rows as $row) {
                $merkBarangInput = $row['merkBarang'] ?? $row['merk_barang'] ?? null;
                $tglMasuk        = $row['tglMasuk'] ?? $row['tgl_masuk'] ?? null;
                $jumlah          = $row['jumlah'] ?? 0;
                $namaSupplier    = $row['namaSupplier'] ?? $row['nama_supplier'] ?? '-';
                $penerima        = $row['penerima'] ?? '-';

                if (!$merkBarangInput || !$tglMasuk) {
                    continue;
                }

                $cleanInputMerk = $cleanString($merkBarangInput);

                if ($tglMasuk instanceof \DateTimeInterface) {
                    $formattedDate = $tglMasuk->format('Y-m-d');
                } else {
                    $formattedDate = date('Y-m-d', strtotime($tglMasuk));
                }

                // 2. PENCOCOKAN BARANG HYBRID (3 TIER MATCHING)
                $matchedIdAnak = null;

                // TIER 1: EXACT MATCH (Kesamaan 100% tanpa simbol/spasi/kapitalisasi)
                foreach ($anakBarangList as $ab) {
                    if ($cleanString($ab->merkBarang) === $cleanInputMerk) {
                        $matchedIdAnak = $ab->id;
                        break;
                    }
                }

                // TIER 2: TOKEN / WORD MATCHING (Pencocokan berbasis kata kunci penting)
                if ($matchedIdAnak === null) {
                    $inputTokens = $getTokenArray($merkBarangInput);
                    $highestTokenScore = 0;

                    if (!empty($inputTokens)) {
                        foreach ($anakBarangList as $ab) {
                            $dbTokens = $getTokenArray($ab->merkBarang);
                            if (empty($dbTokens)) continue;

                            $matchingWords = array_intersect($inputTokens, $dbTokens);
                            $unionCount = count(array_unique(array_merge($inputTokens, $dbTokens)));
                            $score = count($matchingWords) / $unionCount;

                            // Jika persentase kata kunci cocok cukup tinggi (Jaccard Index >= 35%)
                            if ($score >= 0.35 && $score > $highestTokenScore) {
                                $highestTokenScore = $score;
                                $matchedIdAnak = $ab->id;
                            }
                        }
                    }
                }

                // TIER 3: HIGH-THRESHOLD FUZZY MATCH (Persentase Karakter Kemiripan >= 85%)
                if ($matchedIdAnak === null) {
                    $highestPercent = 0;

                    foreach ($anakBarangList as $ab) {
                        $cleanDbMerk = $cleanString($ab->merkBarang);
                        similar_text($cleanInputMerk, $cleanDbMerk, $percent);

                        if ($percent >= 85 && $percent > $highestPercent) {
                            $highestPercent = $percent;
                            $matchedIdAnak = $ab->id;
                        }
                    }
                }

                // PENANGANAN JIKA BARANG COCOK DENGAN MASTER DB
                if ($matchedIdAnak !== null) {
                    $idAnak = $matchedIdAnak;

                    // A. Cek Duplikasi Eksak (idAnak + tglMasuk)
                    $isExactDuplicate = DB::table('tbtransaksibarangmasuk')
                        ->where('idAnak', $idAnak)
                        ->whereDate('tglMasuk', $formattedDate)
                        ->exists();

                    if ($isExactDuplicate) {
                        $skippedCount++;
                        continue; // SKIP duplikat
                    }

                    // B. Cek Tanggal Transaksi Terakhir
                    $lastTx = DB::table('tbtransaksibarangmasuk')
                        ->where('idAnak', $idAnak)
                        ->orderBy('tglMasuk', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($lastTx && $formattedDate < $lastTx->tglMasuk) {
                        $skippedCount++;
                        continue; // SKIP jika tanggal lebih lampau
                    }

                    // C. Masukkan ke Antrean Batch Insert
                    $insertData[] = [
                        'idAnak'       => $idAnak,
                        'tglMasuk'     => $formattedDate,
                        'jumlah'       => (int) $jumlah,
                        'namaSupplier' => $namaSupplier,
                        'penerima'     => $penerima,
                        'keterangan'   => 'Cukup',
                    ];
                } else {
                    // BARANG TIDAK COCOK -> MASUKKAN KE PENDING SESSION UNREGISTERED
                    $isDuplicatePending = false;
                    foreach ($unregisteredItems as $existing) {
                        if ($cleanString($existing['merkBarang']) === $cleanInputMerk && $existing['tglMasuk'] === $formattedDate) {
                            $isDuplicatePending = true;
                            break;
                        }
                    }

                    if (!$isDuplicatePending) {
                        $unregisteredItems[] = [
                            'merkBarang'   => $merkBarangInput,
                            'tglMasuk'     => $formattedDate,
                            'jumlah'       => (int) $jumlah,
                            'namaSupplier' => $namaSupplier,
                            'penerima'     => $penerima
                        ];
                    }
                }
            }

            // Eksekusi Batch Insert
            if (!empty($insertData)) {
                DB::table('tbtransaksibarangmasuk')->insert($insertData);
            }

            DB::commit();

            // Perbarui Session
            if (!empty($unregisteredItems)) {
                session(['unregistered_items' => array_values($unregisteredItems)]);
            } else {
                session()->forget('unregistered_items');
            }

            // Pesan Notifikasi Feedback
            $countInserted = count($insertData);
            $msg = "Import data barang masuk selesai. Total {$countInserted} baris berhasil diimport.";
            
            if ($skippedCount > 0) {
                $msg .= " ({$skippedCount} baris dilewati karena duplikasi ID & tanggal / tanggal lebih lampau).";
            }

            if (!empty($unregisteredItems)) {
                $msg .= ' Terdapat ' . count($unregisteredItems) . ' item barang belum terdaftar di Master Barang.';
                return redirect()->back()->with('warning', $msg);
            }

            if ($countInserted === 0 && $skippedCount > 0) {
                return redirect()->back()->with('warning', "Tidak ada data baru yang diimport. {$skippedCount} baris dilewati karena duplikasi ID & Tanggal.");
            }

            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    /**
     * HAPUS ITEM TERTUNDA DARI SESSION
     */
    public function removeUnregisteredItem($index)
    {
        $items = session('unregistered_items', []);

        if (isset($items[$index])) {
            unset($items[$index]);
            $items = array_values($items);

            if (empty($items)) {
                session()->forget('unregistered_items');
            } else {
                session(['unregistered_items' => $items]);
            }
        }

        return redirect()->back()->with('success', 'Item berhasil dihapus dari daftar pending.');
    }
}