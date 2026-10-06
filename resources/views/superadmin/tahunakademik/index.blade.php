@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">Master Tahun Akademik</h3>
            <p class="text-muted small mb-0">Kelola parameter tahun akademik sistem SIMA PRO.</p>
        </div>
        <!-- Trigger Modal Tambah -->
        <button type="button" class="btn btn-sm btn-primary fw-semibold shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahTa">
            <i class="fa-solid fa-plus me-2"></i>Tambah TA Baru
        </button>
    </div>

    <!-- Alert Handler Cadangan (Fallback jika JS bermasalah) -->
    <noscript>
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            </div>
        @endif
    </noscript>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Gagal memproses data:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tabel Data -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light text-secondary fw-semibold">
                        <tr>
                            <th width="8%" class="text-center">No</th>
                            <th width="25%">Kode TA (5 Digit)</th>
                            <th width="40%">Keterangan Semester</th>
                            <th width="27%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tahunAkademiks as $index => $ta)
                        <tr>
                            <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $ta->tahunAkademik }}</td>
                            <td>
                                @php
                                    $sem = strtolower($ta->semesterAkademik);
                                    $isGanjil = (strpos($sem, 'ganjil') !== false || strpos($sem, '1') !== false);
                                @endphp
                                <span class="badge {{ $isGanjil ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} px-2 py-1 rounded-1">
                                    {{ $ta->semesterAkademik }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Trigger Modal Edit -->
                                    <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalEditTa{{ $ta->id }}">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <button type="button" onclick="konfirmasiHapus('{{ $ta->id }}', '{{ $ta->tahunAkademik }}')" class="btn btn-sm btn-outline-danger py-1 px-2">
                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-folder-open d-block fs-3 mb-2"></i> Belum ada data tahun akademik.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH TA (STATIC BACKDROP)          -->
<!-- ========================================== -->
<div class="modal fade" id="modalTambahTa" tabindex="-1" aria-labelledby="modalTambahTaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <form id="formTambahTa" action="{{ route('tahun-akademik.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary" id="modalTambahTaLabel">
                        <i class="fa-solid fa-plus me-2"></i>Tambah Tahun Akademik
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kode Tahun Akademik (5 Digit) <span class="text-danger">*</span></label>
                        <input type="number" name="tahunAkademik" class="form-control form-control-sm" placeholder="Contoh: 20251" required>
                        <div class="form-text text-muted" style="font-size: 11px;">Format: [4 Digit Tahun] + [1 Digit Semester (1=Ganjil, 2=Genap)].</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Keterangan Semester <span class="text-danger">*</span></label>
                        <input type="text" name="semesterAkademik" class="form-control form-control-sm" placeholder="Contoh: Semester Ganjil 2025/2026" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- LOOP MODAL EDIT TA (STATIC BACKDROP)       -->
<!-- ========================================== -->
@foreach($tahunAkademiks as $ta)
<div class="modal fade" id="modalEditTa{{ $ta->id }}" tabindex="-1" aria-labelledby="modalEditTaLabel{{ $ta->id }}" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <form id="formEditTa{{ $ta->id }}" action="{{ route('tahun-akademik.update', $ta->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-warning" id="modalEditTaLabel{{ $ta->id }}">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Ubah Tahun Akademik
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kode Tahun Akademik (5 Digit) <span class="text-danger">*</span></label>
                        <input type="number" name="tahunAkademik" class="form-control form-control-sm" value="{{ $ta->tahunAkademik }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Keterangan Semester <span class="text-danger">*</span></label>
                        <input type="text" name="semesterAkademik" class="form-control form-control-sm" value="{{ $ta->semesterAkademik }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning text-white fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Form Hidden untuk Destroy -->
<form id="formHapusTa" action="" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. Tampilkan Notifikasi Sukses via SweetAlert2 Lokal jika Session Bernilai Success
    @if(session('success'))
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{!! session('success') !!}",
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Selesai'
            });
        }
    @endif

    // 2. Tampilkan Notifikasi Gagal/Error via SweetAlert2 Lokal jika Session Bernilai Error
    @if(session('error'))
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Proses Gagal!',
                text: "{!! session('error') !!}",
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Tutup'
            });
        }
    @endif

    // Handler loading loader saat submit form tambah data
    $('#formTambahTa').on('submit', function() {
        if (this.checkValidity() && typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menyimpan data...',
                text: 'Harap tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }
    });

    // Handler loading loader saat submit form edit data (loop)
    @foreach($tahunAkademiks as $ta)
        $('#formEditTa{{ $ta->id }}').on('submit', function() {
            if (this.checkValidity() && typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Memperbarui data...',
                    text: 'Harap tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }
        });
    @endforeach
});

// 3. Konfirmasi Hapus Data dengan SweetAlert2 Lokal
function konfirmasiHapus(id, kode) {
    const $form = $('#formHapusTa');
    $form.attr('action', "{{ url('admin/tahun-akademik') }}/" + id);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Tahun Akademik?',
            text: `Apakah Anda yakin ingin menghapus kode TA "${kode}"? Tindakan ini tidak bisa dibatalkan!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Hapus!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                $form.trigger('submit');
            }
        });
    } else {
        if (confirm(`Apakah Anda yakin ingin menghapus Kode TA "${kode}"?`)) {
            $form.trigger('submit');
        }
    }
}
</script>
@endsection