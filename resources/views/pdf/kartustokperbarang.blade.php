<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok Barang - {{ $barang->namaBarang ?? 'Barang' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.2cm 1.5cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 9pt;
        }
        .table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .meta-info {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .meta-info td {
            font-size: 10pt;
            padding: 2px 0;
        }
    </style>
</head>
<body>

    {{-- KOP / HEADER LAPORAN --}}
    @include('pdf.kopsuratdompdf')

    <div class="text-center" style="margin-top: 10px;">
        <h3 style="margin:0; text-transform:uppercase;">KARTU STOK BARANG / RIWAYAT ARUS STOK</h3>
        <p style="margin:2px 0 15px 0; font-size:10pt;">Jurusan Akuntansi - Politeknik Negeri Sriwijaya</p>
    </div>

    {{-- INFORMASI BARANG --}}
    <table class="meta-info">
        <tr>
            <td style="width: 18%;"><strong>Nama Barang</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">{{ $barang->namaBarang ?? '-' }}</td>
            <td style="width: 18%;"><strong>Satuan</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">{{ $barang->satuan ?? 'Pcs' }}</td>
        </tr>
        <tr>
            <td><strong>Merk / Brand</strong></td>
            <td>:</td>
            <td>{{ $barang->merkBarang ?? '-' }}</td>
            <td><strong>Spesifikasi</strong></td>
            <td>:</td>
            <td>{{ $barang->spesifikasi ?? '-' }}</td>
        </tr>
    </table>

    {{-- TABEL RIWAYAT TRANSAKSI --}}
    <table class="table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 8%;">Tipe</th>
                <th style="width: 20%;">Pihak Terkait / Supplier</th>
                <th style="width: 20%;">Penerima / Petugas</th>
                <th style="width: 8%;">Masuk</th>
                <th style="width: 8%;">Keluar</th>
                <th style="width: 8%;">Saldo</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $saldo = 0; 
                $no = 1;
            @endphp

            @forelse($riwayatTransaksi as $row)
                @php
                    $masuk = (int) $row->masuk;
                    $keluar = (int) $row->keluar;
                    $saldo += ($masuk - $keluar);
                @endphp
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y H:i') }}</td>
                    <td class="text-center">
                        <strong>{{ $row->tipe }}</strong>
                    </td>
                    <td>{{ $row->pihak_terkait ?? '-' }}</td>
                    <td>{{ $row->penerima_petugas ?? '-' }}</td>
                    <td class="text-right">{{ $masuk > 0 ? number_format($masuk) : '-' }}</td>
                    <td class="text-right">{{ $keluar > 0 ? number_format($keluar) : '-' }}</td>
                    <td class="text-right fw-bold">{{ number_format($saldo) }}</td>
                    <td>{{ $row->catatan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px;">
                        Belum ada riwayat transaksi masuk atau keluar untuk barang ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@include('pdf.ttd')
</body>
</html>