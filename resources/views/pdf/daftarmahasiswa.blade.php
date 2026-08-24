@extends('pdf.kopsuratmpdf')

@section('title', 'Laporan Keadaan Mahasiswa Yudisium')

@section('additional_styles')
<style>
    /* Container Judul Laporan */
    .container-judul {
        width: 100%;
        text-align: center;
        margin-top: 5px;
        margin-bottom: 10px;
    }
    .judul-utama {
        font-size: 11pt;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: underline;
        margin: 0;
        padding: 0;
        text-align: center;
    }
    .sub-judul {
        font-size: 9pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 2px 0 0 0;
        padding: 0;
        text-align: center;
    }

    /* Sub Header Kelas */
    .sub-header-kelas {
        background-color: #f2f2f2;
        border: 1px solid #000;
        padding: 4px 8px;
        font-size: 9pt;
        font-weight: bold;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    /* Tabel Mahasiswa */
    .tabel-mahasiswa {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    .tabel-mahasiswa th, 
    .tabel-mahasiswa td {
        border: 1px solid #000;
        padding: 4px 6px;
        font-size: 8.5pt;
        vertical-align: middle;
    }
    .tabel-mahasiswa th {
        background-color: #e6e6e6;
        text-align: center;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    /* Repeat Header Tabel jika menyambung ke Halaman 2 untuk kelas yang sama */
    .tabel-mahasiswa thead {
        display: table-header-group;
    }
    .tabel-mahasiswa tr {
        page-break-inside: avoid;
    }

    /* Helper Utilities */
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-bold { font-weight: bold; }
    .text-danger { color: #d9534f; font-weight: bold; }
</style>
@endsection

@section('content')

@php
    // 1. Sortir Program (D3 dulu baru D4)
    $sortedData = $dataMahasiswa->sortBy(function($item) {
        $prodi = strtoupper($item->namaProdi ?? $item->prodi ?? '');
        if (str_contains($prodi, 'D3') || str_contains($prodi, 'D-III')) {
            return '1_' . $prodi . '_' . $item->kelas;
        }
        return '2_' . $prodi . '_' . $item->kelas;
    });

    // 2. Grouping per KELAS
    $groupedKelas = $sortedData->groupBy('kelas');
@endphp

@forelse($groupedKelas as $namaKelas => $listMahasiswa)

    <div style="{{ !$loop->first ? 'page-break-before: always;' : '' }}">

        {{-- KOP SURAT --}}
        <div class="kop-container">
            <table class="kop-table">
                <tr>
                    <td class="kop-logo">
                        @php $logoPath = public_path('img/logo-polsri.png'); @endphp
                        @if(file_exists($logoPath))
                            <img src="{{ $logoPath }}" width="65" height="65" alt="Logo Polsri">
                        @endif
                    </td>
                    <td class="kop-text">
                        <div class="kop-instansi">KEMENTERIAN PENDIDIKAN TINGGI, SAINS,</div>
                        <div class="kop-instansi">DAN TEKNOLOGI</div>
                        <div class="kop-instansi">POLITEKNIK NEGERI SRIWIJAYA</div>
                        <div class="kop-instansi kop-jurusan">JURUSAN AKUNTANSI</div>
                        <div class="kop-alamat">
                            Jalan Srijaya Negara Bukit Besar - Palembang 30139 Telepon (0711) 353414<br>
                            Laman : http://polsri.ac.id, Pos El : info@polsri.ac.id
                        </div>
                    </td>
                </tr>
            </table>
            <div class="kop-garis"></div>
            <div class="kop-garis-tipis"></div>
        </div>

        {{-- JUDUL LAPORAN --}}
        <div class="container-judul">
            <div class="judul-utama">LAPORAN KEADAAN MAHASISWA YUDISIUM</div>
            <div class="sub-judul">JURUSAN AKUNTANSI</div>
            <div class="sub-judul">
                Tahun Akademik: {{ $taAktif->ta_aktif ?? '-' }} - {{ $taAktif->semester_aktif ?? '-' }}
            </div>
        </div>

        @php
            $firstItem = $listMahasiswa->first();
            $namaProgram = $firstItem->namaProdi ?? $firstItem->prodi ?? '-';
        @endphp

        {{-- INFO KELAS --}}
        <div class="sub-header-kelas">
            PROGRAM: {{ $namaProgram }} &nbsp;|&nbsp; KELAS: {{ $namaKelas }}
        </div>

        {{-- TABEL MAHASISWA --}}
        <table class="tabel-mahasiswa">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="18%">NPM</th>
                    <th>Nama Mahasiswa</th>
                    <th width="10%">Kelas</th>
                    <th width="12%">Thn Masuk</th>
                    <th width="18%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($listMahasiswa as $index => $mhs)
                    @php
                        $isAktif = ($mhs->keterangan === 'A');
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        
                        {{-- Kolom NPM --}}
                        <td class="text-center" style="font-family: monospace; font-weight: bold;">
                            {{ $mhs->npm }}
                        </td>
                        
                        {{-- Kolom Nama Mahasiswa --}}
                        <td class="text-left">
                            {{ $mhs->nama }}
                        </td>
                        
                        <td class="text-center">{{ $mhs->kelas }}</td>
                        <td class="text-center">{{ $mhs->tahunMasuk }}</td>
                        
                        <td class="text-center">
                            @if($isAktif)
                                <span class="text-bold">Aktif</span>
                            @else
                                <span class="text-danger">Non Aktif</span>
                                @if(!empty($mhs->statusKeterangan))
                                    <br><small class="text-danger" style="font-size: 7.5pt; font-style: italic;">({{ $mhs->statusKeterangan }})</small>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
@empty
    <div style="text-align: center; padding: 30px; border: 1px solid #000; margin-top: 20px;">
        <b>Tidak ada data mahasiswa yudisium untuk periode akademik ini.</b>
    </div>
@endforelse
@include('pdf.ttd')
@endsection