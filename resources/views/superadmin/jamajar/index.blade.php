@extends('layouts.app') {{-- Sesuaikan dengan nama file master layout Anda --}}

@section('content')
<div class="container-fluid p-0">
    <!-- Header Halaman -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Manajemen Jam Ajar</h4>
            <p class="text-muted small mb-0">Kelola data pengaturan jam mengajar normal dan waktu ramadan pada tabel tbjamajar.</p>
        </div>
        <button class="btn btn-primary shadow-sm btn-sm align-self-start align-self-md-center" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus me-1"></i> Tambah Jam Ajar
        </button>
    </div>

    <!-- Kotak Pencarian & Filter Atas -->
    <div class="row mb-3">
        <div class="col-md-4 ms-auto">
            <div class="input-group input-group-sm shadow-sm rounded-2">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass small"></i>
                </span>
                <input type="text" id="customSearchInput" class="form-control border-start-0 ps-0" placeholder="Cari hari, jam, atau program...">
            </div>
        </div>
    </div>

    <!-- Tabel Tampilan Data -->
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableJamAjar">
                    <thead class="table-light text-secondary fw-semibold small">
                        <tr>
                            <th class="ps-4" style="width: 80px;">No</th>
                            <th>Hari</th>
                            <th>Jam Normal</th>
                            <th>Jam Ramadan</th>
                            <th>Program</th>
                            <th class="text-center pe-4" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small" id="tableBodyJamAjar">
                        @forelse($jamajar as $key => $item)
                        <tr class="data-row">
                            <td class="ps-4 fw-medium text-secondary row-no">{{ $key + 1 }}</td>
                            <td class="fw-semibold text-dark searchable-field">
                                @php
                                    $hariArray = explode(', ', $item->hari);
                                @endphp
                                @foreach($hariArray as $h)
                                    <span class="badge bg-light text-dark border px-2 py-1 mb-1">{{ $h }}</span>
                                @endforeach
                            </td>
                            <td class="searchable-field"><i class="fa-regular fa-clock text-primary me-1"></i> {{ $item->jamNormal }}</td>
                            <td class="searchable-field"><i class="fa-solid fa-moon text-warning me-1"></i> {{ $item->jamRamadan }}</td>
                            <td class="searchable-field"><span class="badge bg-light text-primary border px-2 py-1">{{ $item->namaProgram }}</span></td>
                            <td class="text-center pe-4">
                                <button class="btn btn-outline-warning btn-sm border-0" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $item->id }}" title="Ubah Data">
                                    <i class="fa-solid fa-pen-to-square"></i> Ubah
                                </button>
                                
                                <form action="{{ route('jamajar.destroy', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-outline-danger btn-sm border-0 btn-delete-data" title="Hapus Data">
                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>

                        @php
                            $normal = explode(' - ', $item->jamNormal);
                            $ramadan = explode(' - ', $item->jamRamadan);
                            
                            $normal_mulai = isset($normal[0]) ? trim($normal[0]) : '07:30';
                            $normal_selesai = isset($normal[1]) ? trim($normal[1]) : '12:00';
                            
                            $ramadan_mulai = isset($ramadan[0]) ? trim($ramadan[0]) : '08:00';
                            $ramadan_selesai = isset($ramadan[1]) ? trim($ramadan[1]) : '11:30';
                            
                            $listHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                        @endphp

                        <!-- MODAL EDIT DATA -->
                        <div class="modal fade" id="modalEdit{{ $item->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('jamajar.update', $item->id) }}" method="POST" class="form-jamajar-edit">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="hari" id="edit_hari_string_{{ $item->id }}" value="{{ $item->hari }}">
                                        <input type="hidden" name="jamNormal" id="edit_jamNormal_{{ $item->id }}" value="{{ $item->jamNormal }}">
                                        <input type="hidden" name="jamRamadan" id="edit_jamRamadan_{{ $item->id }}" value="{{ $item->jamRamadan }}">

                                        <div class="modal-header bg-warning text-dark border-0 py-3">
                                            <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Ubah Data Jam Ajar</h6>
                                            <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <!-- Select Multiple Hari (Edit) -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary small d-block">Hari</label>
                                                <select id="edit_hari_select_{{ $item->id }}" class="form-select form-select-sm select-hari-edit" style="height: 120px;" multiple required>
                                                    @foreach($listHari as $hari)
                                                        <option value="{{ $hari }}" {{ in_array($hari, $hariArray) ? 'selected' : '' }}>{{ $hari }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="form-text text-muted" style="font-size: 11px;">Tahan tombol <kbd>Ctrl</kbd> untuk memilih lebih dari satu hari.</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary small d-block">Jam Normal</label>
                                                <div class="input-group">
                                                    <input type="time" id="edit_normal_mulai_{{ $item->id }}" class="form-control form-control-sm" value="{{ $normal_mulai }}" required>
                                                    <span class="input-group-text bg-light text-muted small">s/d</span>
                                                    <input type="time" id="edit_normal_selesai_{{ $item->id }}" class="form-control form-control-sm" value="{{ $normal_selesai }}" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary small d-block">Jam Ramadan</label>
                                                <div class="input-group">
                                                    <input type="time" id="edit_ramadan_mulai_{{ $item->id }}" class="form-control form-control-sm" value="{{ $ramadan_mulai }}" required>
                                                    <span class="input-group-text bg-light text-muted small">s/d</span>
                                                    <input type="time" id="edit_ramadan_selesai_{{ $item->id }}" class="form-control form-control-sm" value="{{ $ramadan_selesai }}" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-secondary small">Program</label>
                                                <select name="kodeProgram" class="form-select" required>
                                                    <option value="">-- Pilih Program --</option>
                                                    @foreach($program as $p)
                                                        <option value="{{ $p->kodeProgram }}" {{ $item->kodeProgram == $p->kodeProgram ? 'selected' : '' }}>
                                                            {{ $p->namaProgram }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 bg-light py-2">
                                            <button type="button" class="btn btn-sm btn-secondary text-white" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-sm btn-warning fw-semibold">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- END MODAL EDIT -->

                        @empty
                        <tr id="noDataRow">
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-hourglass-empty d-block fs-3 mb-2 text-secondary"></i>
                                Belum ada data jam ajar yang terdaftar di sistem.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Navigasi Pagination -->
    <div class="d-flex justify-content-between align-items-center px-2 small">
        <div class="text-muted" id="paginationInfo">
            Menampilkan data...
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0" id="paginationContainer">
            </ul>
        </nav>
    </div>
</div>

<!-- MODAL TAMBAH DATA -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('jamajar.store') }}" method="POST" id="formJamajarTambah">
                @csrf
                <input type="hidden" name="hari" id="tambah_hari_string">
                <input type="hidden" name="jamNormal" id="tambah_jamNormal">
                <input type="hidden" name="jamRamadan" id="tambah_jamRamadan">

                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-plus me-2"></i>Tambah Jam Ajar Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Select Multiple Hari (Tambah) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small d-block">Pilih Hari</label>
                        <select id="tambah_hari_select" class="form-select form-select-sm" style="height: 120px;" multiple required>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                        </select>
                        <div class="form-text text-muted" style="font-size: 11px;">Tahan tombol <kbd>Ctrl</kbd> untuk memilih lebih dari satu hari.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small d-block">Jam Normal</label>
                        <div class="input-group">
                            <input type="time" id="tambah_normal_mulai" class="form-control form-control-sm" value="07:30" required>
                            <span class="input-group-text bg-light text-muted small">s/d</span>
                            <input type="time" id="tambah_normal_selesai" class="form-control form-control-sm" value="12:00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small d-block">Jam Ramadan</label>
                        <div class="input-group">
                            <input type="time" id="tambah_ramadan_mulai" class="form-control form-control-sm" value="08:00" required>
                            <span class="input-group-text bg-light text-muted small">s/d</span>
                            <input type="time" id="tambah_ramadan_selesai" class="form-control form-control-sm" value="11:30" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Program</label>
                        <select name="kodeProgram" class="form-select" required>
                            <option value="">-- Pilih Program --</option>
                            @foreach($program as $p)
                                <option value="{{ $p->kodeProgram }}">{{ $p->namaProgram }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary text-white" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- END MODAL TAMBAH -->
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        const rowsPerPage = 25; 
        let currentPage = 1;
        let $rows = $('.data-row');
        let filteredRows = $rows.toArray();

        function updateTable() {
            let totalRows = filteredRows.length;
            let totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
            if (currentPage > totalPages) currentPage = totalPages;

            let start = (currentPage - 1) * rowsPerPage;
            let end = start + rowsPerPage;

            $rows.hide();
            for (let i = start; i < end && i < totalRows; i++) {
                $(filteredRows[i]).show();
            }

            if (totalRows === 0) {
                $('#paginationInfo').text('Menampilkan 0 sampai 0 dari 0 data');
                if ($('#noDataRow').length === 0) {
                    $('#tableBodyJamAjar').append('<tr id="noDataRow"><td colspan="6" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>');
                }
            } else {
                $('#noDataRow').remove();
                $('#paginationInfo').text(`Menampilkan ${start + 1} sampai ${Math.min(end, totalRows)} dari ${totalRows} data`);
            }
            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            let container = $('#paginationContainer');
            container.empty();
            if (totalPages <= 1) return;

            container.append(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">Sebelumnya</a></li>`);
            for (let i = 1; i <= totalPages; i++) {
                container.append(`<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
            }
            container.append(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">Selanjutnya</a></li>`);
        }

        $('#paginationContainer').on('click', 'a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                currentPage = page;
                updateTable();
            }
        });

        $('#customSearchInput').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            filteredRows = $rows.filter(function() {
                let text = $(this).find('.searchable-field').text().toLowerCase();
                return text.indexOf(value) > -1;
            }).toArray();
            currentPage = 1;
            updateTable();
        });

        updateTable();

        // -----------------------------------------------------
        // Logika Penggabungan Data Array Multiple Select
        // -----------------------------------------------------
        
        // Form Tambah
        $('#formJamajarTambah').on('submit', function(e) {
            let selectedHari = $('#tambah_hari_select').val(); // Menghasilkan array, misal: ["Senin", "Selasa"]

            if (!selectedHari || selectedHari.length === 0) {
                e.preventDefault();
                Swal.fire('Peringatan', 'Silakan pilih minimal satu hari!', 'warning');
                return false;
            }

            // Gabungkan array hari menjadi string yang dipisahkan koma
            $('#tambah_hari_string').val(selectedHari.join(', '));
            $('#tambah_jamNormal').val($('#tambah_normal_mulai').val() + ' - ' + $('#tambah_normal_selesai').val());
            $('#tambah_jamRamadan').val($('#tambah_ramadan_mulai').val() + ' - ' + $('#tambah_ramadan_selesai').val());
        });

        // Form Edit
        $('.form-jamajar-edit').on('submit', function(e) {
            let form = $(this);
            let actionUrl = form.attr('action');
            let id = actionUrl.substring(actionUrl.lastIndexOf('/') + 1);

            let selectedHari = $('#edit_hari_select_' + id).val();

            if (!selectedHari || selectedHari.length === 0) {
                e.preventDefault();
                Swal.fire('Peringatan', 'Silakan pilih minimal satu hari!', 'warning');
                return false;
            }

            $('#edit_hari_string_' + id).val(selectedHari.join(', '));
            $('#edit_jamNormal_' + id).val($('#edit_normal_mulai_' + id).val() + ' - ' + $('#edit_normal_selesai_' + id).val());
            $('#edit_jamRamadan_' + id).val($('#edit_ramadan_mulai_' + id).val() + ' - ' + $('#edit_ramadan_selesai_' + id).val());
        });

        // SweetAlert Hapus Data
        $('.btn-delete-data').on('click', function (e) {
            let form = $(this).closest('form');
            Swal.fire({
                title: 'Hapus data jam ajar?',
                text: "Tindakan ini tidak dapat dibatalkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection