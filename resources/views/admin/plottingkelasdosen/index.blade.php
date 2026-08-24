@extends('layouts.app')

<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

@section('content')
<style>
    .bg-purple { background-color: #6f42c1 !important; color: #ffffff !important; }
    .fs-7 { font-size: 0.8rem !important; }
    .fs-8 { font-size: 0.7rem !important; }
    .input-group .btn-clear {
        border-color: #dee2e6;
        border-left: none;
        background-color: #fff;
        color: #6c757d;
    }
    .input-group .btn-clear:hover {
        background-color: #f8f9fa;
        color: #dc3545;
    }
    
    /* FIX SELECT2 SCROLL: Mengatur tinggi dan scrollbar dropdown select2 */
    .select2-container--bootstrap-5 .select2-results__options,
    .select2-container--default .select2-results__options {
        max-height: 260px !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Plotting Kelas Dosen</h1>
            <p class="text-muted small mb-0">Menampilkan data berdasarkan Pengaturan Sistem Aktif: <span class="badge bg-success text-white fw-bold">{{ $tahunAkademikAktif }}</span></p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" onclick="openTambahModal()">
            <i class="fa-solid fa-plus me-1"></i> Tambah Alokasi Mengajar
        </button>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- TABEL 1: DATA PLOTTING UTAMA -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table id="tablePlottingAjar" class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Dosen Pengajar</th>
                            <th class="text-center" style="width: 120px;">Kelas</th>
                            <th>Mata Kuliah</th>
                            <th class="text-center" style="width: 80px;">SKS</th>
                            <th class="text-center" style="width: 80px;">Jam</th>
                            <th class="text-center" style="width: 180px;">Tahun Akademik</th>
                            <th class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @foreach($dataPlotting as $index => $item)
                        <tr>
                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->nama ?? 'Dosen Tidak Ditemukan' }}</div>
                                <small class="text-muted font-monospace">{{ $item->nip }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1.5 fw-bold fs-7">
                                    {{ $item->kelas }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary">{{ $item->namaMk ?? 'Mata Kuliah Tidak Ditemukan' }}</div>
                                <small class="text-muted font-monospace">{{ $item->kodeMk }}</small>
                            </td>
                            <td class="text-center fw-bold text-dark">{{ $item->sks ?? 0 }}</td>
                            <td class="text-center fw-bold text-dark">{{ $item->jam ?? 0 }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary px-3 py-1.5 rounded-pill fw-semibold shadow-sm">
                                    {{ $item->tahunAkademik ?? '-' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-warning me-1" onclick="openEditModal({{ json_encode($item) }})">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-plotting" data-url="{{ route('admin.plottingkelasdosen.destroy', $item->id) }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL 2: RINGKASAN REKAPITULASI BEBAN AJAR DOSEN -->
    <div class="card shadow-sm border-0 rounded-3 mt-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="card-title fw-bold text-dark mb-1">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i>Rekapitulasi Total SKS & Jam Mengajar Dosen
            </h5>
            <p class="text-muted small mb-0">Akumulasi dihitung otomatis berdasarkan peraturan batas beban kerja pegawai terdata.</p>
        </div>
        <div class="card-body p-3 pt-0">
            
            <!-- Form Pencarian Server-Side untuk Tabel 2 -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 px-1">
                <form method="GET" action="{{ url()->current() }}" class="input-group" style="max-width: 380px;" id="formSearchRekap">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="fa-solid fa-search small"></i>
                    </span>
                    <input type="text" name="search_rekap" id="searchRekap" class="form-control border-start-0 border-end-0 ps-1 small" placeholder="Cari NIP atau nama dosen di rekap... (Enter)" value="{{ request('search_rekap') }}">
                    <button class="btn btn-clear border-start-0 {{ !empty(request('search_rekap')) ? '' : 'd-none' }}" type="button" id="clearSearch" title="Clear Search">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </form>
            </div>

            <div class="table-responsive px-1">
                <table class="table table-bordered align-middle mb-0 w-100">
                    <thead class="table-dark small text-uppercase text-center">
                        <tr>
                            <th style="width: 60px;" rowspan="2" class="align-middle">No</th>
                            <th rowspan="2" class="align-middle text-start">NIP / Nama Dosen / Status</th>
                            <th colspan="2">Komponen Per Kelas</th>
                            <th colspan="2">Total Akumulasi Komponen</th>
                            <th style="width: 120px;" rowspan="2" class="align-middle">Total SKS</th>
                            <th style="width: 150px;" rowspan="2" class="align-middle">Total Jam / Minggu</th>
                            <th style="width: 180px;" rowspan="2" class="align-middle">Status Beban</th>
                        </tr>
                        <tr>
                            <th style="width: 110px;">SKS Teori</th>
                            <th style="width: 110px;">SKS Praktek</th>
                            <th style="width: 120px;">Total SKS Teori</th>
                            <th style="width: 120px;">Total SKS Praktek Dituntut</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @php
                            $noRekap = $rekapDosenPaginator->firstItem() ?? 1;
                        @endphp

                        @forelse($rekapDosenPaginator as $dosenInfo)
                            @php
                                $nip = $dosenInfo->nip;
                                $statusPegawai = strtoupper(trim($dosenInfo->statusPegawai ?? ''));
                                $isPengelola = !empty($dosenInfo->jabatan_pengelola);

                                $listPlotting = $detailPlottingGrup->get($nip, collect());
                                
                                $plottingUnik = $listPlotting->groupBy(function($item) {
                                    return $item->kodeMk . '-' . $item->kelas;
                                });
                                
                                $totalSks = 0;
                                $totalJam = 0;
                                $totalSksTeori = 0;
                                $totalSksPraktekValid = 0;
                                $totalSksPraktekMurni = 0;

                                $detailTeori = [];
                                $detailPraktek = [];

                                foreach ($plottingUnik as $key => $items) {
                                    $dataUtama = $items->first();
                                    $sksTeoriItem = $dataUtama->sksProdiT ?? 0;
                                    $sksPraktekItem = $dataUtama->sksProdiP ?? 0;

                                    $totalSks += $dataUtama->sks ?? 0;
                                    $totalJam += $dataUtama->jam ?? 0;
                                    $totalSksTeori += $sksTeoriItem;
                                    $totalSksPraktekMurni += $sksPraktekItem;

                                    $namaProgramUpper = strtoupper(trim($dataUtama->namaProgram ?? ''));
                                    $namaKelasUpper = strtoupper(trim($dataUtama->kelas ?? ''));

                                    $isSore = (str_contains($namaProgramUpper, 'SORE') || str_contains($namaKelasUpper, 'SORE') || str_contains($namaProgramUpper, 'MALAM') || str_contains($namaKelasUpper, 'MALAM'));
                                    $isKjp2 = (str_contains($namaProgramUpper, 'KJP2') || str_contains($namaKelasUpper, 'KJP') || str_contains($namaProgramUpper, 'KJP 2'));

                                    if (($isSore && $isKjp2) || $isSore || $isKjp2) {
                                        $totalSksPraktekValid += $sksPraktekItem;
                                        $detailPraktek[] = '<div class="d-flex justify-content-between text-danger fw-semibold"><span>' . $dataUtama->kelas . ' (Sore/KJP2):</span><span>' . $sksPraktekItem . ' P</span></div>';
                                    } else {
                                        $detailPraktek[] = '<div class="d-flex justify-content-between text-muted text-decoration-line-through"><span>' . $dataUtama->kelas . ' (Pagi):</span><span>' . $sksPraktekItem . ' P</span></div>';
                                    }

                                    $detailTeori[] = '<div class="d-flex justify-content-between"><span>' . $dataUtama->kelas . ':</span><span class="fw-semibold">' . $sksTeoriItem . ' T</span></div>';
                                }

                                $rowClass = '';
                                $rowStyle = '';
                                $statusText = 'Ideal';
                                $badgeColor = 'bg-success';

                                if ($statusPegawai === 'PNS') {
                                    if ($isPengelola) {
                                        if ($totalSksPraktekValid > 4) {
                                            $rowClass = 'table-warning text-dark'; 
                                            $selisih = $totalSksPraktekValid - 4;
                                            $statusText = 'Praktek Sore > ' . $selisih . ' SKS (Maks 4)';
                                            $badgeColor = 'bg-warning text-dark';
                                        }
                                        if ($totalSks > 16) {
                                            $rowClass = 'table-danger text-danger border-danger'; 
                                            $kelebihan = $totalSks - 16;
                                            $statusText = 'Kelebihan ' . $kelebihan . ' SKS (Maks 16)';
                                            $badgeColor = 'bg-danger text-white';
                                        }
                                    } else {
                                        if ($totalSksPraktekValid > 6) {
                                            $rowClass = 'table-success text-success'; 
                                            $selisih = $totalSksPraktekValid - 6;
                                            $statusText = 'Praktek Sore > ' . $selisih . ' SKS (Maks 6)';
                                            $badgeColor = 'bg-success text-white';
                                        }
                                    }

                                    if ($totalSks < 9) {
                                        $rowClass = 'table-primary'; 
                                        $rowStyle = 'background-color: #e0cffc !important; color: #59359a !important;'; 
                                        $statusText = 'Kurang Beban PNS (< 9 SKS)';
                                        $badgeColor = 'bg-purple';
                                    } elseif ($totalSks > 30) {
                                        $rowClass = 'table-primary';
                                        $rowStyle = 'background-color: #e0cffc !important; color: #59359a !important;';
                                        $kelebihan = $totalSks - 30;
                                        $statusText = 'Kelebihan ' . $kelebihan . ' SKS (Maks 30)';
                                        $badgeColor = 'bg-purple';
                                    }

                                } elseif ($statusPegawai === 'CPNS') {
                                    if ($totalSks < 1) {
                                        $rowClass = 'bg-dark text-white';
                                        $rowStyle = 'background-color: #212529 !important; color: #ffffff !important;';
                                        $statusText = 'Kurang dari 1 SKS';
                                        $badgeColor = 'bg-light text-dark';
                                    }

                                } elseif ($statusPegawai === 'LB') {
                                    if ($totalSks > 16) {
                                        $rowClass = 'table-info';
                                        $rowStyle = 'background-color: #cff4fc !important; color: #055160 !important;';
                                        $statusText = 'Melebihi Batas LB (> 16 SKS)';
                                        $badgeColor = 'bg-info text-dark';
                                    }

                                } elseif ($statusPegawai === 'PPPK') {
                                    if ($totalSks < 8 || $totalSks > 9) {
                                        $rowClass = 'table-warning';
                                        $rowStyle = 'background-color: #ffe5d0 !important; color: #943a00 !important;';
                                        $statusText = $totalSks < 8 ? 'Kurang dari 8 SKS' : 'Kelebihan SKS (Maks 9)';
                                        $badgeColor = 'bg-warning text-dark';
                                    }
                                }
                            @endphp
                            
                            <tr class="{{ $rowClass }}" style="{{ $rowStyle }}">
                                <td class="text-center">{{ $noRekap++ }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $dosenInfo->nama ?? 'Nama Tidak Terdata' }}</div>
                                    <span class="font-monospace small opacity-75 text-muted">{{ $nip }}</span> 
                                    <span class="badge bg-secondary ms-1 small fs-8">{{ $statusPegawai ?: 'N/A' }}</span>
                                    @if($isPengelola)
                                        <span class="badge bg-dark text-warning ms-1 small fs-8" title="{{ $dosenInfo->jabatan_pengelola }}">Pengelola</span>
                                    @endif
                                </td>
                                <td class="p-2" style="min-width: 130px;">
                                    <small>{!! implode('', $detailTeori) !!}</small>
                                </td>
                                <td class="p-2" style="min-width: 130px;">
                                    <small>{!! implode('', $detailPraktek) !!}</small>
                                </td>
                                <td class="text-center fw-bold bg-light-subtle text-dark">{{ $totalSksTeori }} T</td>
                                <td class="text-center fw-bold bg-light-subtle text-dark" title="Total Riil Keseluruhan: {{ $totalSksPraktekMurni }} P">
                                    {{ $totalSksPraktekValid }} P
                                    <small class="text-muted d-block" style="font-size:10px;">(Sore/KJP2)</small>
                                </td>
                                <td class="text-center fw-bold fs-6 text-dark">{{ $totalSks }} SKS</td>
                                <td class="text-center fw-bold fs-6 text-dark">{{ $totalJam }} Jam</td>
                                <td class="text-center">
                                    <span class="badge {{ $badgeColor }} px-3 py-1.5 rounded shadow-sm text-wrap">
                                        {{ $statusText }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Tidak ada data rekapitulasi dosen yang cocok dengan pencarian atau belum ada alokasi mengajar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- BLOK CONTAINER NAVIGASI HALAMAN PAGINASI SERVER -->
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 px-1 small">
                <div class="text-muted">
                    Menampilkan <b>{{ $rekapDosenPaginator->firstItem() ?? 0 }}</b> - <b>{{ $rekapDosenPaginator->lastItem() ?? 0 }}</b> dari <b>{{ $rekapDosenPaginator->total() }}</b> dosen terplotting.
                </div>
                <div>
                    {{ $rekapDosenPaginator->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

<!-- FORM GLOBAL ACTION DELETE -->
<form id="formDeletePlotting" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<!-- MODAL ALOKASI -->
<div class="modal fade" id="modalPlottingAjar" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalPlottingAjarLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalPlottingAjarLabel">Tambah Alokasi Mengajar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formPlottingAjar" method="POST">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="POST">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">1. Dosen Pengajar</label>
                        <select name="nip" id="input_nip" class="form-select select2-modal" data-placeholder="-- Pilih Dosen Pengajar --" required style="width: 100%;">
                            <option value=""></option>
                            @foreach($dosen as $d)
                                <option value="{{ $d->nip }}">{{ $d->nip }} - {{ $d->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    @php
                        $pureTa = explode(' ', trim($tahunAkademikAktif))[0];
                        $lastDigitTa = (int) substr($pureTa, -1);
                        $isSystemGenap = ($lastDigitTa % 2 === 0);
                    @endphp

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">2. Kelas (Sesuai Semester Aktif) - <span class="text-primary fw-normal id-info-pilih">Bisa pilih lebih dari 1</span></label>
                        <select name="kelas[]" id="input_kelas" class="form-select select2-modal" data-placeholder="-- Pilih Kelas --" multiple required style="width: 100%;">
                            @foreach($masterKelas as $k)
                                @php
                                    preg_match('/\d/', $k->namaKelas, $matches);
                                    $isKelasGenap = false;
                                    if (!empty($matches)) {
                                        $angkaKelas = (int) $matches[0];
                                        $isKelasGenap = ($angkaKelas % 2 === 0);
                                    }
                                @endphp
                                @if(($isSystemGenap && $isKelasGenap) || (!$isSystemGenap && !$isKelasGenap))
                                    <option value="{{ $k->namaKelas }}">{{ $k->namaKelas }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">3. Mata Kuliah yang Diajar (Sesuai Semester Aktif)</label>
                        <select name="kodeMk" id="input_kodeMk" class="form-select select2-modal" data-placeholder="-- Pilih Mata Kuliah --" required style="width: 100%;">
                            <option value=""></option>
                            @foreach($matakuliah as $mk)
                                @php
                                    $isMkGenap = ($mk->semester % 2 === 0);
                                @endphp
                                @if(($isSystemGenap && $isMkGenap) || (!$isSystemGenap && !$isMkGenap))
                                    <option value="{{ $mk->kodeMk }}">{{ $mk->kodeMk }} - {{ $mk->namaMk }} (Smstr {{ $mk->semester }} | {{ $mk->total ?? 0 }} SKS)</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="tahunAkademik" id="input_tahunAkademik">

                    <div class="pt-3 border-top text-end">
                        <button type="button" class="btn btn-secondary me-1" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-3 shadow-sm" id="btnSimpanPlotting">Simpan Alokasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    const modalAjar = new bootstrap.Modal(document.getElementById('modalPlottingAjar'));
    var dataTableInstance;
    
    const fullString = "{{ $tahunAkademikAktif }}".trim();
    const pureTaAktif = fullString.split(' ')[0]; 

    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#tablePlottingAjar')) {
            $('#tablePlottingAjar').DataTable().destroy();
        }

        dataTableInstance = $('#tablePlottingAjar').DataTable({
            "ordering": true,
            "searching": true, 
            "pageLength": 10,
            "columnDefs": [
                { "orderable": false, "targets": [0, 7] } 
            ],
            "language": {
                "search": "Cari Data:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "zeroRecords": "Tidak ada data alokasi kelas yang cocok",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": { "next": "Berikutnya", "previous": "Sebelumnya" }
            }
        });

        // Inisialisasi Select2
        $('.select2-modal').select2({
            dropdownParent: $('#modalPlottingAjar'),
            theme: 'bootstrap-5',
            allowClear: true
        });

        if ($('#searchRekap').val().trim() !== '') {
            $('#clearSearch').removeClass('d-none');
        }

        $('#searchRekap').on('keyup input', function() {
            if ($(this).val().trim() !== '') {
                $('#clearSearch').removeClass('d-none');
            } else {
                $('#clearSearch').addClass('d-none');
            }
        });

        $('#clearSearch').on('click', function() {
            $('#searchRekap').val('');
            $('#formSearchRekap').submit();
        });

        $('#tablePlottingAjar').on('click', '.btn-hapus-plotting', function() {
            let targetUrl = $(this).data('url');
            Swal.fire({
                title: 'Hapus Alokasi Mengajar?',
                text: "Data plotting kelas dosen ini akan dihapus permanen dari sistem.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    let actionForm = $('#formDeletePlotting');
                    actionForm.attr('action', targetUrl);
                    actionForm.submit();
                }
            });
        });
    });

    function openTambahModal() {
        $('#modalPlottingAjarLabel').text('Tambah Alokasi Mengajar Baru');
        $('#formPlottingAjar').attr('action', "{{ route('admin.plottingkelasdosen.store') }}");
        $('#methodField').val('POST');
        
        // Kembalikan ke mode multiple select
        $('#input_kelas').attr('multiple', 'multiple');
        $('.id-info-pilih').text('Bisa pilih lebih dari 1');

        // Reset struktur internal Select2 agar tidak bentrok dengan sisa render modal sebelumnya
        if ($('#input_kelas').data('select2')) {
            $('#input_kelas').select2('destroy');
        }

        $('#formPlottingAjar')[0].reset();
        
        // Re-inisialisasi ulang
        $('#input_kelas').select2({
            dropdownParent: $('#modalPlottingAjar'),
            theme: 'bootstrap-5',
            allowClear: true
        });

        $('#input_nip').val('').trigger('change');
        $('#input_kelas').val([]).trigger('change'); 
        $('#input_kodeMk').val('').trigger('change');
        
        $('#input_tahunAkademik').val(pureTaAktif);
        modalAjar.show();
    }

    function openEditModal(data) {
        $('#modalPlottingAjarLabel').text('Ubah Alokasi Mengajar Dosen');
        $('#formPlottingAjar').attr('action', `/admin/plottingkelasdosen/${data.id}`);
        $('#methodField').val('PUT');

        // Hapus attribute multiple untuk proses pembaruan data tunggal
        if ($('#input_kelas').data('select2')) {
            $('#input_kelas').select2('destroy');
        }
        
        $('#input_kelas').removeAttr('multiple');
        $('.id-info-pilih').text('Pilih satu kelas saja untuk diubah');

        // Bangun ulang Select2 non-multiple
        $('#input_kelas').select2({
            dropdownParent: $('#modalPlottingAjar'),
            theme: 'bootstrap-5',
            allowClear: true
        });

        if ($("#input_kelas option[value='" + data.kelas + "']").length == 0) {
            let newOption = new Option(data.kelas, data.kelas, true, true);
            $('#input_kelas').append(newOption);
        }

        // Set value dan perbarui tampilan Select2
        $('#input_nip').val(data.nip).trigger('change');
        $('#input_kelas').val(data.kelas).trigger('change');
        $('#input_kodeMk').val(data.kodeMk).trigger('change');
        
        $('#input_tahunAkademik').val(data.tahunAkademik);
        modalAjar.show();
    }
</script>
@endsection