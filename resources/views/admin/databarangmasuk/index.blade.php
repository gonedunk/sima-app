@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/rowGroup.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<style>
    tr.dtrg-group th {
        background-color: #f8f9fa !important;
        font-weight: 700 !important;
        color: #212529 !important;
        border-top: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
        padding: 8px 12px !important;
    }
    .table-primary-light { background-color: rgba(13, 110, 253, 0.05) !important; }
    .w-fit { width: fit-content; }
    .row-disabled { background-color: #f2f2f2 !important; opacity: 0.6; }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold mb-0">Data Barang Masuk</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Logistik</a></li>
                <li class="breadcrumb-item active">Barang Masuk</li>
            </ol>
        </div>
        
        {{-- TOMBOL EXPORT & IMPORT EXCEL --}}
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                <i class="fa-solid fa-file-excel me-1"></i> Import Excel
            </button>
            <a href="{{ route('barang-masuk.export') }}" class="btn btn-outline-success shadow-sm">
                <i class="fa-solid fa-file-export me-1"></i> Export Excel
            </a>
        </div>
    </div>

    {{-- TABEL PENDING ITEM IMPORT (TETAP TAMPIL SELAMA MASIH ADA DATA DI SESSION) --}}
    @if(session('unregistered_items') && count(session('unregistered_items')) > 0)
    <div class="card shadow-sm border-warning border-2 rounded-3 mb-4">
        <div class="card-header bg-warning text-dark py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>Item Hasil Import Belum Terdaftar di Master Barang
            </h5>
            <span class="badge bg-dark text-white px-3 py-2 fs-7">
                {{ count(session('unregistered_items')) }} Item Perlu Ditambahkan
            </span>
        </div>
        <div class="card-body">
            <div class="alert alert-light border-warning text-dark py-2 fs-7 mb-3">
                <i class="fa-solid fa-circle-info me-1 text-warning"></i>
                Barang-barang berikut gagal diimport otomatis karena merk barang <strong>belum terdaftar</strong>. Klik <strong>"Tambah Ke Master"</strong> untuk mendaftarkan barang baru tersebut. Tabel ini akan otomatis hilang jika seluruh item sudah terdaftar.
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0" id="tablePendingItems">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th>Merk / Anak Barang (File Import)</th>
                            <th width="12%" class="text-center">Jumlah Masuk</th>
                            <th width="20%">Nama Supplier</th>
                            <th width="12%" class="text-center">Tgl Masuk</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('unregistered_items') as $key => $item)
                        <tr>
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td><strong class="text-danger">{{ $item['merkBarang'] }}</strong></td>
                            <td class="text-center"><span class="badge bg-secondary px-2.5 py-1.5 fs-7">{{ $item['jumlah'] }} Pcs</span></td>
                            <td>{{ $item['namaSupplier'] }}</td>
                            <td class="text-center">{{ date('d-m-Y', strtotime($item['tglMasuk'])) }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- Route barang.index mengarahkan ke Master Barang dengan parameter merk --}}
                                    <a href="{{ route('barang.index', ['merk' => $item['merkBarang']]) }}" 
                                       class="btn btn-sm btn-primary rounded-2 px-2.5 py-1" 
                                       target="_blank"
                                       title="Tambah ke Master Barang">
                                        <i class="fa-solid fa-plus-circle me-1"></i> Tambah Ke Master
                                    </a>
                                    
                                    {{-- Form Hapus dari List Pending jika tidak ingin diimport --}}
                                    <form action="{{ route('barang-masuk.remove-pending', $key) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Abaikan Item">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- FORM INPUT UTAMA --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="card-title mb-0"><i class="fa-solid fa-plus me-2"></i>Tambah Transaksi Barang Masuk</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('barang-masuk.store') }}" method="POST" id="formBarangMasuk">
                @csrf
                
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="tglMasuk" class="form-label fw-bold">Tanggal Masuk</label>
                        <input type="date" name="tglMasuk" id="tglMasuk" class="form-control" value="{{ old('tglMasuk', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="namaSupplier" class="form-label fw-bold">Nama Supplier</label>
                        <input type="text" name="namaSupplier" id="namaSupplier" class="form-control" placeholder="Masukkan nama supplier" value="{{ old('namaSupplier') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="penerima" class="form-label fw-bold">Penerima</label>
                        <select name="penerima" id="penerima" class="form-select select2-penerima" required>
                            <option value="">-- Pilih Dosen --</option>
                            @foreach($dosenPenerima as $dsn)
                                <option value="{{ $dsn->nip }}" {{ old('penerima') == $dsn->nip ? 'selected' : '' }}>{{ $dsn->nama }} (NIP. {{ $dsn->nip ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-start mb-4">
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Seluruh Transaksi
                    </button>
                </div>
              
                <hr class="mb-4">
                <h6 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-boxes-stacked me-2"></i>Item Barang Yang Diterima</h6>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-hover align-middle w-100" id="tableInputBarang">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center"><input type="checkbox" id="checkAllInput" class="form-check-input"></th>
                                <th>Master Barang</th>
                                <th width="20%">Merk Barang</th>
                                <th width="25%">Spesifikasi</th>
                                <th width="15%" class="text-center">Jumlah Masuk</th>
                                <th width="35%">Status & Kelola Kekurangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($masterBarang as $index => $barang)
                            @php
                                $ketTerakhir = $barang->keterangan_terakhir ?? '';
                                $isKurang = str_contains($ketTerakhir, 'Kurang') || str_starts_with($ketTerakhir, '-');
                                $angkaKurang = (int) preg_replace('/[^\d]/', '', $ketTerakhir);
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input row-checkbox" data-tgl-terakhir="{{ $barang->tgl_terakhir ?? '' }}">
                                    <input type="hidden" class="input-id-anak" value="{{ $barang->id }}">
                                </td>
                                <td>{{ $barang->nama_master }}</td>
                                <td><span class="fw-semibold text-dark">{{ $barang->merkBarang }}</span></td>
                                <td><small class="text-muted">{{ $barang->spesifikasi ?? '-' }}</small></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm text-center input-jumlah" min="1" placeholder="0" disabled>
                                </td>
                                <td>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-sm-5">
                                            <select class="form-select form-select-sm select-status-kirim" disabled>
                                                <option value="Cukup" {{ !$isKurang ? 'selected' : '' }}>Cukup</option>
                                                <option value="Kurang" {{ $isKurang ? 'selected' : '' }}>Kurang (Hutang)</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-7">
                                            <div class="input-group input-group-sm wrapper-input-kurang {{ $isKurang ? '' : 'd-none' }}">
                                                <span class="input-group-text bg-danger text-white">-</span>
                                                <input type="number" class="form-control text-center text-danger fw-bold input-jumlah-kurang" 
                                                       value="{{ $angkaKurang > 0 ? $angkaKurang : '' }}" 
                                                       min="1" placeholder="Sisa kurang" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL HISTORI TRANSAKSI --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0 text-secondary"><i class="fa-solid fa-table me-2"></i>Histori Transaksi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="tableHistoriBarang">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Masuk</th>
                            <th>Nama Barang</th>
                            <th>Spesifikasi</th>
                            <th>Nama Supplier</th>
                            <th>Penerima</th>
                            <th>Jumlah Masuk</th>
                            <th>Keterangan Status</th>
                            <th width="8%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($barangMasuk as $bm)
                        <tr>
                            <td>{{ date('d-m-Y', strtotime($bm->tglMasuk)) }}</td>
                            <td><span class="fw-semibold text-dark">{{ $bm->merkBarang }}</span></td>
                            <td><small class="text-muted">{{ $bm->spesifikasi ?? '-' }}</small></td>
                            <td>{{ $bm->namaSupplier }}</td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $bm->nama_dosen ?? $bm->nip_penerima }}</span>
                                @if($bm->nama_dosen)
                                    <br><small class="text-muted" style="font-size: 0.75rem;">NIP. {{ $bm->nip_penerima }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary px-2.5 py-1.5 fs-7">{{ $bm->jumlah }} Pcs</span></td>
                            <td>
                                @if(str_contains($bm->keterangan, 'Kurang') || str_starts_with($bm->keterangan, '-'))
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge bg-danger px-2 py-1 fs-7 fw-bold w-fit align-self-start">
                                            <i class="fa-solid fa-circle-down me-1"></i>Kurang (Hutang)
                                        </span>
                                        <small class="text-danger fw-bold ms-1">
                                            Sisa: <span class="badge bg-outline-danger border border-danger text-danger py-0.5 px-1.5 rounded">{{ preg_replace('/[^\d]/', '', $bm->keterangan) ?: '0' }}</span> Pcs
                                        </small>
                                    </div>
                                @else
                                    <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Cukup</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('barang-masuk.destroy', $bm->id) }}" method="POST" class="d-inline form-delete-histori">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-2 py-1 px-2" title="Hapus Data">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
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

