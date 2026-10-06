@extends('layouts.app')

@section('styles')
<!-- Mengembalikan Aset CSS ke File Lokal Sesuai Instruksi -->
<link href="{{ asset('css/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />

<style>
    /* Mengatasi hambatan z-index di layar HP / AIO Desktop */
    .select2-container--bootstrap-5 {
        z-index: 99999 !important; 
        display: block !important;
    }
    .select2-container {
        width: 100% !important;
    }
    .select2-dropdown {
        z-index: 99999 !important;
    }
    /* Sembunyikan elemen select asli agar tidak memicu native wheel Android */
    select.pemicu-select2 {
        opacity: 0 !important;
        position: absolute !important;
        z-index: -1 !important;
    }
    /* Kustomisasi font ukuran kecil sweetalert agar serasi dengan SIMA PRO */
    .swal2-popup {
        font-size: 0.85rem !important;
        border-radius: 12px !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Manajemen Pengelola Jurusan</h1>
            <p class="text-muted small mb-0">Halaman khusus Superadmin untuk kelola struktural jabatan fungsional jurusan.</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" onclick="openTambahModal()">
            <i class="fa-solid fa-plus me-1"></i> Tambah Jabatan Baru
        </button>
    </div>

    <!-- TABEL UTAMA PENGELOLA JURUSAN -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="tablePengelola" class="table table-striped table-hover align-middle mb-0 w-100">
                    <thead class="table-dark small text-uppercase">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Dosen / Pengelola</th>
                            <th>Jabatan</th>
                            <th class="text-center">Tanggal Mulai</th>
                            <th class="text-center">Tanggal Selesai</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($pengelola as $index => $item)
                        <tr>
                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->nama_dosen ?? 'Nama Dosen Tidak Ditemukan' }}</div>
                                <small class="text-muted font-monospace">NIP. {{ $item->nip }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary px-3 py-2 fw-semibold rounded">
                                    {{ $item->jabatan }}
                                </span>
                            </td>
                            <td class="text-center fw-semibold">
                                {{ \Carbon\Carbon::parse($item->tanggalMulai)->format('d-m-Y') }}
                            </td>
                            <td class="text-center">
                                @if($item->tanggalSelesai)
                                    <span class="text-danger fw-semibold">{{ \Carbon\Carbon::parse($item->tanggalSelesai)->format('d-m-Y') }}</span>
                                @else
                                    <span class="badge bg-success-subtle text-success px-2 py-1 border border-success-subtle rounded text-xs">Aktif / Sekarang</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-warning text-white me-1" onclick="openEditModal({{ json_encode($item) }})" title="Ubah Data">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger btn-hapus" data-url="{{ route('superadmin.pengelolajurusan.destroy', $item->id) }}" title="Hapus Data">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted p-4">
                                <i class="fa-solid fa-folder-open fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data pengelola jurusan yang tercatat.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="formDeletePengelola" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<!-- MODAL POPUP -->
<div class="modal fade" id="modalPengelola" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalPengelolaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modalPengelolaLabel">Form Pengelola Jurusan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formForm" method="POST">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="POST">
                    
                    <div class="mb-3" id="container-select-dosen">
                        <label class="form-label small fw-bold text-secondary">Pilih Dosen / Pengelola</label>
                        <select name="nip" id="input_nip" class="form-control pemicu-select2" required style="width: 100%;">
                            <option value="">-- Ketik NIP atau Nama Dosen --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Jabatan Struktural</label>
                        <select name="jabatan" id="input_jabatan" class="form-select" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="Ketua Jurusan">1. Ketua Jurusan</option>
                            <option value="Sekretaris Jurusan">2. Sekretaris Jurusan</option>
                            <option value="Ketua Program Studi">3. Ketua Program Studi</option>
                        </select>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Tanggal Mulai</label>
                            <input type="date" name="tanggalMulai" id="input_tanggalMulai" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Tanggal Selesai</label>
                            <input type="date" name="tanggalSelesai" id="input_tanggalSelesai" class="form-control">
                            <span class="text-muted text-xs d-block mt-1">*Kosongkan jika masih aktif</span>
                        </div>
                    </div>

                    <div class="pt-3 border-top text-end">
                        <button type="button" class="btn btn-sm btn-secondary me-1" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-dark px-4" id="btnSimpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Mengembalikan Seluruh Pemanggilan File Library ke Lokal Proyek Anda -->
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    (function($) {
        $(document).ready(function() {
            const modalElement = document.getElementById('modalPengelola');
            const modalInstance = new bootstrap.Modal(modalElement);

            // SWEETALERT NOTIFIKASI DARI BACKEND LARAVEL SESSION
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 2500
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#343a40'
                });
            @endif

            // Fungsi Inisialisasi Paksa Select2 Ajax
            function hancurkanDanMuatSelect2() {
                let target = $('#input_nip');
                
                if (target.hasClass("select2-hidden-accessible")) {
                    target.select2('destroy');
                }

                target.select2({
                    dropdownParent: $('#modalPengelola'),
                    theme: 'bootstrap-5',
                    placeholder: '-- Ketik NIP atau Nama Dosen --',
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: "{{ route('superadmin.api.dosen') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term || '' };
                        },
                        processResults: function (data) {
                            return { results: data };
                        },
                        cache: true
                    }
                });
            }

            // Jalankan awal
            hancurkanDanMuatSelect2();

            // Jaminan anti-bug di Android Chrome saat transisi modal terbuka
            modalElement.addEventListener('shown.bs.modal', function () {
                hancurkanDanMuatSelect2();
            });

            // Handler untuk tombol Tambah Data
            window.openTambahModal = function() {
                $('#modalPengelolaLabel').text('Tambah Pengelola Jurusan Baru');
                $('#formForm').attr('action', "{{ route('superadmin.pengelolajurusan.store') }}");
                $('#methodField').val('POST');
                $('#formForm')[0].reset();
                
                $('#input_nip').val(null).trigger('change');
                $('#input_jabatan').val('');
                
                modalInstance.show();
            };

            // Handler untuk tombol Edit Data
            window.openEditModal = function(data) {
                $('#modalPengelolaLabel').text('Ubah Data Pengelola Jurusan');
                $('#formForm').attr('action', "{{ url('superadmin/pengelolajurusan') }}/" + data.id);
                $('#methodField').val('PUT');

                let labelDosen = data.nama_dosen ? `${data.nip} - ${data.nama_dosen}` : data.nip;
                if ($('#input_nip').find("option[value='" + data.nip + "']").length === 0) {
                    let newOption = new Option(labelDosen, data.nip, true, true);
                    $('#input_nip').append(newOption).trigger('change');
                } else {
                    $('#input_nip').val(data.nip).trigger('change');
                }

                $('#input_jabatan').val(data.jabatan);
                $('#input_tanggalMulai').val(data.tanggalMulai);
                $('#input_tanggalSelesai').val(data.tanggalSelesai);
                
                modalInstance.show();
            };

            // INTERSEPSI SWEETALERT2 UNTUK AKSI HAPUS DATA
            $('#tablePengelola').on('click', '.btn-hapus', function() {
                let deleteUrl = $(this).data('url');
                
                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data struktural pengelola jurusan ini akan dihapus permanen dari sistem SIMA PRO!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        let form = $('#formDeletePengelola');
                        form.attr('action', deleteUrl);
                        form.submit();
                    }
                });
            });
        });
    })(jQuery);
</script>
@endsection