@extends('layouts.app')

@section('title', 'Rekap Lembur Tendik - Jurusan Akuntansi')

@section('additional_styles')
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
@endsection

@section('content')
<div class="container-fluid px-4 py-3">
    
    <!-- HEADER HALAMAN & KONTROL UTAMA -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Rekapitulasi Jam Kerja Lembur</h4>
            <p class="text-muted small mb-0">Jurusan Akuntansi - Politeknik Negeri Sriwijaya</p>
        </div>
        <div>
            @php
                // Parameter bersih khusus URL Cetak PDF agar KJP2 tidak mencemari Jam Normal
                $pdfParams = [
                    'tanggal_awal' => $tanggal_awal,
                    'tanggal_akhir' => $tanggal_akhir,
                    'jenis_jam' => request('jenis_jam', 'normal')
                ];
                if (request('jenis_jam') === 'kjp2' && is_array(request('nip_kjp2'))) {
                    $pdfParams['nip_kjp2'] = request('nip_kjp2');
                }
                $queryStringPdf = http_build_query($pdfParams);
            @endphp

            <a href="{{ url('admin/tandatangan?'.$queryStringPdf) }}" 
               class="btn btn-danger btn-sm rounded-3 fw-bold shadow-sm" 
               target="_blank">
                <i class="fa-solid fa-file-pdf me-1"></i> Cetak Rekap Bulanan
            </a>
            
            <a href="{{ url('admin/rekaplembur/cetak-mingguan?'.$queryStringPdf) }}" 
               class="btn btn-outline-danger btn-sm rounded-3 fw-bold shadow-sm" 
               target="_blank">
                <i class="fa-solid fa-file-pdf me-1"></i> Cetak Rekap Mingguan
            </a>
            <button type="button" class="btn btn-warning btn-sm rounded-3 fw-bold text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalArsipkan">
                <i class="fa-solid fa-box-archive me-1"></i> Arsipkan Periode Ini
            </button>
        </div>
    </div>

    <!-- FORM FILTER PARAMETER PERIODE & JENIS JAM -->
    <form method="GET" action="{{ url('admin/rekaplembur') }}" id="formFilterLembur">
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 text-warning-emphasis px-3 py-2 rounded-3 me-3">
                                    <i class="fa-solid fa-filter fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Filter Periode & Opsi Jam Lembur</h6>
                                    <small class="text-muted">Pilih mode kalkulasi lembur (Normal / KJP2 Aturan NK & KS) dan rentang tanggal.</small>
                                </div>
                            </div>
                            
                            <!-- PILIHAN RADIO BUTTON JAM NORMAL / KJP2 -->
                            <div class="bg-light p-2 rounded-3 border d-flex gap-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input radio-jam" type="radio" name="jenis_jam" id="jamNormal" value="normal" {{ request('jenis_jam', 'normal') == 'normal' ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-dark" for="jamNormal">
                                        <i class="fa-regular fa-clock me-1 text-primary"></i> Jam Normal
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input radio-jam" type="radio" name="jenis_jam" id="jamKjp2" value="kjp2" {{ request('jenis_jam') == 'kjp2' ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-dark" for="jamKjp2">
                                        <i class="fa-solid fa-user-gear me-1 text-danger"></i> Jam KJP2 (NK & KS)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3 align-items-end border-top pt-3">
                            <div class="col-12 col-sm-4">
                                <label class="form-label small fw-semibold text-secondary">Tanggal Awal</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-regular fa-calendar"></i></span>
                                    <input type="date" name="tanggal_awal" class="form-control rounded-end-3 border-start-0" value="{{ $tanggal_awal }}" required>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="form-label small fw-semibold text-secondary">Tanggal Akhir</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-regular fa-calendar"></i></span>
                                    <input type="date" name="tanggal_akhir" class="form-control rounded-end-3 border-start-0" value="{{ $tanggal_akhir }}" required>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4 d-grid">
                                <button type="submit" class="btn btn-warning btn-sm rounded-3 fw-bold text-dark shadow-sm">
                                    <i class="fa-solid fa-sync me-1"></i> Terapkan & Perbarui Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL 1: TOTAL AKUMULASI GLOBAL PEGAWAI + CHECKBOX KJP2 -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-0">
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="fa-solid fa-users-gear me-1 text-warning"></i> 
                        Tabel 1: Akumulasi Global Total Jam Lembur Pegawai
                    </span>
                    
                    @if(request('jenis_jam') == 'kjp2')
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger text-white px-2 py-1 small">
                                <i class="fa-solid fa-filter me-1"></i> Mode KJP2 Aktif (Aturan NK & KS)
                            </span>
                            <small class="text-muted border-start ps-2">(Centang pegawai untuk memfilter Tabel 2)</small>
                        </div>
                    @else
                        <span class="badge bg-primary text-white px-2 py-1 small">
                            <i class="fa-regular fa-clock me-1"></i> Mode Jam Normal (Senin - Jumat)
                        </span>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-secondary fw-semibold">
                            <tr>
                                <th width="5%">
                                    @if(request('jenis_jam') == 'kjp2')
                                        <input type="checkbox" id="checkAllKjp2" class="form-check-input" title="Pilih Semua KJP2">
                                    @else
                                        No
                                    @endif
                                </th>
                                <th class="text-start" width="30%">Nama Pegawai & NIP</th>
                                <th width="20%">Total Hari Kerja Lembur</th>
                                <th width="20%">Estimasi Total Minggu</th>
                                <th width="25%" class="table-dark text-white">Akumulasi Jam</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($dataLembur) && count($dataLembur) > 0)
                                @php 
                                    $noGlobal = 1; 
                                    $nipSelected = (array) request('nip_kjp2', []);
                                @endphp
                                @foreach($dataLembur as $row)
                                <tr>
                                    <td>
                                        @if(request('jenis_jam') == 'kjp2')
                                            <input type="checkbox" 
                                                   name="nip_kjp2[]" 
                                                   value="{{ $row->nip }}" 
                                                   class="form-check-input checkbox-kjp2"
                                                   {{ in_array($row->nip, $nipSelected) ? 'checked' : '' }}>
                                        @else
                                            {{ $noGlobal++ }}
                                        @endif
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark mb-0">{{ $row->namaPegawai }}</div>
                                        <small class="text-muted font-monospace">NIP. {{ $row->nip }}</small>
                                    </td>
                                    <td>
                                        @if($row->total_hari > 0 && substr($row->total_jam, 0, 5) != '00:00')
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5 fw-semibold">
                                                {{ $row->total_hari }} Hari
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border px-2.5 py-1.5 fw-semibold">
                                                0 Hari
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $row->total_minggu }} Minggu</td>
                                    <td class="fw-bold text-success bg-success bg-opacity-10 fs-6">
                                        {{ substr($row->total_jam, 0, 5) }}
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Data akumulasi global kosong pada rentang tanggal/kategori ini.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>

    <!-- TABEL 2: PRATINJAU DISTRIBUSI MINGGUAN DINAMIS -->
    <div class="card shadow mb-4">
        <div class="p-4 border-bottom bg-white rounded-top">
            @include('pdf.kopsuratdompdf')
        </div>
        
        <div class="card-body">
            @php 
                $adaDataMingguan = false; 
                $isKjp2 = (request('jenis_jam') == 'kjp2');
                $selectedNipKjp2 = (array) request('nip_kjp2', []);
            @endphp
            
            @if(isset($rekapMingguan) && count($rekapMingguan) > 0)
                @foreach($rekapMingguan as $minggu)
                    @php
                        // Filter Pegawai Jika Mode KJP2 Aktif
                        $filteredPegawai = array_filter($minggu['data_pegawai'], function($item) use ($isKjp2, $selectedNipKjp2) {
                            if ($isKjp2) {
                                $isNipChecked = in_array($item['nip'], $selectedNipKjp2);
                                $hasLemburVal = isset($item['total_lembur']) && substr($item['total_lembur'], 0, 5) !== '00:00';
                                return $isNipChecked && $hasLemburVal;
                            }
                            return true;
                        });
                    @endphp

                    @if(count($filteredPegawai) > 0)
                        @php $adaDataMingguan = true; @endphp
                        
                        <div class="mb-3 mt-4 px-2">
                            <h6 class="m-0 font-weight-bold text-dark text-uppercase">
                                <i class="fas fa-calendar-week text-primary mr-2"></i> 
                                MINGGU KE-{{ $minggu['minggu_ke'] }} ({{ $minggu['label_periode'] }})
                            </h6>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle text-center small mb-0" width="100%" cellspacing="0" style="border: 2px solid #bdc3c7;">
                                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #bdc3c7;">
                                    <tr>
                                        <th style="width: 5%; border: 1px solid #bdc3c7;">No</th>
                                        <th style="width: {{ $isKjp2 ? '33%' : '40%' }}; border: 1px solid #bdc3c7;" class="text-start">Nama Pegawai & NIP</th>
                                        <th style="width: 8%; border: 1px solid #bdc3c7;">Senin</th>
                                        <th style="width: 8%; border: 1px solid #bdc3c7;">Selasa</th>
                                        <th style="width: 8%; border: 1px solid #bdc3c7;">Rabu</th>
                                        <th style="width: 8%; border: 1px solid #bdc3c7;">Kamis</th>
                                        <th style="width: 8%; border: 1px solid #bdc3c7;">Jumat</th>
                                        @if($isKjp2)
                                            <th style="width: 8%; border: 1px solid #bdc3c7;" class="bg-warning bg-opacity-10 text-dark fw-bold">Sabtu</th>
                                        @endif
                                        <th style="width: 9%; border: 1px solid #bdc3c7;" class="table-dark text-white">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $noDosen = 1; @endphp
                                    @foreach($filteredPegawai as $item)
                                        <tr style="border-bottom: 1px solid #bdc3c7;">
                                            <td style="border: 1px solid #bdc3c7;">{{ $noDosen++ }}</td>
                                            <td style="border: 1px solid #bdc3c7;" class="text-start">
                                                <div class="fw-bold text-dark mb-0">{{ $item['nama'] }}</div>
                                                <small class="text-muted font-monospace">NIP. {{ $item['nip'] }}</small>
                                            </td>
                                            <td style="border: 1px solid #bdc3c7;">{{ substr($item['Senin'] ?? '00:00', 0, 5) }}</td>
                                            <td style="border: 1px solid #bdc3c7;">{{ substr($item['Selasa'] ?? '00:00', 0, 5) }}</td>
                                            <td style="border: 1px solid #bdc3c7;">{{ substr($item['Rabu'] ?? '00:00', 0, 5) }}</td>
                                            <td style="border: 1px solid #bdc3c7;">{{ substr($item['Kamis'] ?? '00:00', 0, 5) }}</td>
                                            <td style="border: 1px solid #bdc3c7;">{{ substr($item['Jumat'] ?? '00:00', 0, 5) }}</td>
                                            @if($isKjp2)
                                                <td style="border: 1px solid #bdc3c7;" class="bg-warning bg-opacity-10 fw-bold">{{ substr($item['Sabtu'] ?? '00:00', 0, 5) }}</td>
                                            @endif
                                            <td style="border: 1px solid #bdc3c7;" class="fw-bold text-success bg-success bg-opacity-10">
                                                {{ substr($item['total_lembur'] ?? '00:00', 0, 5) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endforeach
            @endif

            @if(!$adaDataMingguan)
                <div class="text-center py-5 text-muted" style="font-style: italic;">
                    @if($isKjp2 && empty($selectedNipKjp2))
                        <i class="fa-solid fa-hand-pointer me-1 text-warning"></i>
                        Silakan centang minimal satu pegawai pada <strong>Tabel 1</strong> lalu klik <strong>"Terapkan & Perbarui Data"</strong> untuk menampilkan rincian KJP2 mingguan.
                    @else
                        Tidak ada data aktivitas lembur pada rentang tanggal/kategori yang dipilih.
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- TABEL 3: ARSIP RIWAYAT MANUAL -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-clock-rotate-left me-1 text-secondary"></i> 
                    Tabel 3: Arsip Riwayat Laporan (Permanent History)
                </span>
                @if(request('jenis_jam') == 'kjp2')
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 small fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i> Menampilkan Khusus Laporan KJP2 (NK / KS)
                    </span>
                @else
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i> Menampilkan Khusus Laporan Normal
                    </span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-center small">
                    <thead class="table-light text-secondary fw-semibold">
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Kode Arsip</th>
                            <th width="23%">Nama Pegawai</th>
                            <th width="25%">Keterangan Laporan</th>
                            <th width="18%">Periode Tanggal</th>
                            <th width="17%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $filteredHistory = collect($dataLemburHistory ?? []);

                            if (request('jenis_jam') == 'kjp2') {
                                $filteredHistory = $filteredHistory->filter(function($history) {
                                    $batch = $history->idtbrekap ?? '';
                                    $ket = strtolower($history->keterangan ?? '');
                                    $aturan = strtoupper($history->aturan ?? '');
                                    return str_contains($batch, '_KJP2') || 
                                           str_contains($ket, 'kjp2') || 
                                           str_contains($ket, 'nk') || 
                                           str_contains($ket, 'ks') || 
                                           in_array($aturan, ['NK', 'KS']);
                                });
                            } else {
                                $filteredHistory = $filteredHistory->reject(function($history) {
                                    $batch = $history->idtbrekap ?? '';
                                    $ket = strtolower($history->keterangan ?? '');
                                    $aturan = strtoupper($history->aturan ?? '');
                                    return str_contains($batch, '_KJP2') || 
                                           str_contains($ket, 'kjp2') || 
                                           in_array($aturan, ['NK', 'KS']);
                                });
                            }
                        @endphp

                        @if($filteredHistory->count() > 0)
                            @php $noHistory = 1; @endphp
                            @foreach($filteredHistory as $history)
                            <tr>
                                <td>{{ $noHistory++ }}</td>
                                <td class="font-monospace fw-bold text-dark">#ARS-{{ sprintf('%03d', $history->id) }}</td>
                                <td class="text-start">
                                    <div class="fw-bold text-dark mb-0">{{ $history->namaPegawai ?? '-' }}</div>
                                    <small class="text-muted font-monospace">NIP. {{ $history->nip ?? '-' }}</small>
                                </td>
                                <td class="text-start" id="ket-{{ $history->id }}">
                                    {{ $history->keterangan ?? 'Laporan Lembur Tendik' }}
                                    @if(isset($history->aturan) && in_array(strtoupper($history->aturan), ['NK', 'KS']))
                                        <span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size: 10px;">{{ $history->aturan }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="badge bg-light text-secondary border px-2 py-1">
                                        {{ !empty($history->dariTanggal) ? date('d/m/Y', strtotime($history->dariTanggal)) : '-' }} 
                                        s/d 
                                        {{ !empty($history->sampaiTanggal) ? date('d/m/Y', strtotime($history->sampaiTanggal)) : '-' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" 
                                                class="btn btn-outline-primary btn-xs rounded-2 px-2 py-1 fw-semibold btn-edit-arsip" 
                                                data-id="{{ $history->id }}" 
                                                data-keterangan="{{ $history->keterangan }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditArsip">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                        </button>

                                        <form action="{{ url('admin/rekaplembur/hapus-arsip/'.$history->id) }}" method="POST" class="form-delete-arsip">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-xs rounded-2 px-2 py-1 fw-semibold btn-delete-submit">
                                                <i class="fa-solid fa-trash me-1"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    @if(request('jenis_jam') == 'kjp2')
                                        Belum ada riwayat arsip permanent history khusus KJP2 (NK/KS).
                                    @else
                                        Belum ada rekaman riwayat arsip permanen Jam Normal.
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL POPUP ARSIPKAN -->
<div class="modal fade" id="modalArsipkan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <form id="formModalArsipkan" method="POST" action="{{ route('lembur.history.generate') }}">
                @csrf
                <input type="hidden" name="tanggal_awal" value="{{ $tanggal_awal }}">
                <input type="hidden" name="tanggal_akhir" value="{{ $tanggal_akhir }}">
                <input type="hidden" name="jenis_jam" id="modal_jenis_jam" value="{{ request('jenis_jam', 'normal') }}">
                
                {{-- Container tersembunyi yang diisi otomatis oleh JavaScript --}}
                <div id="modalNipContainer"></div>

                <div class="modal-header bg-warning bg-opacity-10 border-0 py-3">
                    <h6 class="modal-title fw-bold text-warning-emphasis"><i class="fa-solid fa-box-archive me-1"></i> Konfirmasi Pengarsipan Laporan</h6>
                    <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-secondary mb-3">Anda akan mengunci dan menyimpan riwayat lembur untuk rentang periode berikut ke dalam database permanent history:</p>
                    <div class="bg-light p-3 rounded-3 border text-center mb-3">
                        <span class="fw-bold text-dark font-monospace">{{ date('d M Y', strtotime($tanggal_awal)) }}</span> 
                        <span class="text-muted mx-2">s/d</span> 
                        <span class="fw-bold text-dark font-monospace">{{ date('d M Y', strtotime($tanggal_akhir)) }}</span>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Keterangan / Nama Laporan</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm rounded-3" placeholder="{{ request('jenis_jam') == 'kjp2' ? 'Contoh: Rekap Lembur KJP2 (NK/KS) Bulan Juli' : 'Contoh: Rekap Lembur Bulan Juli Kelompok I' }}" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-3 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-3 fw-semibold border text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm rounded-3 fw-bold text-dark shadow-sm">Simpan ke Arsip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL POPUP EDIT ARSIP -->
<div class="modal fade" id="modalEditArsip" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <form id="formEditArsip" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary bg-opacity-10 border-0 py-3">
                    <h6 class="modal-title fw-bold text-primary-emphasis"><i class="fa-solid fa-pen-to-square me-1"></i> Ubah Keterangan Arsip</h6>
                    <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Keterangan / Nama Laporan</label>
                        <input type="text" id="editKeteranganInput" name="keterangan" class="form-control form-control-sm rounded-3" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-3 pt-0">
                    <button type="button" class="btn btn-light btn-sm rounded-3 fw-semibold border text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // CHECK ALL KJP2 CHECKBOXES
        const checkAll = document.getElementById('checkAllKjp2');
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.checkbox-kjp2');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }

        // SUBMIT OTOMATIS SAAT RADIO BUTTON DIGANTI
        const radioJam = document.querySelectorAll('.radio-jam');
        radioJam.forEach(radio => {
            radio.addEventListener('change', function () {
                if (this.value === 'normal') {
                    document.querySelectorAll('.checkbox-kjp2').forEach(cb => cb.checked = false);
                }
                document.getElementById('formFilterLembur').submit();
            });
        });

        // HANDLER TERHUBUNG: SINKRONISASI CHECKBOX PEGAWAI KE FORM MODAL ARSIP (FIXED & CLEAN)
        const formModalArsipkan = document.getElementById('formModalArsipkan');
        if (formModalArsipkan) {
            formModalArsipkan.addEventListener('submit', function () {
                const container = document.getElementById('modalNipContainer');
                container.innerHTML = ''; // Kosongkan data lama

                // 1. Ambil Jenis Jam yang Aktif
                const activeRadioJam = document.querySelector('input[name="jenis_jam"]:checked');
                const jenisJamValue = activeRadioJam ? activeRadioJam.value : 'normal';
                document.getElementById('modal_jenis_jam').value = jenisJamValue;

                // 2. Jika Mode KJP2, Ambil Semua NIP yang Tercentang
                if (jenisJamValue === 'kjp2') {
                    const checkedBoxes = document.querySelectorAll('.checkbox-kjp2:checked');

                    checkedBoxes.forEach(function (checkbox) {
                        // Input 1: nip_pilihan[]
                        const hiddenPilihan = document.createElement('input');
                        hiddenPilihan.type = 'hidden';
                        hiddenPilihan.name = 'nip_pilihan[]';
                        hiddenPilihan.value = checkbox.value;
                        container.appendChild(hiddenPilihan);

                        // Input 2: nip_kjp2[]
                        const hiddenKjp2 = document.createElement('input');
                        hiddenKjp2.type = 'hidden';
                        hiddenKjp2.name = 'nip_kjp2[]';
                        hiddenKjp2.value = checkbox.value;
                        container.appendChild(hiddenKjp2);
                    });
                }
            });
        }

        // HANDLER NOTIFIKASI FLASH SESSION
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                showConfirmButton: false,
                timer: 2500,
                customClass: { popup: 'rounded-3' }
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal Proses!',
                text: "{{ session('error') }}",
                confirmButtonColor: '#dc3545',
                customClass: { popup: 'rounded-3', confirmButton: 'rounded-3 fw-bold btn-sm' }
            });
        @endif

        // INJEKSI FORM EDIT ARSIP
        const editButtons = document.querySelectorAll('.btn-edit-arsip');
        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const keterangan = this.getAttribute('data-keterangan');
                document.getElementById('formEditArsip').action = `{{ url('admin/rekaplembur/update-arsip') }}/${id}`;
                document.getElementById('editKeteranganInput').value = keterangan;
            });
        });

        // CONFIRMATION POPUP DELETE
        const deleteForms = document.querySelectorAll('.form-delete-arsip');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault(); 
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data arsip permanent history ini akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: { popup: 'rounded-3', confirmButton: 'rounded-3 fw-bold btn-sm' }
                }).then((result) => {
                    if (result.isConfirmed) { form.submit(); }
                });
            });
        });

    });
</script>
@endsection