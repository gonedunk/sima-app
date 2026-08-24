@extends('pdf.kopsuratmpdf')

@section('title', 'Rekapitulasi Mahasiswa Per Kelas & Semester')

@section('additional_styles')
<style>
    .header-title {
        width: 100%;
        text-align: center;
        margin-top: 10px;
        margin-bottom: 15px;
    }
    .header-title h2 {
        margin: 0;
        padding: 0;
        font-size: 11pt;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: underline;
        text-align: center;
    }
    .header-title p {
        margin: 4px 0 0 0;
        padding: 0;
        font-size: 9pt;
        font-weight: bold;
        text-transform: uppercase;
        text-align: center;
    }

    .meta-info {
        width: 100%;
        margin-bottom: 10px;
        border-collapse: collapse;
    }
    .meta-info td {
        font-size: 8.5pt;
        border: none;
        padding: 0;
    }

    .section-title {
        font-size: 10pt;
        font-weight: bold;
        text-transform: uppercase;
        margin-top: 12px;
        margin-bottom: 6px;
        background-color: #f0f0f0;
        padding: 4px;
        border: 1px solid #000;
    }

    .sub-section-title {
        font-size: 9pt;
        font-weight: bold;
        text-transform: uppercase;
        margin-top: 10px;
        margin-bottom: 4px;
    }

    .prodi-title {
        font-size: 9pt;
        font-weight: bold;
        text-transform: uppercase;
        margin-top: 8px;
        margin-bottom: 4px;
        padding-bottom: 2px;
        border-bottom: 1px solid #000;
    }

    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    table.data-table th, 
    table.data-table td {
        border: 1px solid #000;
        padding: 4px 6px;
        font-size: 8.5pt;
        text-align: center;
        vertical-align: middle;
    }
    table.data-table th {
        background-color: #e6e6e6;
        font-weight: bold;
        text-transform: uppercase;
    }
    table.data-table td.text-left {
        text-align: left;
    }
    table.data-table tr {
        page-break-inside: avoid;
    }
</style>
@endsection

