@extends('layouts.app')

@section('styles')
    <!-- CSS Dependencies Khusus Halaman Ini -->
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
    
    <style>
        /* Mengatur Z-Index Select2 berada tepat di atas Modal Bootstrap (Z-Index Modal ~1055) */
        .select2-container--open {
            z-index: 1060 !important;
        }
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.375rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            color: #212529 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #e9ecef !important;
            opacity: 1;
        }
        /* Custom Styling untuk Header & Subheader Grup Tabel */
        .tr-group-header {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            color: #1e293b;
            border-left: 4px solid #0d6efd;
        }
        .tr-subgroup-header {
            background-color: #f8fafc !important;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
            border-left: 4px solid #6c757d;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Data Penempatan Magang</h1>
            <p class="text-muted mb-0">
                Menampilkan penempatan aktif untuk Tahun Akademik: 
                <span class="badge bg-success fs-6 shadow-sm px-3 py-2 ms-1">
                    <i class="fas fa-calendar-check me-1"></i> {{ $taAktif ?? 'Belum Diatur' }}
                </span>
            </p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus-circle me-2"></i>Tambah Penempatan
        </button>
    </div>

    <!-- FITUR PENCARIAN & FILTER -->
    <div class="row mb-3">
        <div class="col-md-6 col-lg-4 ms-auto">
            <form action="{{ route('admin.data-magang.index') }}" method="GET">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" 
                           placeholder="Cari NPM, Nama, atau Instansi..." value="{{ request('search') }}">
                    
                    {{-- Tombol Clear Search --}}
                    @if(request('search'))
                        <a href="{{ route('admin.data-magang.index') }}" class="btn btn-outline-secondary d-flex align-items-center" title="Bersihkan Pencarian">
                            <i class="fas fa-times text-danger"></i>
                        </a>
                    @endif
                    
                    <button class="btn btn-primary px-3" type="submit">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4" style="width: 20%;">NPM</th>
                            <th style="width: 35%;">Nama Mahasiswa</th>
                            <th style="width: 15%;">Program</th>
                            <th class="text-center" style="width: 15%;">Periode Magang</th>
                            <th class="text-center pe-4" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $currentPerusahaan = null;
                            $currentAnakCabang = null;
                            
                            $counts = [];
                            foreach($daftarNilai as $r) {
                                $keyP = $r->namaPerusahaan;
                                $keyA = $r->anakCabang ?: '-';
                                if (!isset($counts[$keyP]['total'])) $counts[$keyP]['total'] = 0;
                                if (!isset($counts[$keyP]['sub'][$keyA])) $counts[$keyP]['sub'][$keyA] = 0;
                                
                                $counts[$keyP]['total']++;
                                $counts[$keyP]['sub'][$keyA]++;
                            }
                        @endphp

                        @forelse($daftarNilai as $row)
                            {{-- 1. HEADER GRUP: PERUSAHAAN/INSTANSI --}}
                            @if($currentPerusahaan !== $row->namaPerusahaan)
                                @php 
                                    $currentPerusahaan = $row->namaPerusahaan; 
                                    $currentAnakCabang = null;
                                    $totalMhs = $counts[$currentPerusahaan]['total'] ?? 0;
                                @endphp
                                <tr class="tr-group-header">
                                    <td colspan="5" class="ps-3 py-2">
                                        <i class="fas fa-building text-primary me-2"></i> 
                                        {{ $currentPerusahaan }} 
                                        <span class="badge bg-primary rounded-pill ms-2 fw-normal" style="font-size: 0.75rem;">
                                            {{ $totalMhs }} Mahasiswa
                                        </span>
                                    </td>
                                </tr>
                            @endif

                            {{-- 2. SUBHEADER GRUP: UNIT INSTANSI / DIVISI --}}
                            @php $actualAnakCabang = $row->anakCabang ?: '-'; @endphp
                            @if($currentAnakCabang !== $actualAnakCabang)
                                @php 
                                    $currentAnakCabang = $actualAnakCabang; 
                                    $subMhs = $counts[$currentPerusahaan]['sub'][$currentAnakCabang] ?? 0;
                                @endphp
                                <tr class="tr-subgroup-header">
                                    <td colspan="5" class="ps-4 py-2">
                                        <i class="fas fa-caret-right text-secondary me-2"></i> 
                                        @if($row->anakCabang)
                                            Cabang/Divisi: {{ $row->anakCabang }}
                                        @else
                                            <span class="text-muted italic">Tanpa Unit Instansi / Kantor Pusat</span>
                                        @endif
                                        <span class="badge bg-secondary rounded-pill ms-2 fw-normal" style="font-size: 0.70rem;">
                                            {{ $subMhs }} Mahasiswa
                                        </span>
                                    </td>
                                </tr>
                            @endif

                            {{-- 3. BARIS DATA MAHASISWA --}}
                            <tr>
                                <td class="ps-4 font-weight-bold fw-semibold">{{ $row->npm }}</td>
                                <td>{{ $row->nama }}</td>
                                <td>{{ $row->prodi }}</td>
                                <td class="text-center small">
                                    <span class="text-nowrap">{{ date('d M Y', strtotime($row->tglMulai)) }}</span>
                                    <span class="text-muted mx-1">s/d</span>
                                    <span class="text-nowrap">{{ date('d M Y', strtotime($row->tglSelesai)) }}</span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-info btn-edit" data-id="{{ $row->id }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.data-magang.destroy', $row->id) }}" method="POST" class="form-hapus-magang" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 d-block text-secondary"></i>
                                    @if(request('search'))
                                        Pencarian "<strong>{{ request('search') }}</strong>" tidak menemukan hasil apapun.
                                    @else
                                        Belum ada data penempatan magang pada Tahun Akademik aktif ini.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Footer Paginasi --}}
        @if($daftarNilai->hasPages())
            <div class="card-footer bg-white border-0 d-flex justify-content-end p-3">
                {{ $daftarNilai->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ==================== MODAL TAMBAH (STATIC BACKDROP) ==================== -->
<div class="modal fade" id="modalTambah" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTambahLabel"><i class="fas fa-user-plus me-2"></i>Tambah Penempatan Magang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.data-magang.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-4">
                    <!-- Dropdown Chained Select2 Perusahaan & Cabang -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">Nama Perusahaan <span class="text-danger">*</span></label>
                            <select id="selectPerusahaan" class="form-select" required></select>
                            <input type="hidden" name="namaPerusahaan" id="hiddenNamaPerusahaan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unit Instansi / Divisi</label>
                            <select id="selectAnakCabang" name="anakCabang" class="form-select" disabled>
                                <option value="">Pilih perusahaan terlebih dahulu...</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Rentang Tanggal Penempatan -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tglMulai" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="tglSelesai" class="form-control" required>
                        </div>
                    </div>

                    <hr class="my-4 text-muted">
                    
                    <!-- Seleksi Pencarian Mahasiswa -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary"><i class="fas fa-graduation-cap me-2"></i>Pilih Mahasiswa Semester 5 <span class="text-danger">*</span></label>
                        <select id="selectMahasiswa" class="form-select"></select>
                    </div>

                    <!-- Container Penampung List Tag Mahasiswa -->
                    <div id="containerMahasiswa" class="mb-2"></div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL EDIT (STATIC BACKDROP) ==================== -->
<div class="modal fade" id="modalEdit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalEditLabel"><i class="fas fa-edit me-2"></i>Edit Penempatan Magang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditMagang" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4">
                    <!-- Informasi Mahasiswa (Read-Only) -->
                    <div class="alert alert-secondary d-flex align-items-center mb-4 py-2 border-0 shadow-sm" role="alert">
                        <i class="fas fa-id-badge fa-lg me-3 text-secondary"></i>
                        <div>
                            <strong>Mahasiswa Terpilih:</strong> <span id="editInfoMahasiswa" class="text-dark"></span>
                        </div>
                    </div>

                    <!-- Dropdown Chained Select2 Perusahaan & Cabang (Edit) -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">Nama Perusahaan <span class="text-danger">*</span></label>
                            <select id="editSelectPerusahaan" class="form-select" required></select>
                            <input type="hidden" name="namaPerusahaan" id="editHiddenNamaPerusahaan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unit Instansi / Divisi</label>
                            <select id="editSelectAnakCabang" name="anakCabang" class="form-select"></select>
                        </div>
                    </div>
                    
                    <!-- Rentang Tanggal Penempatan (Edit) -->
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tglMulai" id="editTglMulai" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="tglSelesai" id="editTglSelesai" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white px-4">Perbarui Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- JS Dependencies Khusus Halaman Ini (Tanpa impor ulang jQuery & Bootstrap) -->
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            var selectedIdIndukTambah = null;
            var selectedIdIndukEdit = null;

            // =========================================================================
            // MATIKAN ENFORCE FOCUS & BERSIHKAN BACKDROP BOOTSTRAP
            // =========================================================================
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            }

            $('#modalTambah, #modalEdit').on('hidden.bs.modal', function () {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('overflow', 'auto');
            });

            // ==========================================
            // NOTIFIKASI SWEETALERT2 (SESSION FLASH)
            // ==========================================
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan!',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#dc3545'
                });
            @endif

            function resetAnakCabangElement(elementId) {
                $(elementId)
                    .prop('disabled', false)
                    .val(null)
                    .empty()
                    .trigger('change');
            }

            // ==========================================
            // A. MODAL TAMBAH: KONFIGURASI AJAX
            // ==========================================

            // 1. Select2 Mahasiswa
            $('#selectMahasiswa').select2({
                dropdownParent: $('#modalTambah'),
                placeholder: 'Cari NPM atau Nama Mahasiswa Semester 5...',
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('admin.data-magang.ajaxGetMahasiswa') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { search: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            // 2. Select2 Perusahaan
            $('#selectPerusahaan').select2({
                dropdownParent: $('#modalTambah'),
                placeholder: 'Ketik & Cari Perusahaan...',
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('admin.data-magang.ajaxGetPerusahaan') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { search: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            // 3. Select2 Unit Instansi
            $('#selectAnakCabang').select2({
                dropdownParent: $('#modalTambah'),
                placeholder: 'Pilih Unit Instansi...',
                minimumInputLength: 0, 
                ajax: {
                    url: "{{ route('admin.data-magang.ajaxGetAnakCabang') }}",
                    dataType: 'json',
                    delay: 100,
                    data: function(params) {
                        return {
                            id_induk: selectedIdIndukTambah,
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            // Event: Perusahaan dipilih di Modal Tambah
            $('#selectPerusahaan').on('select2:select', function(e) {
                var data = e.params.data;
                selectedIdIndukTambah = data.id; 
                
                $('#hiddenNamaPerusahaan').val(data.text); 

                resetAnakCabangElement('#selectAnakCabang');

                if (!selectedIdIndukTambah) return;

                $.ajax({
                    url: "{{ route('admin.data-magang.ajaxGetAnakCabang') }}",
                    type: "GET",
                    data: { id_induk: selectedIdIndukTambah },
                    dataType: "json",
                    success: function(response) {
                        if (response && response.length > 0) {
                            $('#selectAnakCabang').prop('disabled', false).trigger('change');
                        } else {
                            $('#selectAnakCabang').prop('disabled', true);
                            var noBranchOption = new Option("Perusahaan tidak memiliki unit instansi", "", true, true);
                            $('#selectAnakCabang').append(noBranchOption).trigger('change');
                        }
                    },
                    error: function() {
                        $('#selectAnakCabang').prop('disabled', false).trigger('change');
                    }
                });
            });

            // 4. Handle Multi-Mahasiswa
            $('#selectMahasiswa').on('select2:select', function(e) {
                var data = e.params.data;
                if ($('#row_mhs_' + data.id).length == 0) {
                    var html = `
                        <div class="alert alert-info border-0 d-flex justify-content-between align-items-center mb-2 py-2 px-3 shadow-sm" id="row_mhs_${data.id}">
                            <div>
                                <i class="fas fa-check-circle me-2 text-info"></i>
                                <strong>${data.id}</strong> - ${data.nama} | ${data.prodi} (${data.tahun_akademik})
                                <input type="hidden" name="npm[]" value="${data.id}">
                                <input type="hidden" name="nama[]" value="${data.nama}">
                                <input type="hidden" name="prodi[]" value="${data.prodi}">
                                <input type="hidden" name="jurusan[]" value="${data.jurusan || ''}">
                                <input type="hidden" name="tahunAkademik[]" value="${data.tahun_akademik}">
                            </div>
                            <button type="button" class="btn-close remove-mhs" data-id="${data.id}"></button>
                        </div>
                    `;
                    $('#containerMahasiswa').append(html);
                }
                $('#selectMahasiswa').val(null).trigger('change');
            });

            $(document).on('click', '.remove-mhs', function() {
                var id = $(this).data('id');
                $('#row_mhs_' + id).remove();
            });

            // ==========================================
            // B. MODAL EDIT: KONFIGURASI AJAX
            // ==========================================

            $('#editSelectPerusahaan').select2({
                dropdownParent: $('#modalEdit'),
                placeholder: 'Ketik & Cari Perusahaan...',
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('admin.data-magang.ajaxGetPerusahaan') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { search: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            $('#editSelectAnakCabang').select2({
                dropdownParent: $('#modalEdit'),
                placeholder: 'Pilih Unit Instansi...',
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('admin.data-magang.ajaxGetAnakCabang') }}",
                    dataType: 'json',
                    delay: 100,
                    data: function(params) {
                        return {
                            id_induk: selectedIdIndukEdit,
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            // Event: Perusahaan diubah di Modal Edit
            $('#editSelectPerusahaan').on('select2:select', function(e) {
                var data = e.params.data;
                selectedIdIndukEdit = data.id; 

                $('#editHiddenNamaPerusahaan').val(data.text);
                resetAnakCabangElement('#editSelectAnakCabang');

                if (!selectedIdIndukEdit) return;

                $.ajax({
                    url: "{{ route('admin.data-magang.ajaxGetAnakCabang') }}",
                    type: "GET",
                    data: { id_induk: selectedIdIndukEdit },
                    dataType: "json",
                    success: function(response) {
                        if (response && response.length > 0) {
                            $('#editSelectAnakCabang').prop('disabled', false).trigger('change');
                        } else {
                            $('#editSelectAnakCabang').prop('disabled', true);
                            var noBranchOption = new Option("Perusahaan tidak memiliki unit instansi", "", true, true);
                            $('#editSelectAnakCabang').append(noBranchOption).trigger('change');
                        }
                    },
                    error: function() {
                        $('#editSelectAnakCabang').prop('disabled', false).trigger('change');
                    }
                });
            });

            // ==========================================
            // C. POPULASI DATA AWAL MODAL EDIT
            // ==========================================
            $('.btn-edit').on('click', function() {
                var id = $(this).data('id');
                var urlEdit = "{{ route('admin.data-magang.edit', ':id') }}".replace(':id', id);
                var urlUpdate = "{{ route('admin.data-magang.update', ':id') }}".replace(':id', id);

                $('#formEditMagang').attr('action', urlUpdate);
                
                $('#editSelectPerusahaan').val(null).empty().trigger('change');
                $('#editSelectAnakCabang').val(null).empty().trigger('change');

                $.ajax({
                    url: urlEdit,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#editInfoMahasiswa').html(`<strong class="text-primary">${data.npm}</strong> - ${data.nama} | ${data.prodi} (${data.tahunAkademik})`);
                        $('#editTglMulai').val(data.tglMulai);
                        $('#editTglSelesai').val(data.tglSelesai);
                        $('#editHiddenNamaPerusahaan').val(data.namaPerusahaan);

                        if (data.namaPerusahaan) {
                            var optionPerusahaan = new Option(data.namaPerusahaan, data.namaPerusahaan, true, true);
                            $('#editSelectPerusahaan').append(optionPerusahaan).trigger('change');
                        }

                        $.ajax({
                            url: "{{ route('admin.data-magang.ajaxGetPerusahaan') }}",
                            type: "GET",
                            data: { search: data.namaPerusahaan },
                            dataType: "json",
                            success: function(searchRes) {
                                if (searchRes && searchRes.length > 0) {
                                    selectedIdIndukEdit = searchRes[0].id; 

                                    $.ajax({
                                        url: "{{ route('admin.data-magang.ajaxGetAnakCabang') }}",
                                        type: "GET",
                                        data: { id_induk: selectedIdIndukEdit },
                                        dataType: "json",
                                        success: function(branchRes) {
                                            if (branchRes && branchRes.length > 0) {
                                                $('#editSelectAnakCabang').prop('disabled', false).trigger('change');
                                                
                                                if (data.anakCabang) {
                                                    var optionAnak = new Option(data.anakCabang, data.anakCabang, true, true);
                                                    $('#editSelectAnakCabang').append(optionAnak).trigger('change');
                                                }
                                            } else {
                                                $('#editSelectAnakCabang').prop('disabled', true);
                                                var noBranchOption = new Option("Perusahaan tidak memiliki unit instansi", "", true, true);
                                                $('#editSelectAnakCabang').append(noBranchOption).trigger('change');
                                            }
                                        },
                                        error: function() {
                                            console.log("Gagal memuat unit instansi ke select2 edit.");
                                        }
                                    });
                                } else {
                                    selectedIdIndukEdit = null;
                                    $('#editSelectAnakCabang').prop('disabled', true);
                                    var fallbackOption = new Option(data.anakCabang || "Pilih unit instansi...", data.anakCabang || "", true, true);
                                    $('#editSelectAnakCabang').append(fallbackOption).trigger('change');
                                }
                            },
                            error: function() {
                                console.log("Gagal mencocokkan master nama induk perusahaan.");
                            }
                        });

                        var myModal = new bootstrap.Modal(document.getElementById('modalEdit'), {
                            backdrop: 'static',
                            keyboard: false
                        });
                        myModal.show();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal mengambil data penempatan dari server (Status: ' + xhr.status + ').',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            });

            // ==========================================
            // D. HANDLE DELETE CONFIRMATION (SWEETALERT2)
            // ==========================================
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data penempatan mahasiswa ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Reset form saat modal tambah ditutup
            $('#modalTambah').on('hidden.bs.modal', function () {
                $('#selectMahasiswa').val(null).trigger('change');
                $('#containerMahasiswa').empty();
            });
        });
    </script>
@endsection