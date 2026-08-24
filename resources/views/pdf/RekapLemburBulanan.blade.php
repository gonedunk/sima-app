@extends('pdf.kopsurat')

@section('title', 'Rekapitulasi Kehadiran Jurusan Akuntansi')

@section('additional_styles')
    <style>
        .judul-dokumen { text-align: center !important; width: 100%; margin-bottom: 0; }
        .judul-dokumen h3 { font-size: 13pt; text-transform: uppercase; margin: 0; font-weight: bold; text-align: center !important; line-height: 1.4; }
        .judul-dokumen p { font-size: 11pt; margin: 5px 0 0 0; font-weight: bold; text-align: center !important; text-transform: uppercase; }

        /* Table Data Style */
        .table-data { width: 100%; border-collapse: collapse; margin-top: 2em; }
        
        .table-data th, .table-data td { 
            border: 1px solid #000; 
            padding: 5px; 
            font-size: 10pt; 
            vertical-align: middle; 
            text-align: center; 
        }
        .table-data th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 9pt; }
        
        .bg-nomor-kolom { background-color: #eaeaea !important; font-size: 9pt; font-weight: normal !important; text-transform: none; }

        /* Typography Helper */
        .nip-sub { font-size: 8pt; font-family: 'Courier', monospace; color: #333; display: block; }
        .nama-pegawai { font-size: 9pt; font-weight: bold; }
        .fw-bold { font-weight: bold; }
        .font-monospace { font-family: 'Courier', monospace; }
        
        .text-start { 
            text-align: left !important; 
            padding-left: 8px !important; 
        }

        /* STYLES: Area Tanda Tangan (Atas Kanan) */
        .wrapper-ttd {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .table-ttd {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .table-ttd td {
            border: none;
            padding: 0;
            vertical-align: top;
            font-size: 10pt;
        }

        .ttd-kanan {
            width: 45%;
            text-align: left;
            padding-left: 20px;
        }

        .ttd-spacer {
            height: 60px;
        }
    </style>
@endsection

@section('content')
    @php
        // 1. Cek opsi Radio Button Jam KJP 2
        $isKjp2 = isset($jenis_jam) ? ($jenis_jam == 'kjp2') : (request('jenis_jam') == 'kjp2');

        // 2. Ambil list NIP yang dicentang (Menjangkau nip_kjp2, nip_pilihan, selected_nip, maupun nip)
        $nipSelected = $nip_pilihan 
                        ?? request('nip_kjp2') 
                        ?? request('nip_pilihan') 
                        ?? request('selected_nip') 
                        ?? request('nip') 
                        ?? [];

        if (!is_array($nipSelected)) {
            $nipSelected = array_filter(explode(',', $nipSelected));
        }

        // Array translasi nama bulan ke Bahasa Indonesia
        $bulanIndo = [
            '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL',
            '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS',
            '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'
        ];

        // Parsing format Tanggal Awal & Akhir
        $tglAwalHari  = date('d', strtotime($tanggal_awal));
        $tglAwalBulan = $bulanIndo[date('m', strtotime($tanggal_awal))];
        $tglAwalTahun = date('Y', strtotime($tanggal_awal));

        $tglAkhirHari  = date('d', strtotime($tanggal_akhir));
        $tglAkhirBulan = $bulanIndo[date('m', strtotime($tanggal_akhir))];
        $tglAkhirTahun = date('Y', strtotime($tanggal_akhir));

        if ($tglAwalBulan == $tglAkhirBulan && $tglAwalTahun == $tglAkhirTahun) {
            $periodeTeks = $tglAwalHari . " S/D " . $tglAkhirHari . " BULAN " . $tglAwalBulan . " TAHUN " . $tglAwalTahun;
        } else {
            $periodeTeks = $tglAwalHari . " " . $tglAwalBulan . " " . $tglAwalTahun . " S/D " . $tglAkhirHari . " " . $tglAkhirBulan . " " . $tglAkhirTahun;
        }

        // Tanggal TTD
        $tglCetakHari  = date('d');
        $tglCetakBulan = $bulanIndo[date('m')];
        $tglCetakTahun = date('Y');
        $tanggalTTD    = $tglCetakHari . ' ' . $tglCetakBulan . ' ' . $tglCetakTahun;

        // Ambil Data Utama
        $rawCollection = collect($dataCetakHistory ?? $dataCtxtHistory ?? []);

        // =========================================================================
        // FILTERING MANDIRI: Tanpa Mempedulikan Mode KJP1 / KJP2
        // Jika ada NIP yang dicentang, WAJIB disaring berdasarkan NIP tersebut!
        // =========================================================================
        if (!empty($nipSelected)) {
            $filteredCollection = $rawCollection->filter(function($item) use ($nipSelected) {
                $nipItem = $item->nip ?? $item->nip_pegawai ?? null;
                return in_array($nipItem, $nipSelected);
            });
        } else {
            $filteredCollection = $rawCollection;
        }
    @endphp

    <div class="judul-dokumen">
        <h3>REKAPITULASI KEHADIRAN</h3>
        <h3>JURUSAN AKUNTANSI KELAS {{ $isKjp2 ? 'KJP 2' : 'KJP 1' }}</h3>
        <h3>POLITEKNIK NEGERI SRIWIJAYA</h3>
        <p>DARI TANGGAL {{ $periodeTeks }}</p>
    </div>

    <table class="table-data">
        <colgroup>
            <col style="width: 5%;">   <!-- No -->
            <col style="width: 45%;">  <!-- Nama / NIP -->
            <col style="width: 20%;">  <!-- Periode Lembur -->
            <col style="width: 15%;">  <!-- Total Hari -->
            <col style="width: 15%;">  <!-- Total Jam -->
        </colgroup>
        
        <thead>
            <tr>
                <th>No</th>
                <th>Nama / NIP</th>
                <th>Periode Lembur</th>
                <th>Total Hari</th>
                <th>Total Jam</th>
            </tr>
            <tr>
                <th class="bg-nomor-kolom">1</th>
                <th class="bg-nomor-kolom">2</th>
                <th class="bg-nomor-kolom">3</th>
                <th class="bg-nomor-kolom">4</th>
                <th class="bg-nomor-kolom">5</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filteredCollection as $index => $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="text-start">
                    <span class="nama-pegawai">{{ $row->namaPegawai ?? $row->nama ?? '-' }}</span>
                    <span class="nip-sub">NIP. {{ $row->nip ?? '-' }}</span>
                </td>
                <td>
                    {{ date('d/m/y', strtotime($row->dariTanggal)) }} s.d {{ date('d/m/y', strtotime($row->sampaiTanggal)) }}
                </td>
                
                <td>{{ $row->total_hari ?? $row->jumlahTotalHariLembur ?? 0 }} Hari</td>
                <td class="font-monospace fw-bold">
                    @php
                        if ($isKjp2 && isset($row->total_jam_kjp2)) {
                            $totalJamVal = $row->total_jam_kjp2;
                        } else {
                            $totalJamVal = $row->total_jam ?? $row->jumlahTotalJamLembur ?? '00:00';
                        }
                    @endphp
                    {{ substr($totalJamVal, 0, 5) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding: 15px; font-style: italic; color: #777;">
                    Tidak ada data rekapitulasi kehadiran untuk {{ $isKjp2 ? 'KJP 2' : 'KJP 1' }} berdasarkan NIP yang dipilih.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- BLOK KANAN TANDA TANGAN DARI tbpengelolajurusan -->
    <div class="wrapper-ttd">
        <table class="table-ttd">
            <tr>
                <td style="width: 55%;"></td> <!-- Kolom Spasi Kosong Kiri -->
                <td class="ttd-kanan">
                    Palembang, {{ $tanggalTTD }}<br>
                    
                    @php
                        $pejabat = $ketuaJurusan ?? ($pengelolaJurusan->first() ?? null);
                        $isKetua = $pejabat ? (strpos(strtolower($pejabat->jabatan), 'ketua jurusan') !== false) : true;
                    @endphp

                    @if(!$isKetua)
                        a.n. Ketua Jurusan,<br>
                    @endif

                    @if($pejabat)
                        {{ $pejabat->jabatan }},
                    @else
                        Ketua Jurusan Akuntansi,
                    @endif
                    
                    <div class="ttd-spacer"></div>

                    <strong>
                        <u>
                            {{ $pejabat ? $pejabat->nama_pengelola : '-' }}
                        </u>
                    </strong><br>

                    <span class="nip-sub" style="font-size: 9pt;">
                        NIP. {{ $pejabat ? $pejabat->nip : '-' }}
                    </span>
                </td>
            </tr>
        </table>
    </div>
@endsection