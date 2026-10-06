@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">Manajemen Pimpinan Polsri</h3>
            <p class="text-muted small mb-0">Kelola daftar pimpinan dan masa jabatan di lingkungan Politeknik Negeri Sriwijaya.</p>
        </div>
        <!-- Trigger Modal Tambah -->
        <button type="button" class="btn btn-sm btn-primary fw-semibold shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahPimpinan">
            <i class="fa-solid fa-user-plus me-2"></i>Tambah Pimpinan
        </button>
    </div>

    <!-- Fallback Alert untuk browser tanpa JS -->
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
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">NIP</th>
                            <th width="25%">Nama Lengkap</th>
                            <th width="20%">Jabatan</th>
                            <th width="15%">Masa Jabatan</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pimpinan as $index => $p)
                        <tr>
                            <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $p->nip }}</td>
                            <td>{{ $p->nama }}</td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary px-2 py-1 rounded-1">
                                    {{ $p->jabatan }}
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <strong>Mulai:</strong> {{ \Carbon\Carbon::parse($p->tanggalMulai)->translatedFormat('d M Y') }}
                                </div>
                                <div class="small text-muted">
                                    <strong>Selesai:</strong> {!! $p->tanggalSelesai ? \Carbon\Carbon::parse($p->tanggalSelesai)->translatedFormat('d M Y') : '<span class="badge bg-success-subtle text-success py-0 px-1" style="font-size: 10px;">Aktif</span>' !!}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Trigger Modal Edit -->
                                    <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalEditPimpinan{{ $p->id }}">
                                        <i class="fa-solid fa-user-pen"></i> Edit
                                    </button>
                                    <button type="button" onclick="konfirmasiHapus('{{ $p->id }}', '{{ $p->nama }}')" class="btn btn-sm btn-outline-danger py-1 px-2">
                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-users-slash d-block fs-2 mb-2"></i> Belum ada data pimpinan Polsri.
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
<!-- MODAL TAMBAH PIMPINAN (STATIC BACKDROP)    -->
<!-- ========================================== -->
<div class="modal fade" id="modalTambahPimpinan" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <form id="formTambahPimpinan" action="{{ route('pimpinan.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary" id="modalTambahLabel">
                        <i class="fa-solid fa-user-plus me-2"></i>Tambah Pimpinan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">NIP <span class="text-danger">*</span></label>
                        <input type="text" name="nip" class="form-control form-control-sm" placeholder="Contoh: 198203112008121002" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Contoh: Dr. Ir. H. Ahmad, M.T." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="jabatan" class="form-control form-control-sm" placeholder="Contoh: Direktur Polsri / Wadir I / Kajur Akuntansi" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggalMulai" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Selesai</label>
                            <input type="date" name="tanggalSelesai" class="form-control form-control-sm">
                            <div class="form-text text-muted" style="font-size: 10px;">Kosongkan jika masih menjabat.</div>
                        </div>
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
<!-- LOOP MODAL EDIT PIMPINAN (STATIC BACKDROP) -->
<!-- ========================================== -->
@foreach($pimpinan as $p)
<div class="modal fade" id="modalEditPimpinan{{ $p->id }}" tabindex="-1" aria-labelledby="modalEditLabel{{ $p->id }}" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <form id="formEditPimpinan{{ $p->id }}" action="{{ route('pimpinan.update', $p->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-warning" id="modalEditLabel{{ $p->id }}">
                        <i class="fa-solid fa-user-pen me-2"></i>Ubah Data Pimpinan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">NIP <span class="text-danger">*</span></label>
                        <input type="text" name="nip" class="form-control form-control-sm" value="{{ $p->nip }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" value="{{ $p->nama }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="jabatan" class="form-control form-control-sm" value="{{ $p->jabatan }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggalMulai" class="form-control form-control-sm" value="{{ $p->tanggalMulai }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Selesai</label>
                            <input type="date" name="tanggalSelesai" class="form-control form-control-sm" value="{{ $p->tanggalSelesai }}">
                            <div class="form-text text-muted" style="font-size: 10px;">Kosongkan jika masih menjabat.</div>
                        </div>
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

<!-- Form Hidden untuk Hapus -->
<form id="formHapusPimpinan" action="" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. Alert Sukses dari SweetAlert2 Lokal
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

    // 2. Alert Gagal dari SweetAlert2 Lokal
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

    // Loading saat submit form tambah data
    $('#formTambahPimpinan').on('submit', function() {
        if (this.checkValidity() && typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menyimpan data...',
                text: 'Harap tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }
    });

    // Loading saat submit form edit data (loop)
    @foreach($pimpinan as $p)
        $('#formEditPimpinan{{ $p->id }}').on('submit', function() {
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
function konfirmasiHapus(id, nama) {
    const $form = $('#formHapusPimpinan');
    $form.attr('action', "{{ url('admin/pimpinan') }}/" + id);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Data Pimpinan?',
            text: `Apakah Anda yakin ingin menghapus data "${nama}"? Tindakan ini tidak bisa dibatalkan!`,
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
        if (confirm(`Apakah Anda yakin ingin menghapus data "${nama}"?`)) {
            $form.trigger('submit');
        }
    }
}
</script>
@endsection