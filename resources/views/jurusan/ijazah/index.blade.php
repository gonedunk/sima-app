@extends('layouts.app')

@section('title', 'Kelola Scan Ijazah - SIMA PRO')

@section('content')
<!-- Import Library Langsung di Dalam View (Mencegah Gagal Load dari Layout Utama) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    /* Mengunci tinggi dropdown Select2 khusus tampilan mobile agar ada scrollbar */
    .select2-container--bootstrap-5 .select2-dropdown .select2-results__options {
        max-height: 200px !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
</style>

<div class="container-fluid py-2">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-file-contract text-primary me-2"></i> Kelola Scan Ijazah Mahasiswa
        </h4>
    </div>

    {{-- Notifikasi / Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- Form Upload Ijazah Baru --}}
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white font-weight-bold py-3">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Scan Ijazah Baru
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('jurusan.ijazah.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Pilih Mahasiswa Semester Akhir</label>
                            <!-- ID 'select2-mhs' digunakan untuk inisialisasi JS langsung -->
                            <select name="npm" id="select2-mhs" class="form-select" required>
                                <option value="">-- Cari Nama / NPM Mahasiswa --</option>
                                @foreach($mahasiswaList as $m)
                                    <option value="{{ $m->npm }}">{{ $m->npm }} - {{ $m->nama }} ({{ $m->prodi }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">Berkas Scan Ijazah (PDF/Gambar)</label>
                            <input type="file" name="file_ijazah" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text text-muted small">Format: PDF, JPG, JPEG, PNG (Maksimal 2MB)</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan File Ijazah
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Tabel Riwayat Upload Ijazah --}}
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    <i class="fa-solid fa-list me-1"></i> Daftar Ijazah Mahasiswa Tersimpan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">NPM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Berkas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ijazahList as $ijazah)
                                    <tr>
                                        <td class="ps-3 font-monospace fw-semibold">{{ $ijazah->npm }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $ijazah->nama }}</div>
                                            <small class="text-muted">{{ $ijazah->prodi }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">
                                                <i class="fa-solid fa-paperclip me-1"></i>{{ $ijazah->nama_file_asli }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ asset('storage/' . $ijazah->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat Berkas">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>

                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $ijazah->id }}" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>

                                                <form action="{{ route('jurusan.ijazah.destroy', $ijazah->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas ijazah ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Berkas">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Modal Edit Ijazah --}}
                                            <div class="modal fade" id="editModal{{ $ijazah->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h5 class="modal-title fw-bold">
                                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Data Ijazah
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('jurusan.ijazah.update', $ijazah->id) }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold text-secondary">Pilih Mahasiswa</label>
                                                                    <select name="npm" class="form-select select2-modal" required>
                                                                        @foreach($mahasiswaList as $m)
                                                                            <option value="{{ $m->npm }}" {{ $ijazah->npm == $m->npm ? 'selected' : '' }}>
                                                                                {{ $m->npm }} - {{ $m->nama }} ({{ $m->prodi }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold text-secondary">Ganti Berkas Ijazah (Opsional)</label>
                                                                    <input type="file" name="file_ijazah" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                                    <div class="form-text text-muted small">Kosongkan jika tidak ingin mengganti file yang ada.</div>
                                                                </div>

                                                                <div class="mb-2">
                                                                    <small class="text-muted d-block mb-1">File saat ini:</small>
                                                                    <span class="badge bg-light text-secondary border">
                                                                        <i class="fa-solid fa-paperclip me-1"></i>{{ $ijazah->nama_file_asli }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                                                                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-folder-open d-block fs-3 mb-2"></i> Belum ada data scan ijazah tersimpan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load JS secara berurutan langsung di body -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof jQuery !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            // Force Inisialisasi Select2 Utama
            $('#select2-mhs').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Cari Nama / NPM Mahasiswa --'
            });

            // Inisialisasi Select2 Modal
            $('.modal').on('shown.bs.modal', function () {
                $(this).find('.select2-modal').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $(this)
                });
            });
        } else {
            console.error("Select2 atau jQuery gagal dimuat.");
        }
    });
</script>
@endsection