@extends('pdf.kopsuratmpdf')

@section('title', 'Bukti Serah Terima Ijazah')

@section('additional_styles')
<style>
    /* Judul Dokumen - Dipastikan 100% Width & Center */
    .title-doc {
        text-align: center;
        font-weight: bold;
        font-size: 12pt;
        text-transform: uppercase;
        margin-bottom: 15px;
        margin-top: 10px;
        width: 100%;
    }

    /* Informasi Metadata Kelas & TA */
    .meta-table {
        width: 100%;
        margin-bottom: 10px;
        font-size: 10pt;
    }
    .meta-table td {
        padding: 2px 0;
        vertical-align: top;
    }

    /* Tabel Data Mahasiswa */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 9.5pt;
    }
    .data-table th, .data-table td {
        border: 1px solid #000;
        padding: 5px 4px;
    }
    .data-table th {
        background-color: #f2f2f2;
        text-align: center;
        font-weight: bold;
        vertical-align: middle;
    }
    .text-center { 
        text-align: center; 
    }

    /* Cegah teks melipat ke baris baru */
    .nowrap {
        white-space: nowrap;
    }

    /* Area Tanda Tangan */
    .ttd-table {
        width: 100%;
        margin-top: 20px;
        font-size: 10pt;
    }
    .ttd-table td {
        vertical-align: top;
    }
</style>
@endsection

@section('content')

    <!-- Judul Dokumen (Atribut align="center" dan style width 100%) -->
    <div class="title-doc" align="center" style="text-align: center; width: 100%;">
        <u>DAFTAR SERAH TERIMA IJAZAH & TRANSKRIP NILAI</u>
    </div>

    @foreach($mahasiswaGrouped as $kelas => $listMahasiswa)
    <!-- Metadata Kelas & Tahun Akademik -->
    <table class="meta-table">
        <tr>
            <td width="12%"><strong>Kelas</strong></td>
            <td width="2%">:</td>
            <td width="46%">{{ $kelas }}</td>
            <td width="18%"><strong>Tahun Akademik</strong></td>
            <td width="2%">:</td>
            <td width="20%">{{ $ta }}</td>
        </tr>
    </table>

    <!-- Tabel Mahasiswa -->
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" width="5%" class="nowrap">No</th>
                <th rowspan="2" width="16%" class="nowrap">NPM</th>
                <th rowspan="2" width="23%">Nama Mahasiswa</th>
                <th rowspan="2" width="14%">No. Seri Ijazah</th>
                <th rowspan="2" width="12%">Tgl Terima</th>
                <th colspan="2" width="30%">Tanda Tangan Terima</th>
            </tr>
            <tr>
                <th width="15%">Ijazah Asli</th>
                <th width="15%">Transkrip Asli</th>
            </tr>
        </thead>
        <tbody>
            @forelse($listMahasiswa as $index => $mhs)
            <tr>
                <td class="text-center nowrap">{{ $index + 1 }}</td>
                <td class="text-center nowrap">{{ $mhs->npm }}</td>
                <td>{{ $mhs->nama ?? $mhs->namaMahasiswa ?? '-' }}</td>
                <td></td>
                <td></td>
                <td style="height: 28px; vertical-align: bottom;">
                    <span style="font-size: 7.5pt; color: #666;">{{ $index + 1 }}.</span>
                </td>
                <td style="height: 28px; vertical-align: bottom;">
                    <span style="font-size: 7.5pt; color: #666;">{{ $index + 1 }}.</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Data mahasiswa tidak ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @endforeach

@endsection