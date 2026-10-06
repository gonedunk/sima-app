@extends('layouts.app')

@section('title', 'Data Alumni - SIMA PRO')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Data Alumni & Tracer Study</h4>
            <p class="text-muted small mb-0">Kelola arsip lulusan dan berkas Google Drive alumni.</p>
        </div>
        <button class="btn btn-primary btn-sm rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus me-1"></i> Tambah Alumni
        </button>
    </div>

    <!-- Filter & Pencarian -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.alumni.index') }}" method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NPM, Nama, atau Program..." value="{{ $search }}">
                </div>
                <div class="col-md-5">
                    <select name="tahun_lulus" class="form-select form-control-sm">
                        <option value="">-- Semua Tahun Lulus --</option>
                        @foreach($tahunOptions as $tahun)
                            <option value="{{ $tahun }}" {{ $selectedTahun == $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-magnifying-glass me-1"></i> Cari</button>
                    @if($search || $selectedTahun)
                        <a href="{{ route('admin.alumni.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3" style="width: 5%;">No</th>
                            <th>NPM</th>
                            <th>Nama Lengkap</th>
                            <th>Program</th>
                            <th class="text-center">Tahun Lulus</th>
                            <th class="text-center">Berkas Drive</th>
                            <th class="text-center pe-3" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alumniList as $index => $item)
                        <tr>
                            <td class="ps-3">{{ $alumniList->firstItem() + $index }}</td>
                            <td class="fw-bold text-secondary">{{ $item->npm }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->program_studi ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-info text-dark">{{ $item->tahun_lulus ?? '-' }}</span></td>
                            <td class="text-center">
                                {{-- Menambahkan parameter tahun_lulus agar sistem bisa mencari berkas di subfolder tahun akademik --}}
                                @if($item->ijazah_gdrive_id)
                                    <a href="{{ route('admin.alumni.download', ['npm' => $item->npm, 'jenis' => 'ijazah', 'tahun_lulus' => $item->tahun_lulus]) }}" target="_blank" class="btn btn-sm btn-outline-success me-1" title="Lihat Ijazah">
                                        <i class="fa-solid fa-file-pdf"></i> Ijazah
                                    </a>
                                @endif
                                @if($item->transkrip_gdrive_id)
                                    <a href="{{ route('admin.alumni.download', ['npm' => $item->npm, 'jenis' => 'transkrip', 'tahun_lulus' => $item->tahun_lulus]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat Transkrip">
                                        <i class="fa-solid fa-file-lines"></i> Transkrip
                                    </a>
                                @endif
                                @if(!$item->ijazah_gdrive_id && !$item->transkrip_gdrive_id)
                                    <span class="text-muted small">Belum ada file</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $item->id }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.alumni.destroy', $item->id) }}" method="POST" class="d-inline form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger btn-delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEdit{{ $item->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.alumni.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Data Alumni</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">NPM <span class="text-danger">*</span></label>
                                                <input type="text" name="npm" class="form-control" value="{{ $item->npm }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" name="nama" class="form-control" value="{{ $item->nama }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Program</label>
                                                <input type="text" name="program_studi" class="form-control" value="{{ $item->program_studi }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Tahun Lulus / Tahun Akademik</label>
                                                <input type="text" name="tahun_lulus" class="form-control" value="{{ $item->tahun_lulus }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">File GDrive Ijazah</label>
                                                <input type="text" name="ijazah_gdrive_id" class="form-control" value="{{ $item->ijazah_gdrive_id }}" placeholder="Nama file di folder Drive">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">File GDrive Transkrip</label>
                                                <input type="text" name="transkrip_gdrive_id" class="form-control" value="{{ $item->transkrip_gdrive_id }}" placeholder="Nama file di folder Drive">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Data alumni belum tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($alumniList->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $alumniList->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.alumni.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Data Alumni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NPM <span class="text-danger">*</span></label>
                        <input type="text" name="npm" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Program</label>
                        <input type="text" name="program_studi" class="form-control" placeholder="Contoh: D3 Akuntansi / 3050">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tahun Lulus / Tahun Akademik</label>
                        <input type="text" name="tahun_lulus" class="form-control" placeholder="Contoh: 2024 atau 20242">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">File GDrive Ijazah</label>
                        <input type="text" name="ijazah_gdrive_id" class="form-control" placeholder="Nama file di folder Drive">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">File GDrive Transkrip</label>
                        <input type="text" name="transkrip_gdrive_id" class="form-control" placeholder="Nama file di folder Drive">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            var form = $(this).closest('.form-delete');
            
            Swal.fire({
                title: 'Hapus Data Alumni?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
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
    });
</script>
@endsection