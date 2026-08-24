@extends('layouts.app')

@section('content')
<style>
    .select2-container--bootstrap-5 .select2-results__options {
        max-height: 280px !important; 
        overflow-y: auto !important;  
    }
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .bg-group-dosen {
        background-color: #eaecf4 !important;
        color: #4e73df !important;
    }
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
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Input Jam Mengajar Dosen</h1>
    </div>

    {{-- Alert Pesan Validasi Error --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="fw-bold small mb-1"><i class="fas fa-exclamation-triangle"></i> Terjadi Kesalahan:</div>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Alert Pesan Sukses Berhasil Disimpan --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-11 mx-auto">
            <!-- FORM ALOKASI BEBAN MENGAJAR -->
            <div class="card shadow-sm border-0 rounded-3 mb-5">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="card-title mb-0 fw-bold small text-uppercase">Form Alokasi Beban Mengajar</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.ajardosen.store') }}" method="POST" id="formAjarDosen">
                        @csrf

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Dosen Pengajar</label>
                                <select name="nip" id="select_dosen" class="form-select select2-field" required data-placeholder="-- Pilih Dosen --">
                                    <option value=""></option>
                                    @foreach($dosen as $d)
                                        <option value="{{ $d->nip }}" {{ old('nip') == $d->nip ? 'selected' : '' }}>
                                            {{ $d->nip }} - {{ $d->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Kelas</label>
                                <select name="kelas" id="select_kelas" class="form-select select2-field" required data-placeholder="-- Pilih Kelas --">
                                    <option value=""></option>
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->namaKelas }}" {{ old('kelas') == $k->namaKelas ? 'selected' : '' }}>
                                            {{ strtoupper($k->namaKelas) }} ({{ $k->namaProdi ?? 'Umum' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Mata Kuliah</label>
                                <div id="wrapper_select_mk">
                                    <select name="kodeMk" id="select_mk" class="form-select select2-field" required disabled data-placeholder="-- Pilih Kelas Terlebih Dahulu --">
                                        <option value=""></option>
                                        @foreach($matakuliah as $mk)
                                            <option value="{{ $mk->kodeMk }}" data-semester="{{ $mk->semester }}" data-totaljam="{{ $mk->totalJamPerMinggu }}" data-prodi="{{ $mk->prodi }}" {{ old('kodeMk') == $mk->kodeMk ? 'selected' : '' }}>
                                                [Smstr {{ $mk->semester }}] {{ $mk->kodeMk }} - {{ $mk->namaMk }} (Beban: {{ $mk->totalJamPerMinggu }} Jam/Minggu)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- Container pesan teks pengganti jika MK sudah penuh --}}
                                <div id="alert_mk_lengkap" class="alert alert-warning py-2 px-3 mb-0 mt-1 small d-none">
                                    <i class="fas fa-info-circle me-1"></i> Alokasi mata kuliah untuk kelas ini sudah lengkap (terisi penuh di database).
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Tahun Akademik (Aktif)</label>
                                <input type="text" name="tahunAkademik" id="input_tahunAkademik" class="form-control form-control-sm text-dark bg-light fw-bold" value="{{ $tahunAkademikAktif }}" readonly required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Hari</label>
                                <select name="hari" id="select_hari" class="form-select select2-field" required data-placeholder="-- Pilih Hari --">
                                    <option value=""></option>
                                    @foreach($listHariAjar as $ha)
                                        <option value="{{ $ha->hari }}" {{ old('hari') == $ha->hari ? 'selected' : '' }}>{{ $ha->hari }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- CONTAINER CHECKBOX JAM MENGAJAR -->
                        <div class="mb-4 p-3 bg-light rounded border">
                            <label class="form-label small fw-bold text-dark d-block mb-2">Pilih Jam Mengajar (Normal)</label>
                            <div id="container_checkbox_jam">
                                <span class="text-muted small">Pilih kelas dan hari terlebih dahulu untuk memuat daftar jam...</span>
                            </div>
                        </div>

                        <div class="pt-3 border-top text-end">
                            <button type="reset" class="btn btn-sm btn-outline-secondary me-1" id="btnResetForm">Reset Form</button>
                            <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm" id="btnSimpanJadwal">Simpan Jadwal</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL DATA RIWAYAT ALOKASI JAM MENGAJAR DENGAN GROUP HEADER DOSEN -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="m-0 fw-bold"><i class="fas fa-history me-1"></i> Riwayat Alokasi Mengajar Dosen</h6>
                    <span class="badge bg-light text-primary fw-bold px-3 py-2 shadow-sm">Pencarian Global Terintegrasi</span>
                </div>
                <div class="card-body p-3">
                    
                    <!-- KOTAK PENCARIAN -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="input-group" style="max-width: 380px;">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fas fa-search small"></i>
                            </span>
                            <input type="text" id="searchRiwayat" class="form-control border-start-0 border-end-0 ps-1" value="{{ $search ?? '' }}" placeholder="Cari nama dosen, matakuliah, atau kelas...">
                            <button class="btn btn-clear border-start-0 {{ !empty($search) ? '' : 'd-none' }}" type="button" id="clearSearch" title="Clear Search">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    </div>
                    
                    {{-- CONTAINER RESPONSIVE --}}
                    <div class="table-responsive w-100" style="overflow-x: auto; -webkit-overflow-scrolling: touch; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                        <table class="table table-bordered table-hover align-middle mb-0" id="tableRiwayat" style="min-width: 1000px; width: 100%; border-collapse: collapse;">
                            <thead class="table-dark text-center text-nowrap">
                                <tr>
                                    <th style="width: 6%; min-width: 60px; vertical-align: middle;">No</th>
                                    <th style="width: 44%; min-width: 380px; vertical-align: middle;">Mata Kuliah</th>
                                    <th style="width: 12%; min-width: 100px; vertical-align: middle;">Kelas</th>
                                    <th style="width: 13%; min-width: 110px; vertical-align: middle;">Hari</th>
                                    <th style="width: 25%; min-width: 250px; vertical-align: middle;">Jam Mengajar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $dosenCounter = 1;
                                    $totalBarisRecord = 0;
                                @endphp

                                @forelse($riwayatAjarGrupDetail as $namaDosen => $daftarJadwal)
                                    @php $totalBarisRecord += count($daftarJadwal); @endphp
                                    <tr class="bg-group-dosen">
                                        <td class="text-center fw-bold text-primary bg-group-dosen" style="border-right: none;">
                                            {{ $dosenCounter++ }}
                                        </td>
                                        <td colspan="4" class="fw-bold text-primary bg-group-dosen" style="border-left: none; padding-top: 10px; padding-bottom: 10px;">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-white text-primary rounded-circle me-2 text-center shadow-xs" style="width: 30px; height: 30px; line-height: 30px;">
                                                    <i class="fas fa-user-tie small"></i>
                                                </div>
                                                <span class="nama-dosen-text" style="font-size: 0.95rem; letter-spacing: 0.3px;">{{ $namaDosen }}</span>
                                                <span class="badge bg-primary text-white ms-3 fw-normal" style="font-size: 0.75rem;">
                                                    {{ count($daftarJadwal) }} Total Jadwal
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    @foreach($daftarJadwal as $item)
                                        <tr class="data-row-jadwal">
                                            <td class="text-center text-muted small" style="background-color: #fafafa;">
                                                <i class="fas fa-angle-right opacity-50"></i>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-start flex-column gap-1">
                                                    <span class="badge bg-secondary font-monospace px-2 py-1" style="font-size: 0.75rem;">{{ $item->kodeMk }}</span>
                                                    <span class="text-secondary fw-medium" style="white-space: normal;">{{ $item->namaMk }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border border-secondary-subtle fw-bold px-3 py-1.5">{{ $item->kelas }}</span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $bgHari = match($item->hari) {
                                                        'Senin'   => 'bg-primary text-white',
                                                        'Selasa'  => 'bg-success text-white',
                                                        'Rabu'    => 'bg-warning text-dark',
                                                        'Kamis'   => 'bg-info text-dark',
                                                        'Jumat'   => 'bg-danger text-white',
                                                        default   => 'bg-dark text-white'
                                                    };
                                                @endphp
                                                <span class="badge {{ $bgHari }} fw-semibold px-2 py-1.5" style="min-width: 70px; display: inline-block;">{{ $item->hari }}</span>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="text-dark fw-medium p-1" style="letter-spacing: 0.2px;">
                                                    <i class="far fa-clock me-1 text-primary"></i>{{ $item->daftarJam }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr id="emptyRowPlaceholder">
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <div class="py-3">
                                                <i class="fas fa-folder-open d-block mb-3 fa-3x text-secondary-subtle"></i> 
                                                <h6 class="fw-bold text-secondary mb-1">Belum Ada Riwayat Mengajar</h6>
                                                <small class="text-muted">Tidak ditemukan data alokasi mengajar aktif pada Tahun Akademik saat ini.</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                
                                <tr id="noSearchResultRow" style="display: none;">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-search-minus fa-2x d-block mb-2 text-secondary-subtle"></i>
                                        <span class="small fw-semibold">Data tidak ditemukan untuk kata kunci tersebut.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- FOOTER CARD --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap bg-light p-3 rounded border gap-3">
                        <div class="text-muted small">
                            Menampilkan seluruh data dari total <b>{{ count($riwayatAjarGrupDetail) }}</b> nama Dosen dengan akumulasi <b>{{ $totalBarisRecord }}</b> grup jadwal mengajar.
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        const masterMkOptions = $('#select_mk').html();
        let maxJamAllowed = 0; 
        let jamTerisiDatabase = 0; 

        $('.select2-field').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            dropdownParent: $(document.body)
        });

        // =========================================================================
        // LOGIKA PENCARIAN REAL-TIME
        // =========================================================================
        function filterTable() {
            let value = $('#searchRiwayat').val().toLowerCase().trim();
            let clearBtn = $('#clearSearch');
            let hasVisibleRows = false;

            if (value !== '') {
                clearBtn.removeClass('d-none');
            } else {
                clearBtn.addClass('d-none');
            }

            $('.table tbody tr.bg-group-dosen').each(function() {
                let headerRow = $(this);
                let namaDosen = headerRow.find('.nama-dosen-text').text().toLowerCase();
                
                let childRows = headerRow.nextUntil('tr.bg-group-dosen', 'tr.data-row-jadwal');
                let matchInChild = false;

                childRows.each(function() {
                    let childRow = $(this);
                    let textJadwal = childRow.text().toLowerCase();

                    if (textJadwal.includes(value) || namaDosen.includes(value)) {
                        childRow.show();
                        matchInChild = true;
                        hasVisibleRows = true;
                    } else {
                        childRow.hide();
                    }
                });

                if (matchInChild || namaDosen.includes(value)) {
                    headerRow.show();
                } else {
                    headerRow.hide();
                }
            });

            if ($('#emptyRowPlaceholder').length === 0) { 
                if (!hasVisibleRows && value !== '') {
                    $('#noSearchResultRow').show();
                } else {
                    $('#noSearchResultRow').hide();
                }
            }
        }

        if ($('#searchRiwayat').val() !== '') {
            filterTable();
        }

        $('#searchRiwayat').on('input', function() {
            filterTable();
        });

        $('#clearSearch').on('click', function() {
            $('#searchRiwayat').val('');
            filterTable();
            $('#searchRiwayat').focus();
        });

        // =========================================================================
        // 1. FUNGSI AJAX: MEMUAT JADWAL JAM (CHECKBOX)
        // =========================================================================
        function muatCheckboxJam() {
            let kelasTerpilih = $('#select_kelas').val();
            let hariTerpilih = $('#select_hari').val();
            let kodeMkTerpilih = $('#select_mk').val();
            let container = $('#container_checkbox_jam');

            if (kelasTerpilih && hariTerpilih) {
                container.html('<span class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Memvalidasi ketersediaan jam...</span>');
                
                $.ajax({
                    url: "{{ route('admin.ajardosen.getcheckboxjam') }}",
                    type: "GET",
                    data: {
                        kelas: kelasTerpilih,
                        hari: hariTerpilih,
                        kodeMk: kodeMkTerpilih
                    },
                    success: function(response) {
                        if (response.success) {
                            container.html(response.html);
                            batasiCentangJam();
                        } else {
                            container.html(response.html);
                        }
                    },
                    error: function() {
                        container.html('<span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Gagal mengambil data jam dari server.</span>');
                    }
                });
            } else {
                container.html('<span class="text-muted small">Pilih kelas dan hari terlebih dahulu untuk memuat daftar jam...</span>');
            }
        }

        // =========================================================================
        // 2. LOGIKA VALIDASI: PEMBATASAN CENTANG JAM (REAL-TIME SINKRON)
        // =========================================================================
        function batasiCentangJam() {
            let selectMk = $('#select_mk');
            let kodeMkTerpilih = selectMk.val();
            let availableCheckboxes = $('.item-jam-checkbox').not('[data-terisi="1"]');

            // Proteksi awal: Jika mata kuliah belum dipilih, kunci semua checkbox
            if (!kodeMkTerpilih) {
                availableCheckboxes.prop('checked', false).prop('disabled', true);
                $('#checkAllJam').prop('checked', false).prop('disabled', true);
                return;
            }

            // AMBIL DATA BEBAN SECARA SEGAR LANGSUNG DARI OPTION YANG TERPILIH DI SELECT2
            let selectedOption = selectMk.find(':selected');
            let totalJam = selectedOption.attr('data-totaljam');
            let terisiDb = selectedOption.attr('data-terisidb');

            // Set paksa variabel kontrol agar nilainya sinkron
            maxJamAllowed = totalJam ? parseInt(totalJam, 10) : 0;
            jamTerisiDatabase = terisiDb ? parseInt(terisiDb, 10) : 0;

            // Hitung jumlah checkbox yang dicentang user saat ini
            let checkedFormCount = $('.item-jam-checkbox:checked').not('[data-terisi="1"]').length;
            let totalAkumulasiTerisi = jamTerisiDatabase + checkedFormCount;

            // Batasi sisa slot centang jika sudah menyentuh beban maksimal mata kuliah
            if (maxJamAllowed > 0 && totalAkumulasiTerisi >= maxJamAllowed) {
                availableCheckboxes.not(':checked').prop('disabled', true);
                $('#checkAllJam').prop('disabled', true);
            } else {
                availableCheckboxes.prop('disabled', false);
                $('#checkAllJam').prop('disabled', false);
            }

            // Atur status master checkbox "Pilih Semua"
            if (availableCheckboxes.length > 0 && availableCheckboxes.filter(':checked').length === availableCheckboxes.length) {
                $('#checkAllJam').prop('checked', true);
            } else {
                $('#checkAllJam').prop('checked', false);
            }
        }

        // =========================================================================
        // 3. LISTENERS & EVENT HANDLERS
        // =========================================================================
        $('#select_kelas, #select_hari').on('change', function() {
            muatCheckboxJam();
        });

        $('#select_mk').on('change', function() {
            batasiCentangJam();
            muatCheckboxJam();
        });

        $(document).on('change', '.item-jam-checkbox', function() {
            batasiCentangJam();
        });

        $(document).on('change', '#checkAllJam', function() {
            let isChecked = $(this).is(':checked');
            let availableCheckboxes = $('.item-jam-checkbox').not('[data-terisi="1"]');
            
            // Ambil data beban terbaru sebelum memproses isi centangan masal
            let selectedOption = $('#select_mk').find(':selected');
            maxJamAllowed = parseInt(selectedOption.attr('data-totaljam'), 10) || 0;
            jamTerisiDatabase = parseInt(selectedOption.attr('data-terisidb'), 10) || 0;
            
            let sisaSlotBolehDicari = maxJamAllowed - jamTerisiDatabase;
            
            if (isChecked && sisaSlotBolehDicari > 0) {
                availableCheckboxes.prop('checked', false);
                availableCheckboxes.each(function(index) {
                    if (index < sisaSlotBolehDicari) {
                        $(this).prop('checked', true);
                    }
                });
            } else {
                availableCheckboxes.prop('checked', false);
            }
            
            batasiCentangJam();
        });

        // =========================================================================
        // 4. 3-LEVEL FILTER: PENYARINGAN DROP DOWN MATA KULIAH + LOGIKA TEKS HABIS
        // =========================================================================
        $('#select_kelas').on('change', function() {
            let kelasVal = $(this).val();
            let selectMk = $('#select_mk');
            let wrapperSelectMk = $('#wrapper_select_mk');
            let alertMkLengkap = $('#alert_mk_lengkap');

            if (kelasVal) {
                let match = kelasVal.match(/\d+/); 
                if (match) {
                    let semesterTarget = match[0];
                    selectMk.html(masterMkOptions);

                    $.ajax({
                        url: "{{ route('admin.ajardosen.getterisimk') }}",
                        type: "GET",
                        data: { kelas: kelasVal },
                        success: function(response) {
                            if (response.success) {
                                let prodiKelas = response.kodeProdi; 
                                let terisiMkList = response.terisiMk; 

                                selectMk.find('option').each(function() {
                                    let mkSemester = $(this).attr('data-semester');
                                    let mkProdi = $(this).attr('data-prodi'); 
                                    let totalJamBeban = parseInt($(this).attr('data-totaljam')) || 0;
                                    let kodeMkVal = $(this).val();

                                    if (kodeMkVal === "") return;

                                    // Filter Prodi & Semester
                                    if (mkProdi && mkProdi !== prodiKelas) {
                                        $(this).remove();
                                        return;
                                    } 
                                    if (mkSemester && mkSemester !== semesterTarget) {
                                        $(this).remove();
                                        return;
                                    } 
                                    
                                    // Validasi sisa jam mengajar dari database (tbajardosen)
                                    if (terisiMkList && terisiMkList.hasOwnProperty(kodeMkVal)) {
                                        let jamSudahTerisiDiDb = parseInt(terisiMkList[kodeMkVal]) || 0;
                                        
                                        if (jamSudahTerisiDiDb >= totalJamBeban) {
                                            $(this).remove(); 
                                        } else {
                                            $(this).attr('data-terisidb', jamSudahTerisiDiDb);
                                        }
                                    } else {
                                        $(this).attr('data-terisidb', 0);
                                    }
                                });

                                let totalSisaOpsi = selectMk.find('option').length;

                                if (totalSisaOpsi <= 1) {
                                    wrapperSelectMk.addClass('d-none');
                                    alertMkLengkap.removeClass('d-none');
                                    selectMk.prop('required', false);
                                } else {
                                    wrapperSelectMk.removeClass('d-none');
                                    alertMkLengkap.addClass('d-none');
                                    selectMk.prop('required', true);
                                    selectMk.prop('disabled', false).attr('data-placeholder', '-- Pilih Mata Kuliah Semester ' + semesterTarget + ' --');
                                }
                            }
                            
                            selectMk.select2({ theme: 'bootstrap-5', allowClear: true, dropdownParent: $(document.body) });
                            selectMk.trigger('change');
                        },
                        error: function() {
                            console.error('Gagal menyinkronkan ketersediaan beban kontrak mata kuliah.');
                        }
                    });
                }
            } else {
                wrapperSelectMk.removeClass('d-none');
                alertMkLengkap.addClass('d-none');
                selectMk.prop('required', true);
                
                selectMk.val('').trigger('change').prop('disabled', true).attr('data-placeholder', '-- Pilih Kelas Terlebih Dahulu --');
                maxJamAllowed = 0;
                jamTerisiDatabase = 0;
                selectMk.select2({ theme: 'bootstrap-5', allowClear: true, dropdownParent: $(document.body) });
            }
        });

        // =========================================================================
        // 5. BUTTON RESET & VALIDASI SUBMIT FORM
        // =========================================================================
        $('#btnResetForm').on('click', function() {
            setTimeout(function() {
                maxJamAllowed = 0;
                jamTerisiDatabase = 0;
                
                $('#wrapper_select_mk').removeClass('d-none');
                $('#alert_mk_lengkap').addClass('d-none');
                
                $('.select2-field').val('').trigger('change');
                $('#select_mk').html(masterMkOptions).val('').trigger('change').prop('disabled', true).prop('required', true);
                $('#container_checkbox_jam').html('<span class="text-muted small">Pilih kelas dan hari terlebih dahulu untuk memuat daftar jam...</span>');
                $('.select2-field').select2({ theme: 'bootstrap-5', allowClear: true, dropdownParent: $(document.body) });
            }, 10);
        });

        $('#formAjarDosen').on('submit', function() {
            if (!$('#alert_mk_lengkap').hasClass('d-none')) {
                alert('Kesalahan: Tidak dapat menyimpan data. Semua alokasi mata kuliah untuk kelas ini sudah lengkap!');
                return false;
            }

            if ($('.item-jam-checkbox:checked').length === 0 && $('#select_kelas').val() && $('#select_hari').val()) {
                alert('Peringatan: Anda wajib memilih minimal 1 jam mengajar sebelum menyimpan jadwal!');
                return false;
            }
            $('#btnSimpanJadwal').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        });
    });
</script>
@endsection