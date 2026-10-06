@extends('layouts.app')

@section('content')
<!-- 1. MEMASTIKAN SEMUA ASSET CSS TERMUAT SECARA MANDIRI -->
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/rowGroup.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}">
<!-- TAMBAHKAN CSS SWEETALERT2 -->
<link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}"> 

<style>
    .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 0.5rem; }
    
    /* STYLING UTAMA ROWGROUP HEADER (SINKRON DENGAN BOOTSTRAP 5) */
    tr.dtrg-group.dtrg-start td {
        background-color: #f1f3f5 !important;
        font-weight: 700 !important;
        color: #0d6efd !important;
        border-left: 4px solid #0d6efd !important;
        padding: 12px 15px !important;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .select2-container--bootstrap-5 {
        display: block;
        width: 100% !important;
    }
    .select2-container--bootstrap-5 .select2-selection { 
        border: 1px solid #dee2e6 !important;
        border-radius: 0.375rem !important; 
        min-height: 40px !important;
        display: flex !important;
        align-items: center !important;
    }

    /* FIX SCROLL DROPDOWN SELECT2 */
    .select2-container--bootstrap-5 .select2-results__options {
        max-height: 250px !important; 
        overflow-y: auto !important;  
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Manajemen Inventaris & Master Barang</h1>
    </div>

    <div class="row g-4">
        <!-- FORM INPUT DENGAN SELECT2 TAGGING -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="m-0 fw-bold">Form Input Data Barang</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('barang.store') }}" method="POST">
                        @csrf
                        
                        <!-- Master Barang -->
                        <div class="mb-3 text-start position-relative">
                            <label class="form-label fw-bold text-secondary">Master Barang</label>
                            <select class="form-select select2-target" id="selectMaster" name="namaBarang" required>
                                <option value=""></option>
                                @foreach($masterBarangOpt as $mb)
                                    <option value="{{ $mb->namaBarang }}">{{ $mb->namaBarang }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Anak Barang (Dihubungkan dengan data-parent) -->
                        <div class="mb-3 text-start position-relative">
                            <label class="form-label fw-bold text-secondary">Merk / Anak Barang</label>
                            <select class="form-select select2-target" id="selectAnak" name="merkBarang" required>
                                <option value=""></option>
                                @foreach($anakBarangOpt as $ab)
                                    <option value="{{ $ab->merkBarang }}--{{ $ab->namaBarang ?? '' }}" data-parent="{{ $ab->namaBarang ?? '' }}">{{ $ab->merkBarang }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Spesifikasi -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Spesifikasi</label>
                            <textarea class="form-control" name="spesifikasi" rows="3" placeholder="Contoh: Ukuran F4, 1,5 Volt, dll."></textarea>
                        </div>

                        <!-- Satuan -->
                        <div class="mb-4 text-start position-relative">
                            <label class="form-label fw-bold text-secondary">Satuan Barang</label>
                            <select class="form-select select2-target" name="jenisBarang" required>
                                <option value=""></option>
                                @foreach($satuanOpt as $sat)
                                    <option value="{{ $sat->jenisBarang }}">{{ $sat->jenisBarang }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2">Simpan Terintegrasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- DATATABLE -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <h6 class="m-0 fw-bold text-primary">Daftar Inventaris (Grup Master)</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold" id="btnClearSearch">
                        Clear Search
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="tableBarang" class="table table-bordered align-middle" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Master Barang</th>
                                    <th style="width: 40%;">Merk / Anak Barang</th>
                                    <th style="width: 30%;">Spesifikasi</th>
                                    <th style="width: 15%;">Satuan</th>
                                    <th style="width: 15%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($barang as $b)
                                    <tr>
                                        <td>{{ $b->namaBarang }}</td>
                                        <td class="fw-semibold">{{ $b->merkBarang }}</td>
                                        <td class="text-muted">{{ $b->spesifikasi ?? '-' }}</td>
                                        <td><span class="badge bg-secondary px-2 py-1">{{ $b->jenisBarang }}</span></td>
                                        <td class="text-center">
                                            <button type="button" 
                                                class="btn btn-sm btn-warning fw-bold" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditBarang"
                                                data-id="{{ $b->id_anak }}"
                                                data-master="{{ $b->namaBarang }}"
                                                data-merk="{{ $b->merkBarang }}"
                                                data-spesifikasi="{{ $b->spesifikasi }}"
                                                data-satuan="{{ $b->jenisBarang }}"
                                                onclick="bukaModalEdit(this)">
                                                Edit
                                            </button>
                                            
                                            <!-- Form Hapus RESTful -->
                                            <form action="{{ route('barang.destroy', $b->id_anak) }}" method="POST" class="d-inline form-delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger fw-bold">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT DATA BARANG (BOOTSTRAP 5) -->
<div class="modal fade" id="modalEditBarang" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalEditBarangLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="modalEditBarangLabel">Form Edit Data Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditBarang" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body text-start">
                    <!-- Master Barang (Edit) -->
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Master Barang</label>
                        <select class="form-select select2-edit" id="editMaster" name="namaBarang" required>
                            <option value=""></option>
                            @foreach($masterBarangOpt as $mb)
                                <option value="{{ $mb->namaBarang }}">{{ $mb->namaBarang }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Anak Barang (Edit) -->
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Merk / Anak Barang</label>
                        <select class="form-select select2-edit" id="editAnak" name="merkBarang" required>
                            <option value=""></option>
                            @foreach($anakBarangOpt as $ab)
                                <option value="{{ $ab->merkBarang }}">{{ $ab->merkBarang }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Spesifikasi (Edit) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Spesifikasi</label>
                        <textarea class="form-control" id="editSpesifikasi" name="spesifikasi" rows="3" placeholder="Contoh: Ukuran F4, 1,5 Volt, dll."></textarea>
                    </div>

                    <!-- Satuan (Edit) -->
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Satuan Barang</label>
                        <select class="form-select select2-edit" id="editSatuan" name="jenisBarang" required>
                            <option value=""></option>
                            @foreach($satuanOpt as $sat)
                                <option value="{{ $sat->jenisBarang }}">{{ $sat->jenisBarang }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MEMANGGIL JAVASCRIPT SECARA BERURUTAN -->
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('js/dataTables.rowGroup.min.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<!-- TAMBAHKAN JS SWEETALERT2 -->
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script> 

<script>
    (function($) {
        "use strict";

        $(document).ready(function() {
            
            var $selectAnak = $('#selectAnak');
            var anakOptionsBackup = $selectAnak.find('option').clone();

            // --- 1. INISIALISASI SELECT2 FORM INPUT (FORM TAMBAH) ---
            $('.select2-target').each(function() {
                var $el = $(this);
                $el.select2({
                    theme: 'bootstrap-5',
                    placeholder: '--- Cari / Ketik Baru ---',
                    allowClear: true,
                    tags: true,
                    dropdownAutoWidth: true,
                    dropdownParent: $el.parent(),
                    createTag: function (params) {
                        var term = $.trim(params.term);
                        if (term === '') { return null; }
                        return {
                            id: term,
                            text: term + ' (Simpan Baru)',
                            newTag: true
                        }
                    }
                });
            });

            // --- 2. OTOMATIS AMBIL NAMA BARANG DARI URL PARAMETER `?merk=...` ---
            const urlParams = new URLSearchParams(window.location.search);
            const merkFromUrl = urlParams.get('merk');

            if (merkFromUrl) {
                // Masukkan nilai merk dari URL ke Select2 Merk / Anak Barang (#selectAnak)
                if ($selectAnak.find("option[value='" + merkFromUrl + "']").length === 0) {
                    var newOpt = new Option(merkFromUrl + ' (Simpan Baru)', merkFromUrl, true, true);
                    $selectAnak.append(newOpt).trigger('change');
                } else {
                    $selectAnak.val(merkFromUrl).trigger('change');
                }

                // Buka otomatis dropdown Master Barang (#selectMaster) agar user langsung memilih kelompoknya
                setTimeout(function() {
                    $('#selectMaster').select2('open');
                }, 300);
            }

            // --- 3. OTOMATIS TAMPILKAN SWEETALERT JIKA ADA FLASH SESSION SUCCESS ---
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 2000,
                    customClass: {
                        popup: 'border-radius-10'
                    }
                });
            @endif

            // --- 4. CEGAT FORM HAPUS DAN GANTI DENGAN SWEETALERT CONFIRMATION ---
            $('#tableBarang').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                
                var form = this;

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data inventaris ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // --- 5. LOGIKA FILTER ANAK BARANG (FORM TAMBAH) ---
            $('#selectMaster').on('change', function() {
                var selectedMaster = $(this).val();
                
                $selectAnak.select2('destroy');
                $selectAnak.empty();
                $selectAnak.append('<option value=""></option>');

                if (selectedMaster !== "") {
                    anakOptionsBackup.each(function() {
                        var optionValue = $(this).val();
                        var parentData = $(this).data('parent') || '';
                        
                        if (parentData == selectedMaster || optionValue.indexOf(selectedMaster) !== -1) {
                            $selectAnak.append($(this).clone());
                        }
                    });
                } else {
                    anakOptionsBackup.each(function() {
                        if ($(this).val() !== "") {
                            $selectAnak.append($(this).clone());
                        }
                    });
                }

                $selectAnak.select2({
                    theme: 'bootstrap-5',
                    placeholder: '--- Cari / Ketik Baru ---',
                    allowClear: true,
                    tags: true,
                    dropdownAutoWidth: true,
                    dropdownParent: $selectAnak.parent(),
                    createTag: function (params) {
                        var term = $.trim(params.term);
                        if (term === '') { return null; }
                        return {
                            id: term,
                            text: term + ' (Simpan Baru)',
                            newTag: true
                        }
                    }
                });

                // Apabila ada nilai dari URL parameter, pertahankan nilainya
                if (merkFromUrl) {
                    if ($selectAnak.find("option[value='" + merkFromUrl + "']").length === 0) {
                        var newOpt = new Option(merkFromUrl, merkFromUrl, true, true);
                        $selectAnak.append(newOpt);
                    }
                    $selectAnak.val(merkFromUrl).trigger('change');
                } else {
                    $selectAnak.val('').trigger('change');
                }
            });

            // --- 6. CONFIG DATATABLES ---
            var table = $('#tableBarang').DataTable({
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                columnDefs: [
                    { "visible": false, "targets": 0 }, 
                    { "orderable": false, "targets": 4 } 
                ],
                order: [[0, 'asc']],
                pageLength: 15, 
                lengthMenu: [[15, 25, 50, -1], [15, 25, 50, "Semua"]], 
                rowGroup: {
                    dataSrc: 0,
                    startRender: function (rows, group) {
                        return '📦 ' + group + ' (' + rows.count() + ' Item)';
                    }
                }
            });

            $('#btnClearSearch').on('click', function() {
                table.search('').columns().search('').draw();
            });

            // --- 7. CONFIG SELECT2 EDIT (MODE TAGGING) ---
            function initSelect2Edit() {
                $('.select2-edit').each(function() {
                    var $el = $(this);
                    if ($el.hasClass("select2-hidden-accessible")) {
                        $el.select2('destroy');
                    }
                    $el.select2({
                        theme: 'bootstrap-5',
                        placeholder: '--- Cari / Ketik Baru ---',
                        allowClear: true,
                        tags: true, 
                        dropdownAutoWidth: true,
                        dropdownParent: $('#modalEditBarang'),
                        createTag: function (params) {
                            var term = $.trim(params.term);
                            if (term === '') { return null; }
                            return {
                                id: term,
                                text: term + ' (Simpan Baru)',
                                newTag: true
                            }
                        }
                    });
                });
            }

            // Helper suntik nilai tagging
            function setSelect2Tagging(selector, value) {
                var $el = $(selector);
                if ($el.find("option[value='" + value + "']").length === 0) {
                    var newOption = new Option(value, value, true, true);
                    $el.append(newOption);
                } else {
                    $el.val(value);
                }
                $el.trigger('change');
            }

            // --- 8. EVENT CAPTURE DATA KETIKA TOMBOL EDIT DIKLIK ---
            $('#tableBarang').on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                var master = $(this).data('master');
                var merk = $(this).data('merk');
                var spesifikasi = $(this).data('spesifikasi');
                var satuan = $(this).data('satuan');

                var updateUrl = "{{ route('barang.update', ':id') }}";
                updateUrl = updateUrl.replace(':id', id);
                $('#formEditBarang').attr('action', updateUrl);

                $('#editSpesifikasi').val(spesifikasi);

                initSelect2Edit();

                setSelect2Tagging('#editMaster', master);
                setSelect2Tagging('#editAnak', merk);
                setSelect2Tagging('#editSatuan', satuan);
            });

            $('#modalEditBarang').on('shown.bs.modal', function () {
                initSelect2Edit();
            });

        });
    })(jQuery);

    // --- FUNGSI GLOBAL UNTUK MODAL EDIT ---
    function bukaModalEdit(button) {
        var id = button.getAttribute('data-id');
        var master = button.getAttribute('data-master');
        var merk = button.getAttribute('data-merk');
        var spesifikasi = button.getAttribute('data-spesifikasi');
        var satuan = button.getAttribute('data-satuan');

        var updateUrl = "{{ route('barang.update', ':id') }}";
        updateUrl = updateUrl.replace(':id', id);
        document.getElementById('formEditBarang').setAttribute('action', updateUrl);

        document.getElementById('editSpesifikasi').value = spesifikasi;

        if (window.jQuery) {
            var $ = window.jQuery;
            
            $('.select2-edit').each(function() {
                var $el = $(this);
                if (!$el.hasClass("select2-hidden-accessible")) {
                    $el.select2({
                        theme: 'bootstrap-5',
                        placeholder: '--- Cari / Ketik Baru ---',
                        allowClear: true,
                        tags: true,
                        dropdownAutoWidth: true,
                        dropdownParent: $('#modalEditBarang')
                    });
                }
            });

            function setSelect2Value(selector, val) {
                var $el = $(selector);
                if ($el.find("option[value='" + val + "']").length === 0) {
                    var newOption = new Option(val, val, true, true);
                    $el.append(newOption);
                } else {
                    $el.val(val);
                }
                $el.trigger('change');
            }

            setSelect2Value('#editMaster', master);
            setSelect2Value('#editAnak', merk);
            setSelect2Value('#editSatuan', satuan);
        }
    }
</script>
@endsection