{{-- MODAL IMPORT EXCEL --}}
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-labelledby="modalImportExcelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalImportExcelLabel"><i class="fa-solid fa-file-excel me-2"></i>Import Data Barang Masuk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('barang-masuk.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_excel" class="form-label fw-bold">Pilih File Excel (.xlsx, .xls, .csv)</label>
                        <input type="file" class="form-control" name="file_excel" id="file_excel" accept=".xlsx, .xls, .csv" required>
                    </div>
                    <div class="alert alert-info py-2 fs-7 mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Pastikan header kolom file Excel sesuai: <strong>tglMasuk, merkBarang, jumlah, namaSupplier, penerima</strong>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-upload me-1"></i> Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('js/dataTables.rowGroup.min.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

<script>
    $(document).ready(function() {
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", confirmButtonColor: '#0d6efd' });
        @endif

        @if(session('warning'))
            Swal.fire({ icon: 'warning', title: 'Perhatian!', text: "{{ session('warning') }}", confirmButtonColor: '#ffc107' });
        @endif

        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal Transaksi!', text: "{{ session('error') }}", confirmButtonColor: '#dc3545' });
        @endif

        const tableInput = $('#tableInputBarang').DataTable({
            "pageLength": 10,
            "columnDefs": [
                { "visible": false, "targets": 1 }, 
                { "orderable": false, "targets": [0, 4, 5] }
            ],
            "order": [[1, 'asc']],
            "rowGroup": {
                "dataSrc": 1,
                "startRender": function (rows, group) {
                    return $('<tr/>').append('<td colspan="5"><i class="fa-solid fa-folder-open me-2 text-primary"></i>Kategori: ' + group + '</td>');
                }
            }
        });

        $('#tableHistoriBarang').DataTable({
            "pageLength": 10,
            "order": [[0, "desc"]],
            "columnDefs": [{ "orderable": false, "targets": 7 }]
        });

        $('.select2-penerima').select2({ theme: 'bootstrap-5', width: '100%' });

        function validateDatesAndToggleCheckboxes() {
            const inputTgl = $('#tglMasuk').val();
            if (!inputTgl) return;

            const rows = tableInput.rows().nodes().to$();

            rows.each(function() {
                const row = $(this);
                const checkbox = row.find('.row-checkbox');
                const tglTerakhirBarang = checkbox.attr('data-tgl-terakhir');

                if (tglTerakhirBarang && tglTerakhirBarang !== '') {
                    if (inputTgl < tglTerakhirBarang) {
                        checkbox.prop('checked', false).prop('disabled', true);
                        row.addClass('row-disabled');
                        toggleRowState(row, false);
                    } else {
                        checkbox.prop('disabled', false);
                        row.removeClass('row-disabled');
                    }
                } else {
                    checkbox.prop('disabled', false);
                    row.removeClass('row-disabled');
                }
            });
        }

        validateDatesAndToggleCheckboxes();
        $('#tglMasuk').on('change', function() {
            validateDatesAndToggleCheckboxes();
        });

        function toggleRowState(row, isChecked) {
            const $row = $(row);
            const inputJumlah = $row.find('.input-jumlah');
            const selectStatus = $row.find('.select-status-kirim');
            const wrapperInputKurang = $row.find('.wrapper-input-kurang');
            const inputJumlahKurang = $row.find('.input-jumlah-kurang');

            if (isChecked) {
                $row.addClass('table-primary-light');
                inputJumlah.prop('disabled', false);
                selectStatus.prop('disabled', false);
                if (selectStatus.val() === 'Kurang') {
                    wrapperInputKurang.removeClass('d-none');
                    inputJumlahKurang.prop('disabled', false);
                }
            } else {
                $row.removeClass('table-primary-light');
                inputJumlah.prop('disabled', true).val('');
                selectStatus.prop('disabled', true);
                wrapperInputKurang.addClass('d-none');
                inputJumlahKurang.prop('disabled', true);
            }
        }

        $(document).on('change', '.row-checkbox', function() {
            const row = $(this).closest('tr');
            toggleRowState(row, this.checked);

            if (!this.checked) {
                $('#checkAllInput').prop('checked', false);
            }
        });

        $('#checkAllInput').on('change', function() {
            const isChecked = this.checked;
            const rows = tableInput.rows().nodes().to$();

            rows.each(function() {
                const row = $(this);
                const checkbox = row.find('.row-checkbox');

                if (!checkbox.prop('disabled')) {
                    checkbox.prop('checked', isChecked);
                    toggleRowState(row, isChecked);
                }
            });
        });

        $(document).on('change', '.select-status-kirim', function() {
            const row = $(this).closest('tr');
            const wrapperInputKurang = row.find('.wrapper-input-kurang');
            const inputJumlahKurang = row.find('.input-jumlah-kurang');

            if ($(this).val() === 'Kurang') {
                wrapperInputKurang.removeClass('d-none');
                inputJumlahKurang.prop('disabled', false);
            } else {
                wrapperInputKurang.addClass('d-none');
                inputJumlahKurang.prop('disabled', true);
            }
        });

        $('#formBarangMasuk').on('submit', function(e) {
            e.preventDefault();
            const form = this;

            const checkedNodes = tableInput.rows().nodes().to$().find('.row-checkbox:checked');

            if (checkedNodes.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Barang Belum Dipilih!',
                    text: 'Harap centang minimal 1 checkbox barang pada tabel.',
                    confirmButtonColor: '#0d6efd'
                });
                return false;
            }

            let hasError = false;
            $(form).find('.temp-submit-input').remove();

            checkedNodes.each(function(idx) {
                const row = $(this).closest('tr');
                const idAnak = row.find('.input-id-anak').val();
                const jumlah = row.find('.input-jumlah').val();
                const statusKirim = row.find('.select-status-kirim').val();
                const jumlahKurangInput = row.find('.input-jumlah-kurang').val();

                if (!jumlah || parseInt(jumlah) <= 0) {
                    hasError = true;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Jumlah Masuk Kosong!',
                        text: 'Mohon lengkapi jumlah masuk pada setiap item barang yang dicentang.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return false;
                }

                $(form).append(`
                    <input type="hidden" class="temp-submit-input" name="items[${idx}][selected]" value="1">
                    <input type="hidden" class="temp-submit-input" name="items[${idx}][idAnak]" value="${idAnak}">
                    <input type="hidden" class="temp-submit-input" name="items[${idx}][jumlah]" value="${jumlah}">
                    <input type="hidden" class="temp-submit-input" name="items[${idx}][status_kirim]" value="${statusKirim}">
                    <input type="hidden" class="temp-submit-input" name="items[${idx}][jumlah_kurang_input]" value="${jumlahKurangInput}">
                `);
            });

            if (hasError) return false;

            Swal.fire({
                title: 'Simpan Transaksi?',
                text: `Anda akan menyimpan ${checkedNodes.length} transaksi item barang masuk.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        $(document).on('submit', '.form-delete-histori', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Hapus Histori?',
                text: 'Data transaksi yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection