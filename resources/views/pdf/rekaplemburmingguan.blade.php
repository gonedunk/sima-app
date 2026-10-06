<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Lembur Mingguan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.3;
        }
        .container {
            width: 100%;
        }
        .judul-rekap {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin-top: 10px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        /* Mengunci totalitas blok judul minggu + tabel agar tidak terpisah halaman */
        .block-minggu {
            display: inline-block;
            width: 100%;
            page-break-inside: avoid; 
            margin-bottom: 20px;
        }
        
        .header-minggu {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
            margin-bottom: 6px;
        }
        .kop-table, .kop-table th, .kop-table td {
            border: none !important;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000000;
            padding: 4px 2px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #f8f9fa;
            color: #000000;
            font-weight: bold;
            text-align: center;
        }
        .th-total {
            background-color: #34495e;
            color: #ffffff;
        }
        .bg-nomor-kolom {
            background-color: #eaeaea;
            font-size: 9px;
            font-weight: normal;
            text-align: center;
            padding: 2px;
        }
        .text-start {
            text-align: left;
            padding-left: 4px;
        }
        .nama-pegawai {
            font-weight: bold;
            color: #000;
        }
        .nip-pegawai {
            color: #333333;
            font-size: 9px;
            font-family: monospace;
            margin-top: 1px;
        }
        .small-date {
            font-weight: normal;
            font-size: 9px;
            color: #000;
            display: block;
            margin-top: 1px;
        }
        .td-total {
            font-weight: bold;
            background-color: #f1f9f5;
        }

        /* STYLE KHUSUS: Kolom di luar rentang tanggal (Background Hitam) */
        .bg-outside-range {
            background-color: #000000 !important;
            color: #ffffff !important;
        }
        .bg-outside-range .small-date {
            color: #cccccc !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="kop-table">
        @include('pdf.kopsuratdompdf')
    </div>

    @php
        // Cek apakah radiobutton jam kjp2 dipilih
        // Silakan sesuaikan nama variabel request/parameter ($jenis_jam atau $kjp) dengan yang dikirim dari Controller
        $isKjp2 = isset($jenis_jam) && $jenis_jam == 'kjp2';

        $bulanIndo = [
            '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL',
            '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS',
            '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'
        ];

        $tglAwalHari  = date('d', strtotime($tanggal_awal));
        $tglAwalBulan = $bulanIndo[date('m', strtotime($tanggal_awal))];
        $tglAwalTahun = date('Y', strtotime($tanggal_awal));

        $tglAkhirHari  = date('d', strtotime($tanggal_akhir));
        $tglAkhirBulan = $bulanIndo[date('m', strtotime($tanggal_akhir))];
        $tglAkhirTahun = date('Y', strtotime($tanggal_akhir));

        if ($tglAwalBulan == $tglAkhirBulan && $tglAwalTahun == $tglAkhirTahun) {
            $periodeTeks = $tglAwalHari . " s/d " . $tglAkhirHari . " BULAN " . $tglAwalBulan . " TAHUN " . $tglAwalTahun;
        } else {
            $periodeTeks = $tglAwalHari . " " . $tglAwalBulan . " " . $tglAwalTahun . " s/d " . $tglAkhirHari . " " . $tglAkhirBulan . " " . $tglAkhirTahun;
        }

        // Helper Function untuk Mengecek Apakah Tanggal Berada di Luar Rentang Filter
        function isOutsideRange($tgl, $start, $end) {
            if (!$tgl || $tgl == '-') return true;
            $current = strtotime($tgl);
            $min = strtotime($start);
            $max = strtotime($end);
            return ($current < $min || $current > $max);
        }

        // Jika KJP 2, tambahkan hari Sabtu
        $listHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        if ($isKjp2) {
            $listHari[] = 'Sabtu';
        }
        
        $jumlahHari = count($listHari); // 5 hari atau 6 hari
    @endphp

    <div class="judul-rekap">
        REKAPITULASI KEHADIRAN<br>
        JURUSAN AKUNTANSI KELAS {{ $isKjp2 ? 'KJP 2' : 'KJP 1' }}<br>
        POLITEKNIK NEGERI SRIWIJAYA<br>
        DARI TANGGAL {{ $periodeTeks }}
    </div>

    @php $adaData = false; @endphp

    @foreach($rekapMingguan as $minggu)
        @if(count($minggu['data_pegawai']) > 0)
            @php $adaData = true; @endphp
            
            <div class="block-minggu">
                <div class="header-minggu">
                    MINGGU KE-{{ $minggu['minggu_ke'] }}
                </div>

                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 5%;">No</th>
                            <th rowspan="2" style="width: 30%;" class="text-start">Nama Pegawai & NIP</th>
                            <th colspan="{{ $jumlahHari }}" style="width: 55%;">Hari / Tanggal Kerja</th>
                            <th rowspan="2" style="width: 10%;" class="th-total">Total</th>
                        </tr>
                        <tr>
                            @foreach($listHari as $hari)
                                @php
                                    $tglHari = $minggu['tanggal_hari'][$hari] ?? '-';
                                    $isOutside = isOutsideRange($tglHari, $tanggal_awal, $tanggal_akhir);
                                @endphp
                                <th class="{{ $isOutside ? 'bg-outside-range' : '' }}">
                                    {{ $hari }}
                                    <span class="small-date">
                                        {{ $tglHari != '-' ? date('d/m/Y', strtotime($tglHari)) : '-' }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="bg-nomor-kolom">1</th>
                            <th class="bg-nomor-kolom">2</th>
                            @foreach($listHari as $idx => $hari)
                                @php
                                    $tglHari = $minggu['tanggal_hari'][$hari] ?? '-';
                                    $isOutside = isOutsideRange($tglHari, $tanggal_awal, $tanggal_akhir);
                                @endphp
                                <th class="bg-nomor-kolom {{ $isOutside ? 'bg-outside-range' : '' }}">
                                    {{ $idx + 3 }}
                                </th>
                            @endforeach
                            <th class="bg-nomor-kolom">{{ $jumlahHari + 3 }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($minggu['data_pegawai'] as $item)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td class="text-start">
                                    <div class="nama-pegawai">{{ $item['nama'] }}</div>
                                    <div class="nip-pegawai">NIP. {{ $item['nip'] }}</div>
                                </td>

                                @foreach($listHari as $hari)
                                    @php
                                        $tglHari = $minggu['tanggal_hari'][$hari] ?? '-';
                                        $isOutside = isOutsideRange($tglHari, $tanggal_awal, $tanggal_akhir);
                                        $valJam = isset($item[$hari]) ? substr($item[$hari], 0, 5) : '-';
                                    @endphp
                                    <td class="{{ $isOutside ? 'bg-outside-range' : '' }}">
                                        {{ $isOutside ? '' : $valJam }}
                                    </td>
                                @endforeach

                                <td class="td-total">{{ substr($item['total_lembur'], 0, 5) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    @if(!$adaData)
        <div style="text-align: center; margin-top: 50px; font-style: italic; color: #7f8c8d;">
            Tidak ada data aktivitas lembur mingguan dalam rentang tanggal yang dipilih.
        </div>
    @endif
</div>

</body>
</html>