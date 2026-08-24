<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RekapLemburMingguanController extends Controller
{
    /**
     * Helper internal untuk mengamankan tipe data tanggal VARCHAR/String agar menjadi DATE standard MySQL
     */
    private function formatTanggalSql($kolom)
    {
        return "CASE 
            WHEN {$kolom} LIKE '__/__/____' THEN STR_TO_DATE({$kolom}, '%d/%m/%Y')
            WHEN {$kolom} LIKE '__-__-____' THEN STR_TO_DATE({$kolom}, '%d-%m-%Y')
            ELSE CAST({$kolom} AS DATE)
        END";
    }

    /**
     * Menampilkan halaman rekap lembur
     */
    public function index(Request $request)
    {
        $tanggal_awal = $request->get('tanggal_awal', date('Y-m-01'));
        $tanggal_akhir = $request->get('tanggal_akhir', date('Y-m-t'));

        // Parameter Mode Jam Lembur ('normal' / 'kjp2')
        $jenis_jam = $request->get('jenis_jam', 'normal');
        $nip_kjp2  = (array) $request->get('nip_kjp2', []);

        // PROTEKSI: Standardisasi format inputan dari filter jika berbentuk DD/MM/YYYY
        if (strpos($tanggal_awal, '/') !== false) {
            $tanggal_awal = Carbon::createFromFormat('d/m/Y', $tanggal_awal)->format('Y-m-d');
            $tanggal_akhir = Carbon::createFromFormat('d/m/Y', $tanggal_akhir)->format('Y-m-d');
        }

        $tglSql = $this->formatTanggalSql('tbrekaplembur.tanggal');

        // Pengecualian Hari: Mode KJP2 menyertakan Sabtu (hanya mengecualikan Minggu)
        $excludedDaysSql = ($jenis_jam === 'kjp2') 
            ? "DAYNAME({$tglSql}) NOT IN ('Sunday')" 
            : "DAYNAME({$tglSql}) NOT IN ('Saturday', 'Sunday')";

        // Ambil data olahan matriks mingguan (Tabel 2)
        $rekapMingguan = $this->prosesDataMingguanPerMinggu($tanggal_awal, $tanggal_akhir, $jenis_jam, $nip_kjp2);

        // REKAP UTAMA (TABEL 1): Disinkronkan penuh dengan query Generate History
        $queryGlobal = DB::table('tbrekaplembur')
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
                // TOTAL HARI: Menggunakan rumus terstandar > 00:00:00 dan tglSql terbungkus aman
                DB::raw("COUNT(DISTINCT CASE WHEN tbrekaplembur.jamLembur > '00:00:00' AND {$excludedDaysSql} THEN {$tglSql} END) as total_hari"),
                // TOTAL JAM
                DB::raw("SEC_TO_TIME(SUM(CASE WHEN {$excludedDaysSql} THEN TIME_TO_SEC(tbrekaplembur.jamLembur) ELSE 0 END)) as total_jam"),
                // ESTIMASI MINGGU
                DB::raw("COUNT(DISTINCT CASE WHEN {$excludedDaysSql} THEN WEEK({$tglSql}) END) as total_minggu")
            )
            ->whereBetween(DB::raw($tglSql), [$tanggal_awal, $tanggal_akhir])
            ->whereRaw($excludedDaysSql);

        // FILTER ATURAN JAM LEMBUR:
        // Jika mode KJP2 -> Hanya ambil aturan NK dan KS
        // Jika mode Normal -> Ambil selain NK dan KS
        if ($jenis_jam === 'kjp2') {
            $queryGlobal->whereIn('tbrekaplembur.aturan', ['NK', 'KS']);
        } else {
            $queryGlobal->whereNotIn('tbrekaplembur.aturan', ['NK', 'KS']);
        }

        $dataLembur = $queryGlobal->groupBy('tbrekaplembur.nip', 'tbdosen.nama')->get();

        // Load data untuk TABEL 3 (Arsip Riwayat)
        $dataLemburHistory = DB::table('tbrekaplemburhistory')
            ->orderBy('dariTanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.rekaplembur.index', compact(
            'tanggal_awal', 
            'tanggal_akhir', 
            'dataLembur', 
            'rekapMingguan', 
            'dataLemburHistory'
        ));
    }

    /**
     * Mengunduh/Pratinjau rekap lembur mingguan dalam format PDF
     */
    public function cetakMingguan(Request $request)
    {
        $tanggal_awal = $request->get('tanggal_awal', date('Y-m-01'));
        $tanggal_akhir = $request->get('tanggal_akhir', date('Y-m-t'));
        $jenis_jam    = $request->get('jenis_jam', 'normal');
        $nip_kjp2     = (array) $request->get('nip_kjp2', []);

        if (strpos($tanggal_awal, '/') !== false) {
            $tanggal_awal = Carbon::createFromFormat('d/m/Y', $tanggal_awal)->format('Y-m-d');
            $tanggal_akhir = Carbon::createFromFormat('d/m/Y', $tanggal_akhir)->format('Y-m-d');
        }

        $rekapMingguan = $this->prosesDataMingguanPerMinggu($tanggal_awal, $tanggal_akhir, $jenis_jam, $nip_kjp2);

        $pdf = Pdf::loadView('pdf.rekaplemburmingguan', compact('tanggal_awal', 'tanggal_akhir', 'rekapMingguan', 'jenis_jam'))
                  ->setPaper('a4', 'portrait'); 

        return $pdf->stream('Rekap_Lembur_Mingguan_' . $tanggal_awal . '_to_' . $tanggal_akhir . '.pdf');
    }

    /**
     * Core Helper: Memproses data lembur harian ke format baris Senin-Jumat/Sabtu dikelompokkan per minggu kalender
     */
    private function prosesDataMingguanPerMinggu($tanggal_awal, $tanggal_akhir, $jenis_jam = 'normal', $nip_kjp2 = [])
    {
        $tglSql = $this->formatTanggalSql('tbrekaplembur.tanggal');

        // Pengecualian Hari
        $excludedDaysSql = ($jenis_jam === 'kjp2') 
            ? "DAYNAME({$tglSql}) NOT IN ('Sunday')" 
            : "DAYNAME({$tglSql}) NOT IN ('Saturday', 'Sunday')";

        $query = DB::table('tbrekaplembur')
            ->join('tbdosen', function($join) {
                $join->on(
                    DB::raw('tbrekaplembur.nip COLLATE utf8mb4_general_ci'), 
                    '=', 
                    DB::raw('tbdosen.nip COLLATE utf8mb4_general_ci')
                );
            })
            ->select('tbrekaplembur.nip', 'tbdosen.nama as namaPegawai', DB::raw("{$tglSql} as tanggal_bersih"), 'tbrekaplembur.jamLembur')
            ->whereBetween(DB::raw($tglSql), [$tanggal_awal, $tanggal_akhir])
            ->whereRaw($excludedDaysSql);

        // FILTER ATURAN KJP2 / NORMAL
        if ($jenis_jam === 'kjp2') {
            $query->whereIn('tbrekaplembur.aturan', ['NK', 'KS']);
            if (!empty($nip_kjp2)) {
                $query->whereIn('tbrekaplembur.nip', $nip_kjp2);
            }
        } else {
            $query->whereNotIn('tbrekaplembur.aturan', ['NK', 'KS']);
        }

        $rawMingguan = $query->orderBy(DB::raw($tglSql), 'asc')
            ->orderBy('tbdosen.nama', 'asc')
            ->get();

        $grupMinggu = [];
        
        // Pemetaan Hari dalam Bahasa Indonesia
        $daftarHari = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat'
        ];

        // Jika Mode KJP2, masukkan hari Sabtu
        if ($jenis_jam === 'kjp2') {
            $daftarHari['Saturday'] = 'Sabtu';
        }

        foreach ($rawMingguan as $row) {
            // Menggunakan tanggal yang sudah dibersihkan oleh MySQL
            $carbonDate = Carbon::parse($row->tanggal_bersih);
            $hariInggris = $carbonDate->format('l');

            if (!array_key_exists($hariInggris, $daftarHari)) {
                continue;
            }

            $hariIndo = $daftarHari[$hariInggris];
            $yearWeekKey = $carbonDate->format('o-W'); 

            if (!isset($grupMinggu[$yearWeekKey])) {
                $startOfWeek = $carbonDate->startOfWeek(Carbon::MONDAY)->translatedFormat('d M Y');
                
                if ($jenis_jam === 'kjp2') {
                    $endOfWeek = $carbonDate->endOfWeek(Carbon::SATURDAY)->translatedFormat('d M Y');
                    $tanggalHariDefault = [
                        'Senin' => '-', 'Selasa' => '-', 'Rabu' => '-', 'Kamis' => '-', 'Jumat' => '-', 'Sabtu' => '-'
                    ];
                } else {
                    $endOfWeek = $carbonDate->endOfWeek(Carbon::FRIDAY)->translatedFormat('d M Y');
                    $tanggalHariDefault = [
                        'Senin' => '-', 'Selasa' => '-', 'Rabu' => '-', 'Kamis' => '-', 'Jumat' => '-'
                    ];
                }

                $grupMinggu[$yearWeekKey] = [
                    'label_periode' => $startOfWeek . ' s/d ' . $endOfWeek,
                    'tanggal_hari'  => $tanggalHariDefault,
                    'data_pegawai'  => []
                ];
            }

            $grupMinggu[$yearWeekKey]['tanggal_hari'][$hariIndo] = $row->tanggal_bersih;

            $nip = $row->nip;
            if (!isset($grupMinggu[$yearWeekKey]['data_pegawai'][$nip])) {
                $pegawaiDefault = [
                    'nip'         => $nip,
                    'nama'        => $row->namaPegawai,
                    'Senin'       => '00:00:00',
                    'Selasa'      => '00:00:00',
                    'Rabu'        => '00:00:00',
                    'Kamis'       => '00:00:00',
                    'Jumat'       => '00:00:00',
                    'total_detik' => 0
                ];

                if ($jenis_jam === 'kjp2') {
                    $pegawaiDefault['Sabtu'] = '00:00:00';
                }

                $grupMinggu[$yearWeekKey]['data_pegawai'][$nip] = $pegawaiDefault;
            }

            $grupMinggu[$yearWeekKey]['data_pegawai'][$nip][$hariIndo] = $row->jamLembur;
            
            if ($row->jamLembur && $row->jamLembur != '00:00:00') {
                list($h, $m, $s) = explode(':', $row->jamLembur);
                $grupMinggu[$yearWeekKey]['data_pegawai'][$nip]['total_detik'] += ($h * 3600) + ($m * 60) + $s;
            }
        }

        ksort($grupMinggu);

        $outputHasil = [];
        $nomorMinggu = 1;

        foreach ($grupMinggu as $key => $value) {
            $pegawaiFormatted = [];
            foreach ($value['data_pegawai'] as $nip => $data) {
                $tSec = $data['total_detik'];
                $hours = floor($tSec / 3600);
                $minutes = floor(($tSec % 3600) / 60);
                $seconds = $tSec % 60;
                
                $data['total_lembur'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                $pegawaiFormatted[] = $data;
            }

            $outputHasil[] = [
                'minggu_ke'     => $nomorMinggu++,
                'label_periode' => $value['label_periode'],
                'tanggal_hari'  => $value['tanggal_hari'],
                'data_pegawai'  => $pegawaiFormatted
            ];
        }

        return $outputHasil;
    }
}