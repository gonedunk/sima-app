@extends('layouts.app')

@section('title', 'Manajemen Induk Organisasi - SIMA PRO')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Manajemen Induk Organisasi</h4>
            <p class="text-muted mb-0 small">Kelola data induk organisasi utama beserta pengelompokan kategorinya.</p>
        </div>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus"></i> Tambah Induk
        </button>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form action="{{ route('superadmin.perusahaan.index') }}" method="GET" id="searchForm">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   id="searchInput"
                                   class="form-control border-start-0 ps-0" 
                                   placeholder="Cari nama atau kategori..." 
                                   value="{{ $search ?? '' }}">
                            @if(!empty($search))
                                <a href="{{ route('superadmin.perusahaan.index') }}" class="btn btn-outline-secondary border-start-0" title="Bersihkan Pencarian">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-secondary px-3">Cari</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="tableIndukOrganisasi">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" width="60">No</th>
                            <th class="py-3">Nama Induk Organisasi</th>
                            <th class="py-3">Kategori</th>
                            <th class="text-center py-3" width="130">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($induk_organisasi as $key => $item)
                            <tr>
                                <td class="text-center fw-semibold text-secondary">
                                    {{ $induk_organisasi->firstItem() + $key }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->nama_induk }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border px-3 py-2 rounded-pill small fw-semibold">
                                        <i class="fa-solid fa-tags me-1"></i> {{ $item->kategori }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- Tombol Edit -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning btn-edit border-0" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEdit"
                                                data-id_induk="{{ $item->id_induk }}"
                                                data-nama_induk="{{ $item->nama_induk }}"
                                                data-kategori="{{ $item->kategori }}"
                                                title="Ubah Data">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <form action="{{ route('superadmin.perusahaan.destroy', $item->id_induk) }}" method="POST" class="d-inline form-delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete border-0" title="Hapus Data">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-folder-open fs-1 d-block mb-3 text-secondary"></i>
                                    Tidak ditemukan data induk organisasi yang cocok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination Footer -->
        @if ($induk_organisasi->hasPages())
            <div class="card-footer bg-white border-0 py-3 d-flex flex-column flex-md-row align-items-center justify-content-between">
                <div class="text-muted small mb-2 mb-md-0">
                    Menampilkan {{ $induk_organisasi->firstItem() }} sampai {{ $induk_organisasi->lastItem() }} dari {{ $induk_organisasi->total() }} data
                </div>
                <div>
                    {{-- Render Pagination Link ber-style Bootstrap 5 secara otomatis --}}
                    {!! $induk_organisasi->links('pagination::bootstrap-5') !!}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- MODAL TAMBAH DATA (CREATE) --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalTambahLabel"><i class="fa-solid fa-sitemap me-2"></i> Tambah Induk Organisasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('superadmin.perusahaan.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nama_induk" class="form-label fw-semibold">Nama Induk Organisasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_induk" name="nama_induk" placeholder="Contoh: Ikatan Akuntan Indonesia" required>
                        </div>
                        <div class="col-12">
                            <label for="kategori" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kategori" name="kategori" placeholder="Contoh: Profesi / Akademik / Olahraga" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT DATA (UPDATE) --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="modalEditLabel"><i class="fa-solid fa-pen-to-square me-2"></i> Ubah Induk Organisasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_nama_induk" class="form-label fw-semibold">Nama Induk Organisasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nama_induk" name="nama_induk" required>
                        </div>
                        <div class="col-12">
                            <label for="edit_kategori" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_kategori" name="kategori" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // 1. Data Binding untuk Edit Modal
        $('.btn-edit').on('click', function() {
            const id_induk = $(this).data('id_induk');
            const nama_induk = $(this).data('nama_induk');
            const kategori = $(this).data('kategori');

            $('#edit_nama_induk').val(nama_induk);
            $('#edit_kategori').val(kategori);

            // Ganti action URL dinamis
            $('#formEdit').attr('action', `/superadmin/perusahaan/${id_induk}`);
        });

        // 2. Konfirmasi Hapus SweetAlert2
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            const form = $(this).closest('.form-delete');

            Swal.fire({
                title: 'Hapus data induk organisasi?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Ya, Hapus',
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