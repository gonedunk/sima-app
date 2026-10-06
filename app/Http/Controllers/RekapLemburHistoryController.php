<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class RekapLemburHistoryController extends Controller
{
    /**
     * Tampilkan halaman utama rekap lembur history.
     */
    public function index(Request $request)
    {
        $dataLemburHistory = DB::table('tbrekaplemburhistory')
            ->orderBy('dariTanggal', 'desc')
            ->get();

        return view('admin.rekaplembur.index', compact('dataLemburHistory'));
    }

    /**
     * Menyimpan data rekap bulanan ke Log Arsip Permanen (tbrekaplemburhistory).
     * Memungkinkan 1 NIP memiliki 2 arsip terpisah: Jam Normal & Jam KJP 2.
     */
    public function generateHistory(Request $request)
    {
        // 1. Validasi Input Parameter
        $request->validate([
            'tanggal_awal'  => 'required',
            'tanggal_akhir' => 'required',
            'keterangan'    => 'required|string|max:255',
            'jenis_jam'     => 'nullable|string',
        ]);

        $tanggal_awal   = $request->tanggal_awal;
        $tanggal_akhir  = $request->tanggal_akhir;
        $keteranganUser = $request->keterangan;
        
        // Mode Jam (Normal vs KJP2)
        $jenis_jam = $request->input('jenis_jam', 'normal');
        $isKjp2    = ($jenis_jam === 'kjp2');

        // Tangkap NIP tercentang dari form
        $nipPilihan = $request->input('nip_pilihan') 
                        ?? $request->input('nip_kjp2') 
                        ?? $request->input('selected_nips') 
                        ?? $request->input('nip') 
                        ?? [];

        if (is_string($nipPilihan)) {
            $nipPilihan = array_filter(explode(',', $nipPilihan));
        }

        // Jika mode KJP 2 dipilih tetapi tidak ada NIP yang dicentang
        if ($isKjp2 && empty($nipPilihan)) {
            return redirect()->back()->with('error', 'Silakan centang minimal satu NIP pegawai untuk menyimpan arsip KJP 2.');
        }

        // 2. Standardisasi Format Tanggal ke YYYY-MM-DD
        try {
            if (strpos($tanggal_awal, '/') !== false) {
                $tanggal_awal = Carbon::createFromFormat('d/m/Y', $tanggal_awal)->format('Y-m-d');
                $tanggal_akhir = Carbon::createFromFormat('d/m/Y', $tanggal_akhir)->format('Y-m-d');
            } else {
                $tanggal_awal = Carbon::parse($tanggal_awal)->format('Y-m-d');
                $tanggal_akhir = Carbon::parse($tanggal_akhir)->format('Y-m-d');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Format rentang tanggal filter tidak valid.');
        }

        // 3. Eksekusi Database Transaction
        try {
            DB::beginTransaction();

            // DAYOFWEEK MySQL: 1 = Minggu, 2 = Senin, ..., 6 = Jumat, 7 = Sabtu
            $querySource = DB::table('tbrekaplembur')
                ->join('tbdosen', function($join) {
                    $join->on(
                        DB::raw('tbrekaplembur.nip COLLATE utf8mb4_general_ci'), 
                        '=', 
                        DB::raw('tbdosen.nip COLLATE utf8mb4_general_ci')
                    );
                })
                ->select(
                    'tbrekaplembur.nip',
                    'tbdosen.nama as namaPegawai',
                    DB::raw("COUNT(DISTINCT CASE WHEN tbrekaplembur.jamLembur > '00:00:00' THEN tbrekaplembur.tanggal END) as total_hari"),
                    DB::raw("SEC_TO_TIME(SUM(TIME_TO_SEC(tbrekaplembur.jamLembur))) as total_jam"),
                    DB::raw("CEIL(COUNT(DISTINCT CASE WHEN tbrekaplembur.jamLembur > '00:00:00' THEN tbrekaplembur.tanggal END) / " . ($isKjp2 ? 6 : 5) . ") as total_minggu")
                )
                ->whereBetween('tbrekaplembur.tanggal', [$tanggal_awal, $tanggal_akhir]);

            // =========================================================================
            // ISOLASI FILTER HARI & ATURAN JAM
            // =========================================================================
            if ($isKjp2) {
                // KJP2: Hari Sabtu/Minggu (1, 7) atau penanda khusus KJP2
                $querySource->where(function($q) {
                    $q->whereRaw("DAYOFWEEK(tbrekaplembur.tanggal) IN (1, 7)");
                    if (DB::getSchemaBuilder()->hasColumn('tbrekaplembur', 'jenis_jam')) {
                        $q->orWhere('tbrekaplembur.jenis_jam', 'kjp2');
                    } elseif (DB::getSchemaBuilder()->hasColumn('tbrekaplembur', 'kjp2')) {
                        $q->orWhere('tbrekaplembur.kjp2', 1);
                    }
                });

                if (!empty($nipPilihan)) {
                    $querySource->whereIn('tbrekaplembur.nip', $nipPilihan);
                }
            } else {
                // Jam Normal: Hanya Senin s/d Jumat (2 s/d 6)
                $querySource->whereRaw("DAYOFWEEK(tbrekaplembur.tanggal) BETWEEN 2 AND 6");

                if (DB::getSchemaBuilder()->hasColumn('tbrekaplembur', 'jenis_jam')) {
                    $querySource->where(function($q) {
                        $q->where('tbrekaplembur.jenis_jam', 'normal')
                          ->orWhereNull('tbrekaplembur.jenis_jam');
                    });
                } elseif (DB::getSchemaBuilder()->hasColumn('tbrekaplembur', 'kjp2')) {
                    $querySource->where(function($q) {
                        $q->where('tbrekaplembur.kjp2', 0)
                          ->orWhereNull('tbrekaplembur.kjp2');
                    });
                }
            }

            $sourceData = $querySource->groupBy('tbrekaplembur.nip', 'tbdosen.nama')->get();

            if ($sourceData->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tidak ditemukan data aktivitas lembur untuk kategori ' . ($isKjp2 ? 'KJP2' : 'Normal') . ' pada rentang tanggal tersebut.');
            }

            // =========================================================================
            // ID INTEGER PEMBEDA IDTBREKAP (KHUSUS COLUMN TIPE INT)
            // Contoh Mei 2026:
            // Normal => 2026051 (Angka Murni)
            // KJP2   => 2026052 (Angka Murni)
            // =========================================================================
            $baseBatchId = (int) date('Ym', strtotime($tanggal_awal));
            $batchId     = $isKjp2 ? (int)($baseBatchId . '2') : (int)($baseBatchId . '1');
            $isUpdated   = false;

            // 4. Proses Insert / Update
            foreach ($sourceData as $data) {
                if ($data->total_hari == 0 || !$data->total_jam || substr($data->total_jam, 0, 5) == '00:00') {
                    continue;
                }

                // Cek ketersediaan record spesifik berdasarkan idtbrekap Integer
                $existing = DB::table('tbrekaplemburhistory')
                    ->where('idtbrekap', $batchId)
                    ->where('nip', $data->nip)
                    ->where('dariTanggal', $tanggal_awal)
                    ->where('sampaiTanggal', $tanggal_akhir)
                    ->first();

                $jamFormatted = substr($data->total_jam, 0, 8);
                $mingguLembur = $data->total_minggu > 0 ? $data->total_minggu : 1;

                if ($existing) {
                    DB::table('tbrekaplemburhistory')
                        ->where('id', $existing->id)
                        ->update([
                            'namaPegawai'           => $data->namaPegawai,
                            'dariTanggal'           => $tanggal_awal,
                            'sampaiTanggal'         => $tanggal_akhir,
                            'jumlahTotalHariLembur' => $data->total_hari,
                            'jumlahTotalJamLembur'  => $jamFormatted,
                            'jumlahMingguLembur'    => $mingguLembur,
                            'keterangan'            => $keteranganUser
                        ]);
                    $isUpdated = true;
                } else {
                    DB::table('tbrekaplemburhistory')->insert([
                        'idtbrekap'             => $batchId,
                        'nip'                   => $data->nip,
                        'namaPegawai'           => $data->namaPegawai,
                        'dariTanggal'           => $tanggal_awal,
                        'sampaiTanggal'         => $tanggal_akhir,
                        'jumlahTotalHariLembur' => $data->total_hari,
                        'jumlahTotalJamLembur'  => $jamFormatted,
                        'jumlahMingguLembur'    => $mingguLembur,
                        'keterangan'            => $keteranganUser
                    ]);
                }
            }

            DB::commit();

            $jenisLaporanStr = $isKjp2 ? 'KJP 2' : 'Jam Normal';
            $msg = $isUpdated 
                ? "Arsip riwayat ({$jenisLaporanStr}) berhasil diperbarui." 
                : "Arsip riwayat ({$jenisLaporanStr}) berhasil disimpan secara terpisah dari Jam Normal.";

            return redirect()->back()->with('success', $msg);

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses pengarsipan data: ' . $e->getMessage());
        }
    }

    /**
     * Mengubah Keterangan Arsip Riwayat Laporan
     */
    public function updateArsip(Request $request, $id)
    {
        $request->validate([
            'keterangan' => 'required|string|max:255',
        ]);

        try {
            DB::table('tbrekaplemburhistory')
                ->where('id', $id)
                ->update(['keterangan' => $request->keterangan]);

            return redirect()->back()->with('success', 'Keterangan arsip berhasil diubah.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengubah arsip: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus Data Arsip dari Permanent History
     */
    public function destroyArsip($id)
    {
        try {
            DB::table('tbrekaplemburhistory')->where('id', $id)->delete();
            return redirect()->back()->with('success', 'Data arsip permanent history berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus arsip: ' . $e->getMessage());
        }
    }
}