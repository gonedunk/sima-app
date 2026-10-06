@extends('layouts.app')

@section('title', 'Data Barang Keluar - SIMA PRO')

@section('styles')
    {{-- Asset CSS dari public/css/ --}}
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.min.css') }}" />

    <style>
        #formHeader { transition: background-color 0.3s ease, color 0.3s ease; }
        .bg-primary { background-color: #0d6efd !important; }
        .bg-warning { background-color: #ffc107 !important; }
        
        .select2-container--bootstrap-5 { z-index: 1060 !important; }
        .select2-result-barang__title { font-weight: 600; color: #212529; font-size: 0.875rem; }
        .select2-result-barang__meta { font-size: 0.75rem; color: #6c757d; margin-top: 1px; }

        .select2-container--bootstrap-5 .select2-results__options {
            max-height: 240px !important;
            overflow-y: auto !important;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Alert Notifikasi Sistem -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><strong>Berhasil!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Gagal!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- FORM INPUT BARANG KELUAR -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 76px; z-index: 10;">
                <div class="card-header py-3 bg-primary text-white" id="formHeader">
                    <h6 class="card-title mb-0 fw-bold text-white" id="formTitle">
                        <i class="bi bi-plus-lg me-2"></i>Tambah Barang Keluar
                    </h6>
                </div>
                <div class="card-body p-3" id="formCardBody">
                    <!-- Container Notifikasi Validasi Stok Real-time -->
                    <div id="stokValidationAlert" class="alert alert-danger d-none border-0 small py-2 shadow-sm mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span id="stokValidationErrorText">Jumlah permintaan lebih besar dari jumlah stok!</span>
                    </div>

                    <!-- Container Notifikasi Validasi Tanggal Keluar -->
                    <div id="tglValidationAlert" class="alert alert-danger d-none border-0 small py-2 shadow-sm mb-3" role="alert">
                        <i class="bi bi-calendar-x-fill me-2"></i>
                        <span id="tglValidationErrorText">Tanggal keluar tidak boleh lebih kecil dari tanggal barang pertama kali masuk!</span>
                    </div>

                    <form action="{{ route('barang-keluar.store') }}" method="POST" id="formTransaksi">
                        @csrf
                        <div id="methodContainer"></div>

                        <!-- 1. TANGGAL -->
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Tanggal Keluar</label>
                            <input type="date" name="tglKeluar" id="tglKeluar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <!-- 2. SELECT2 MASTER BARANG -->
                        <div class="mb-2 position-relative">
                            <label class="form-label small fw-semibold">Master Barang</label>
                            <select id="idMaster" class="form-select form-select-sm select2-master" style="width: 100%;">
                                <option value=""></option>
                                @foreach($masterBarang as $mb)
                                    <option value="{{ $mb->id }}">{{ $mb->namaBarang }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 3. SELECT2 ANAK BARANG -->
                        <div class="mb-2 position-relative">
                            <label class="form-label small fw-semibold">Anak Barang (Detail)</label>
                            <select name="idAnak" id="idAnak" class="form-select form-select-sm select2-anak" required style="width: 100%;" disabled>
                                <option value=""></option>
                                @foreach($anakBarang as $ab)
                                    <option value="{{ $ab->id }}" 
                                            data-idmaster="{{ $ab->idMaster }}"
                                            data-merk="{{ $ab->merkBarang }}"
                                            data-spek="{{ $ab->spesifikasi }}"
                                            data-stok="{{ $ab->stokRealtime }}"
                                            data-tglmasuk="{{ $ab->tglMasukPertama ?? '' }}">
                                        {{ $ab->merkBarang }} ({{ $ab->spesifikasi ?? 'Tanpa Spesifikasi' }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="infoStokContainer" class="mt-1 d-none">
                                <small class="text-muted d-block" style="font-size: 0.78rem;">
                                    <i class="bi bi-box-seam me-1"></i> Sisa Stok Aktual: 
                                    <b id="textStokAkhir" class="text-primary">0</b> Pcs
                                </small>
                            </div>
                        </div>

                        <!-- 4. JUMLAH -->
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Jumlah Keluar</label>
                            <input type="number" name="jumlah" id="jumlah" min="1" class="form-control form-control-sm" placeholder="0" required>
                        </div>

                        <!-- 5. PETUGAS & PENERIMA -->
                        <div class="row g-2">
                            <div class="col-6 mb-2 position-relative">
                                <label class="form-label small fw-semibold">Petugas</label>
                                <select name="petugas" id="petugas" class="form-select form-select-sm select2-dosen" required style="width: 100%;">
                                    <option value=""></option>
                                    @foreach($dosenPetugas as $dsn)
                                        <option value="{{ $dsn->nip }}">{{ $dsn->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 mb-2 position-relative">
                                <label class="form-label small fw-semibold">Penerima</label>
                                <select name="penerima" id="penerima" class="form-select form-select-sm select2-dosen" required style="width: 100%;">
                                    <option value=""></option>
                                    @foreach($dosenPenerima as $dsn)
                                        <option value="{{ $dsn->nip }}">{{ $dsn->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- 6. CATATAN -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" id="catatan" rows="2" class="form-control form-control-sm" placeholder="Catatan tambahan..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" id="btnBatalEdit" class="btn btn-secondary btn-sm w-50 fw-semibold d-none">Batal</button>
                            <button type="submit" id="btnSubmit" class="btn btn-primary btn-sm w-100 fw-semibold">Simpan Transaksi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABEL DATA TRANSAKSI BOOTSTRAP 5 (DATATABLES) -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-list-ul me-2"></i>Daftar Transaksi Barang Keluar
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="tabelBarangKeluar">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="text-center" style="width: 80px;">Tanggal</th>
                                    <th>Detail Barang</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Stok Snapshot</th>
                                    <th>Petugas / Penerima</th>
                                    <th>Catatan</th>
                                    <th class="text-end pe-3" style="width: 90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($barangKeluar as $row)
                                <tr>
                                    <!-- 1. Tanggal -->
                                    <td class="text-center fw-semibold">
                                        {{ \Carbon\Carbon::parse($row->tglKeluar)->format('d/m/Y') }}
                                    </td>

                                    <!-- 2. Detail Barang -->
                                    <td>
                                        <div class="fw-bold text-dark">{{ $row->merkBarang ?? 'Merk Tidak Ditemukan' }}</div>
                                        <div class="text-muted small" style="font-size: 0.78rem;">
                                            <i class="bi bi-gear me-1"></i>{{ $row->spesifikasi ?? 'Tanpa Spesifikasi' }}
                                        </div>
                                    </td>

                                    <!-- 3. Jumlah -->
                                    <td class="text-center">
                                        <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1">- {{ $row->jumlah }} Pcs</span>
                                    </td>

                                    <!-- 4. Stok Snapshot -->
                                    <td class="text-center">
                                        <span class="fw-bold text-secondary">{{ $row->stokAkhir ?? 0 }}</span> <span class="text-muted small">Pcs</span>
                                    </td>

                                    <!-- 5. Petugas / Penerima -->
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $row->nama_petugas ?? $row->petugas }}</div>
                                        <div class="text-muted" style="font-size: 0.8rem;">Penerima: {{ $row->nama_penerima ?? $row->penerima }}</div>
                                    </td>

                                    <!-- 6. Catatan -->
                                    <td class="text-muted">{{ $row->catatan ?? '-' }}</td>

                                    <!-- 7. Aksi -->
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button" 
                                                    class="btn btn-outline-warning btn-sm btn-edit-row"
                                                    data-id="{{ $row->id }}"
                                                    data-idanak="{{ $row->idAnak }}"
                                                    data-tgl="{{ $row->tglKeluar }}"
                                                    data-jumlah="{{ $row->jumlah }}"
                                                    data-petugas="{{ $row->petugas }}"
                                                    data-penerima="{{ $row->penerima }}"
                                                    data-catatan="{{ $row->catatan }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            
                                            <form action="{{ route('barang-keluar.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Hapus transaksi ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
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
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{-- Asset JS Lokal dari public/js/ --}}
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(document).ready(function() {
        var urlStore = "{{ route('barang-keluar.store') }}";
        var tglHariIni = "{{ date('Y-m-d') }}";
        
        var currentStokAkhir = 0; 
        var tglMasukPertama = '';
        var $anakSelect = $('#idAnak');
        var $allOptions = $anakSelect.find('option').clone();

        // 1. Inisialisasi DataTables Bootstrap 5
        var table = $('#tabelBarangKeluar').DataTable({
            language: {
                search: "Cari Data:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                zeroRecords: "Tidak ada data transaksi ditemukan.",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: "Lanjut",
                    previous: "Kembali"
                }
            }
        });

        // 2. Inisialisasi Select2 Master Barang
        $('.select2-master').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Pilih Kelompok Master...',
            dropdownParent: $('#formCardBody')
        });

        // 3. Tampilan Dropdown Anak Barang
        function formatAnakBarang(state) {
            if (!state.id) { return state.text; }
            var element = $(state.element);
            var merk = element.data('merk') || '-';
            var spek = element.data('spek') || 'Tanpa Spesifikasi';
            var stok = element.data('stok') !== undefined ? parseInt(element.data('stok')) : 0;

            var badgeColor = stok > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';

            return $(
                '<div class="select2-result-barang d-flex justify-content-between align-items-center py-1">' +
                    '<div>' +
                        '<div class="select2-result-barang__title">' + state.text + '</div>' +
                        '<div class="select2-result-barang__meta">' +
                            '<span class="me-3"><i class="bi bi-tag me-1"></i>Merk: <b>' + merk + '</b></span>' +
                            '<span><i class="bi bi-gear me-1"></i>Spek: ' + spek + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="text-end ms-2">' +
                        '<span class="badge ' + badgeColor + ' fw-bold px-2 py-1" style="font-size:0.75rem;">Stok: ' + stok + '</span>' +
                    '</div>' +
                '</div>'
            );
        }

        function initSelect2Anak() {
            $anakSelect.select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'Pilih detail anak barang...',
                templateResult: formatAnakBarang,
                dropdownParent: $('#formCardBody')
            });
        }
        initSelect2Anak();

        $('.select2-dosen').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Pilih Personel...',
            dropdownParent: $('#formCardBody')
        });

        // Chained Dropdown
        $('#idMaster').on('change', function() {
            var selectedMasterId = $(this).val();
            
            $anakSelect.val(null).trigger('change');
            $('#infoStokContainer').addClass('d-none');
            currentStokAkhir = 0;
            tglMasukPertama = '';
            validateAll();

            if (!selectedMasterId) {
                $anakSelect.prop('disabled', true);
            } else {
                $anakSelect.prop('disabled', false);
                $anakSelect.empty().append('<option value=""></option>');
                
                $allOptions.each(function() {
                    if ($(this).data('idmaster') == selectedMasterId) {
                        $anakSelect.append($(this).clone());
                    }
                });
            }
            initSelect2Anak();
        });

        $anakSelect.on('change', function() {
            var idAnak = $(this).val();
            if (!idAnak) {
                $('#infoStokContainer').addClass('d-none');
                currentStokAkhir = 0;
                tglMasukPertama = '';
                validateAll();
                return;
            }

            var selectedOption = $(this).find('option:selected');
            currentStokAkhir = parseInt(selectedOption.data('stok')) || 0;
            tglMasukPertama = selectedOption.data('tglmasuk') || '';

            $('#textStokAkhir').text(currentStokAkhir);
            $('#infoStokContainer').removeClass('d-none');
            
            validateAll();
        });

        $('#jumlah, #tglKeluar').on('input change', function() {
            validateAll();
        });

        // Fungsi Validasi Stok & Tanggal
        function validateAll() {
            var jmlInput = parseInt($('#jumlah').val()) || 0;
            var tglKeluarVal = $('#tglKeluar').val();
            var idAnakSelected = $anakSelect.val();

            var isStokValid = true;
            var isTglValid = true;

            // Validasi Stok
            if (idAnakSelected && jmlInput > 0) {
                if (jmlInput > currentStokAkhir) {
                    $('#stokValidationAlert').removeClass('d-none');
                    isStokValid = false;
                } else {
                    $('#stokValidationAlert').addClass('d-none');
                }
            } else {
                $('#stokValidationAlert').addClass('d-none');
            }

            // Validasi Tanggal Keluar vs Tanggal Masuk Pertama
            if (idAnakSelected && tglKeluarVal && tglMasukPertama) {
                if (tglKeluarVal < tglMasukPertama) {
                    $('#tglValidationErrorText').text('Tanggal keluar tidak boleh lebih kecil dari tanggal barang pertama kali masuk (' + tglMasukPertama + ')!');
                    $('#tglValidationAlert').removeClass('d-none');
                    isTglValid = false;
                } else {
                    $('#tglValidationAlert').addClass('d-none');
                }
            } else {
                $('#tglValidationAlert').addClass('d-none');
            }

            // Atur Status Tombol Submit
            if (isStokValid && isTglValid) {
                $('#btnSubmit').prop('disabled', false).removeClass('opacity-50');
                return true;
            } else {
                $('#btnSubmit').prop('disabled', true).addClass('opacity-50');
                return false;
            }
        }

        $('#formTransaksi').on('submit', function(e) {
            if (!validateAll()) {
                e.preventDefault();
                alert("Transaksi Dibatalkan! Mohon periksa kembali stok atau tanggal pengeluaran barang.");
                return false;
            }
        });

        // Trigger Edit Mode
        $(document).on('click', '.btn-edit-row', function() {
            var id = $(this).data('id');
            var idAnak = $(this).data('idanak');
            var tgl = $(this).data('tgl');
            var jumlah = $(this).data('jumlah');
            var petugas = $(this).data('petugas');
            var penerima = $(this).data('penerima');
            var catatan = $(this).data('catatan');

            $('html, body').animate({ scrollTop: $('#formTransaksi').offset().top - 90 }, 'fast');

            $('#formHeader').removeClass('bg-primary').addClass('bg-warning text-dark');
            $('#formTitle').html('<i class="bi bi-pencil-square me-2"></i>Ubah Data Transaksi').removeClass('text-white').addClass('text-dark');
            $('#btnSubmit').removeClass('btn-primary').addClass('btn-warning text-dark').text('Simpan Perubahan');
            $('#btnBatalEdit').removeClass('d-none');
            $('#btnSubmit').removeClass('w-100').addClass('w-50');

            $('#methodContainer').html('<input type="hidden" name="_method" value="PUT">');
            $('#formTransaksi').attr('action', '/admin/barang-keluar/' + id);

            var targetOption = $allOptions.filter('[value="' + idAnak + '"]');
            var parentMasterId = targetOption.data('idmaster');

            $('#idMaster').val(parentMasterId).trigger('change');
            $anakSelect.val(idAnak).trigger('change');

            $('#petugas').val(petugas).trigger('change');
            $('#penerima').val(penerima).trigger('change');

            $('#tglKeluar').val(tgl);
            $('#jumlah').val(jumlah);
            $('#catatan').val(catatan);
        });

        $('#btnBatalEdit').on('click', function() {
            resetFormToCreateMode();
        });

        function resetFormToCreateMode() {
            $('#formHeader').removeClass('bg-warning text-dark').addClass('bg-primary');
            $('#formTitle').html('<i class="bi bi-plus-lg me-2"></i>Tambah Barang Keluar').removeClass('text-dark').addClass('text-white');
            $('#btnSubmit').removeClass('bg-warning text-dark').addClass('btn-primary').text('Simpan Transaksi');
            $('#btnBatalEdit').addClass('d-none');
            $('#btnSubmit').removeClass('w-50').addClass('w-100');

            $('#methodContainer').html('');
            $('#formTransaksi').attr('action', urlStore);
            $('#formTransaksi')[0].reset();
            
            $('#idMaster').val('').trigger('change');
            $anakSelect.val('').trigger('change').prop('disabled', true);
            $('#petugas').val('').trigger('change');
            $('#penerima').val('').trigger('change');
            
            currentStokAkhir = 0;
            tglMasukPertama = '';
            validateAll();
            
            $('#infoStokContainer').addClass('d-none');
            $('#tglKeluar').val(tglHariIni);
        }
    });
</script>
@endsection