@section('content')

    {{-- ==================== HALAMAN 1: D3 - REKAP PER KELAS ==================== --}}
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
    <div class="header-title">
        <h2>REKAPITULASI JUMLAH MAHASISWA PER KELAS & SEMESTER</h2>
        <p>
            TAHUN AKADEMIK: {{ $taAktif->ta_aktif ?? '-' }} 
            @if(!empty($taAktif->semester_aktif))
                - {{ strtoupper($taAktif->semester_aktif) }}
            @endif
        </p>
    </div>

    <table class="meta-info">
        <tr>
            <td style="width: 50%;">
                <strong>Dicetak Oleh:</strong> 
                {{ $userLogin->nama ?? $userLogin->name ?? 'Administrator' }}
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>Tanggal Cetak:</strong> 
                {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB
            </td>
        </tr>
    </table>

    <div class="section-title">PROGRAM DIPLOMA III (D3)</div>
    
    {{-- 1. Rekap Per Kelas D3 --}}
    <div class="sub-section-title">1. REKAPITULASI PER KELAS</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="35%">KELAS</th>
                <th width="20%">JUMLAH AWAL SEMESTER</th>
                <th width="20%">JUMLAH TIDAK AKTIF</th>
                <th width="20%">JUMLAH AKHIR SEMESTER (AKTIF)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandAwalD3 = $grandTidakAktifD3 = $grandAkhirD3 = 0;
            @endphp
            @forelse ($dataD3 as $index => $row)
                @php
                    $grandAwalD3       += $row->awal_semester;
                    $grandTidakAktifD3 += $row->tidak_aktif;
                    $grandAkhirD3      += $row->akhir_semester;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $row->kelas }}</td>
                    <td>{{ $row->awal_semester }}</td>
                    <td>{{ $row->tidak_aktif }}</td>
                    <td>{{ $row->akhir_semester }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Data Mahasiswa D3 Tidak Ditemukan.</td>
                </tr>
            @endforelse

            @if ($dataD3->isNotEmpty())
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="2" style="text-align: right;">TOTAL D3:</td>
                    <td>{{ $grandAwalD3 }}</td>
                    <td>{{ $grandTidakAktifD3 }}</td>
                    <td>{{ $grandAkhirD3 }}</td>
                </tr>
            @endif
        </tbody>
    </table>


    {{-- ==================== HALAMAN 2: D3 - REKAP SEMESTER & DETAIL NON AKTIF ==================== --}}
    <pagebreak />

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

    <div class="section-title">PROGRAM DIPLOMA III (D3) - REKAPITULASI SEMESTER</div>

    {{-- 2. Rekap Per Semester D3 --}}
    <div class="sub-section-title">2. REKAPITULASI PER SEMESTER</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="35%">SEMESTER</th>
                <th width="20%">JUMLAH MHS AWAL</th>
                <th width="20%">JUMLAH MHS NON AKTIF</th>
                <th width="20%">TOTAL JUMLAH MAHASISWA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totAwalSmtD3 = $totNonAktifSmtD3 = $totTotalSmtD3 = 0;
                $semestersD3 = [2, 4, 6];
            @endphp
            @foreach ($semestersD3 as $idx => $smt)
                @php
                    $itemsSmt = $dataD3->filter(function($item) use ($smt) {
                        return (int)($item->semester ?? 0) === $smt;
                    });
                    
                    $awalSmt = $itemsSmt->sum('awal_semester');
                    $nonAktifSmt = $itemsSmt->sum('tidak_aktif');
                    $totalSmt = $awalSmt - $nonAktifSmt;

                    $totAwalSmtD3 += $awalSmt;
                    $totNonAktifSmtD3 += $nonAktifSmt;
                    $totTotalSmtD3 += $totalSmt;
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="text-left">SEMESTER {{ $smt }}</td>
                    <td>{{ $awalSmt }}</td>
                    <td>{{ $nonAktifSmt }}</td>
                    <td>{{ $totalSmt }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="2" style="text-align: right;">TOTAL:</td>
                <td>{{ $totAwalSmtD3 }}</td>
                <td>{{ $totNonAktifSmtD3 }}</td>
                <td>{{ $totTotalSmtD3 }}</td>
            </tr>
        </tbody>
    </table>

    {{-- 3. Detail Mahasiswa Non-Aktif D3 --}}
    <div class="sub-section-title">3. DAFTAR MAHASISWA NON AKTIF D3</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="20%">NPM / NIM</th>
                <th width="40%">NAMA MAHASISWA</th>
                <th width="35%">STATUS / KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mhsNonAktifD3 ?? [] as $idx => $mhs)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $mhs->npm ?? $mhs->nim ?? '-' }}</td>
                    <td class="text-left">{{ $mhs->nama }}</td>
                    <td class="text-left">{{ $mhs->statusKeterangan ?? $mhs->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Tidak ada mahasiswa non-aktif pada program D3.</td>
                </tr>
            @endforelse
        </tbody>
    </table>


    {{-- ==================== HALAMAN 3: D4 - REKAP PER KELAS (PER PRODI) ==================== --}}
    <pagebreak />

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

    <div class="header-title">
        <h2>REKAPITULASI JUMLAH MAHASISWA PER KELAS & SEMESTER</h2>
        <p>
            TAHUN AKADEMIK: {{ $taAktif->ta_aktif ?? '-' }} 
            @if(!empty($taAktif->semester_aktif))
                - {{ strtoupper($taAktif->semester_aktif) }}
            @endif
        </p>
    </div>

    <table class="meta-info">
        <tr>
            <td style="width: 50%;">
                <strong>Dicetak Oleh:</strong> 
                {{ $userLogin->nama ?? $userLogin->name ?? 'Administrator' }}
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>Tanggal Cetak:</strong> 
                {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB
            </td>
        </tr>
    </table>

    <div class="section-title">PROGRAM DIPLOMA IV (D4) / SARJANA TERAPAN</div>
    
    @php
        // Grouping Data D4 berdasarkan nama Program Studi
        $groupedD4 = $dataD4->groupBy(function($item) {
            return $item->prodi ?? $item->namaProdi ?? 'D4';
        });
    @endphp

    {{-- 1. Rekap Per Kelas D4 (Dipecah per Prodi) --}}
    <div class="sub-section-title">1. REKAPITULASI PER KELAS</div>

    @forelse ($groupedD4 as $namaProdi => $rowsProdi)
        <div class="prodi-title">PROGRAM STUDI: {{ strtoupper($namaProdi) }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="35%">KELAS</th>
                    <th width="20%">JUMLAH AWAL SEMESTER</th>
                    <th width="20%">JUMLAH TIDAK AKTIF</th>
                    <th width="20%">JUMLAH AKHIR SEMESTER (AKTIF)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subAwalD4 = $subTidakAktifD4 = $subAkhirD4 = 0;
                @endphp
                @foreach ($rowsProdi as $index => $row)
                    @php
                        $subAwalD4       += $row->awal_semester;
                        $subTidakAktifD4 += $row->tidak_aktif;
                        $subAkhirD4      += $row->akhir_semester;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $row->kelas }}</td>
                        <td>{{ $row->awal_semester }}</td>
                        <td>{{ $row->tidak_aktif }}</td>
                        <td>{{ $row->akhir_semester }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="2" style="text-align: right;">TOTAL {{ strtoupper($namaProdi) }}:</td>
                    <td>{{ $subAwalD4 }}</td>
                    <td>{{ $subTidakAktifD4 }}</td>
                    <td>{{ $subAkhirD4 }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="data-table">
            <tbody>
                <tr>
                    <td colspan="5">Data Mahasiswa D4 Tidak Ditemukan.</td>
                </tr>
            </tbody>
        </table>
    @endforelse


    {{-- ==================== HALAMAN 4: D4 - REKAP SEMESTER & DETAIL NON AKTIF (PER PRODI) ==================== --}}
    <pagebreak />

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

    <div class="section-title">PROGRAM DIPLOMA IV (D4) - REKAPITULASI SEMESTER & DETAIL NON AKTIF</div>

    {{-- 2. Rekap Per Semester D4 (Dipecah per Prodi) --}}
    <div class="sub-section-title">2. REKAPITULASI PER SEMESTER</div>

    @forelse ($groupedD4 as $namaProdi => $rowsProdi)
        <div class="prodi-title">PROGRAM STUDI: {{ strtoupper($namaProdi) }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="35%">SEMESTER</th>
                    <th width="20%">JUMLAH MHS AWAL</th>
                    <th width="20%">JUMLAH MHS NON AKTIF</th>
                    <th width="20%">TOTAL JUMLAH MAHASISWA</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totAwalSmtProdi = $totNonAktifSmtProdi = $totTotalSmtProdi = 0;
                    $semestersD4 = [2, 4, 6, 8];
                @endphp
                @foreach ($semestersD4 as $idx => $smt)
                    @php
                        $itemsSmt = $rowsProdi->filter(function($item) use ($smt) {
                            return (int)($item->semester ?? 0) === $smt;
                        });
                        
                        $awalSmt = $itemsSmt->sum('awal_semester');
                        $nonAktifSmt = $itemsSmt->sum('tidak_aktif');
                        $totalSmt = $awalSmt - $nonAktifSmt;

                        $totAwalSmtProdi += $awalSmt;
                        $totNonAktifSmtProdi += $nonAktifSmt;
                        $totTotalSmtProdi += $totalSmt;
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="text-left">SEMESTER {{ $smt }}</td>
                        <td>{{ $awalSmt }}</td>
                        <td>{{ $nonAktifSmt }}</td>
                        <td>{{ $totalSmt }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="2" style="text-align: right;">TOTAL {{ strtoupper($namaProdi) }}:</td>
                    <td>{{ $totAwalSmtProdi }}</td>
                    <td>{{ $totNonAktifSmtProdi }}</td>
                    <td>{{ $totTotalSmtProdi }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p style="font-size: 8.5pt;">Data Rekap Semester D4 tidak ditemukan.</p>
    @endforelse

    {{-- 3. Detail Mahasiswa Non-Aktif D4 (Dipecah per Prodi) --}}
    <div class="sub-section-title">3. DAFTAR MAHASISWA NON AKTIF D4</div>

    @php
        $mhsNonAktifD4Grouped = collect($mhsNonAktifD4 ?? [])->groupBy(function($item) {
            return $item->prodi ?? $item->namaProdi ?? 'D4';
        });
    @endphp

    @forelse ($mhsNonAktifD4Grouped as $namaProdi => $listMhs)
        <div class="prodi-title">PROGRAM STUDI: {{ strtoupper($namaProdi) }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="20%">NPM / NIM</th>
                    <th width="40%">NAMA MAHASISWA</th>
                    <th width="35%">STATUS / KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($listMhs as $idx => $mhs)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $mhs->npm ?? $mhs->nim ?? '-' }}</td>
                        <td class="text-left">{{ $mhs->nama }}</td>
                        <td class="text-left">{{ $mhs->statusKeterangan ?? $mhs->keterangan ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="20%">NPM / NIM</th>
                    <th width="40%">NAMA MAHASISWA</th>
                    <th width="35%">STATUS / KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4">Tidak ada mahasiswa non-aktif pada program D4.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    {{-- Tanda Tangan --}}
    @include('pdf.ttd')

@endsection