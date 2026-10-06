<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Stok Opname Seluruh</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.2cm 1.5cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            line-height: 1.2;
            color: #000;
        }

        .title-section {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 12px;
        }
        .title-section h4 {
            margin: 0;
            font-size: 11pt;
            text-transform: uppercase;
            text-decoration: underline;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #e0e0e0;
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
        }

        .text-center { text-align: center; }
        .bg-master { background-color: #d9edf7; font-weight: bold; font-size: 9pt; }
        .bg-anak { background-color: #f5f5f5; font-weight: bold; }

        table.nested-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            background-color: #ffffff;
        }
        table.nested-table th, table.nested-table td {
            border: 1px solid #aaa;
            padding: 2px 4px;
            font-size: 7.5pt;
        }
        table.nested-table th { background-color: #eee; }

        .badge-masuk { color: #006600; font-weight: bold; }
        .badge-keluar { color: #cc0000; font-weight: bold; }
    </style>
</head>
<body>

    <!-- 1. KOP SURAT -->
    @include('pdf.kopsuratdompdf')

    <!-- 2. JUDUL LAPORAN -->
    <div class="title-section">
        <h4>LAPORAN REKAPITULASI STOK OPNAME & SIRKULASI ARUS BARANG</h4>
        @if(!empty($tgl_mulai) && !empty($tgl_selesai))
            <p style="font-size: 8.5pt; margin-top: 3px;">Periode: {{ \Carbon\Carbon::parse($tgl_mulai)->locale('id')->isoFormat('D MMMM Y') }} s/d {{ \Carbon\Carbon::parse($tgl_selesai)->locale('id')->isoFormat('D MMMM Y') }}</p>
        @endif
    </div>

    <!-- 3. TABEL DATA -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th>Nama / Merk & Spesifikasi Barang / Detail Arus Transaksi</th>
                <th style="width: 7%;">Satuan</th>
                <th style="width: 9%;">Total Masuk</th>
                <th style="width: 9%;">Total Keluar</th>
                <th style="width: 9%;">Stok Akhir</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $no = 1; 
                $listBuku = $bukuBesar ?? $data['bukuBesar'] ?? [];
            @endphp

            @forelse ($listBuku as $row)
                <tr class="bg-master">
                    <td class="text-center">{{ $no++ }}</td>
                    <td colspan="5">MASTER BARANG: {{ $row->master->namaBarang }}</td>
                </tr>

                @if(isset($row->items) && count($row->items) > 0)
                    @foreach ($row->items as $item)
                        <tr class="bg-anak">
                            <td></td>
                            <td>
                                <strong>&bull; {{ $item->merkBarang }}</strong> 
                                {{ $item->spesifikasi ? '('.$item->spesifikasi.')' : '' }}
                            </td>
                            <td class="text-center">{{ $item->namaSatuan }}</td>
                            <td class="text-center badge-masuk">{{ number_format($item->totalMasuk, 0, ',', '.') }}</td>
                            <td class="text-center badge-keluar">{{ number_format($item->totalKeluar, 0, ',', '.') }}</td>
                            <td class="text-center"><strong>{{ number_format($item->stokAkhir, 0, ',', '.') }}</strong></td>
                        </tr>

                        <tr>
                            <td></td>
                            <td colspan="5" style="padding: 4px 8px 8px 15px;">
                                <div style="font-weight: bold; margin-bottom: 2px; text-decoration: underline;">Detail Arus Transaksi Barang:</div>
                                
                                <table class="nested-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 8%;">Jenis</th>
                                            <th style="width: 12%;">Tanggal</th>
                                            <th style="width: 25%;">Supplier / Petugas</th>
                                            <th style="width: 25%;">Penerima</th>
                                            <th style="width: 10%;">Jumlah</th>
                                            <th>Keterangan / Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($item->barangMasuk) && count($item->barangMasuk) > 0)
                                            @foreach($item->barangMasuk as $bm)
                                                <tr>
                                                    <td class="text-center badge-masuk">MASUK</td>
                                                    <td class="text-center">{{ \Carbon\Carbon::parse($bm->tanggal)->isoFormat('DD/MM/YYYY') }}</td>
                                                    <td>{{ $bm->supplier ?? '-' }}</td>
                                                    <td>{{ $bm->penerima ?? '-' }}</td>
                                                    <td class="text-center">+{{ number_format($bm->jumlah, 0, ',', '.') }}</td>
                                                    <td>{{ $bm->keterangan ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        @endif

                                        @if(isset($item->barangKeluar) && count($item->barangKeluar) > 0)
                                            @foreach($item->barangKeluar as $bk)
                                                <tr>
                                                    <td class="text-center badge-keluar">KELUAR</td>
                                                    <td class="text-center">{{ \Carbon\Carbon::parse($bk->tanggal)->isoFormat('DD/MM/YYYY') }}</td>
                                                    <td>{{ $bk->petugas ?? '-' }}</td>
                                                    <td>{{ $bk->penerima ?? '-' }}</td>
                                                    <td class="text-center">-{{ number_format($bk->jumlah, 0, ',', '.') }}</td>
                                                    <td>{{ $bk->keterangan ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        @endif

                                        @if(count($item->barangMasuk) == 0 && count($item->barangKeluar) == 0)
                                            <tr>
                                                <td colspan="6" class="text-center" style="font-style: italic; color: #888;">
                                                    Belum ada histori arus transaksi barang masuk / keluar pada periode ini.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td></td>
                        <td colspan="5" style="font-style: italic; color: #777;">(Belum ada rincian anak barang)</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 10px;">
                        <em>Tidak ada data rekap stok opname.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 4. PANGGIL TANDA TANGAN (DIKELOLA UNIVERSAL) -->
    @include('pdf.ttd')

</body>
</html>