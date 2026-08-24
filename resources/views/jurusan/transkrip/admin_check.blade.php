@extends('layouts.app')

@section('title', 'Verifikasi Transkrip - SIMA PRO')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-dark m-0">Verifikasi Transkrip Mahasiswa</h4>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body">
            <!-- Filter & Search Form -->
            <form method="GET" action="{{ route('transkrip.admin.index') }}" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="prodi" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Program --</option>
                        <option value="3050" {{ request('prodi') == '3050' ? 'selected' : '' }}>D3 Akuntansi</option>
                        <option value="4051" {{ request('prodi') == '4051' ? 'selected' : '' }}>D4 Akuntansi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Status --</option>
                        <option value="Belum Diperiksa" {{ request('status') == 'Belum Diperiksa' ? 'selected' : '' }}>Belum Diperiksa</option>
                        <option value="Valid" {{ request('status') == 'Valid' ? 'selected' : '' }}>Valid</option>
                        <option value="Invalid" {{ request('status') == 'Invalid' ? 'selected' : '' }}>Invalid</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NPM atau Nama..." value="{{ request('search') }}">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i> Cari
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tabel Data -->
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle fs-7">
                    <thead class="table-primary text-nowrap">
                        <tr>
                            <th width="50">No</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program</th>
                            <th>File Transkrip</th>
                            <th>Catatan</th>
                            <th>Status Verifikasi</th>
                            <th width="100" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transkrip as $index => $item)
                        <tr>
                            <td>{{ $transkrip->firstItem() + $index }}</td>
                            <td>{{ $item->npm }}</td>
                            <td class="fw-semibold">{{ $item->nama }}</td>
                            <td>{{ $item->prodi == '3050' ? 'D3 Akuntansi' : 'D4 Akuntansi' }}</td>
                            <td>
                                @if($item->path_file)
                                    <a href="{{ asset('storage/' . $item->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        <i class="fa-solid fa-file-pdf me-1"></i> Lihat File
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted border">Belum Upload</span>
                                @endif
                            </td>
                            <td>{{ $item->catatan ?? '-' }}</td>
                            <td>
                                @if($item->status_verifikasi == 'Valid')
                                    <span class="badge bg-success">Valid</span>
                                @elseif($item->status_verifikasi == 'Invalid')
                                    <span class="badge bg-danger">Invalid</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Diperiksa</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-sm btn-info text-white py-0 px-2 btn-verifikasi"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalVerifikasi"
                                        data-npm="{{ $item->npm }}"
                                        data-nama="{{ $item->nama }}"
                                        data-status="{{ $item->status_verifikasi ?? 'Belum Diperiksa' }}"
                                        data-catatan="{{ $item->catatan }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Data mahasiswa tidak ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Single Pagination -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 pt-2 border-top gap-2">
                <div class="small text-muted">
                    Menampilkan <strong>{{ $transkrip->firstItem() ?? 0 }}</strong> - <strong>{{ $transkrip->lastItem() ?? 0 }}</strong> dari <strong>{{ $transkrip->total() }}</strong> data
                </div>
                <div>
                    {{ $transkrip->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Verifikasi -->
<div class="modal fade" id="modalVerifikasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formVerifikasi" method="POST" action="">
                @csrf
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-circle-check me-2"></i> Verifikasi Transkrip</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted m-0">Mahasiswa</label>
                        <div id="modalNamaMhs" class="fw-bold text-dark"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status Verifikasi</label>
                        <select name="status_verifikasi" id="modalStatus" class="form-select form-select-sm" required>
                            <option value="Belum Diperiksa">Belum Diperiksa</option>
                            <option value="Valid">Valid</option>
                            <option value="Invalid">Invalid</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Catatan / Alasan (Opsional)</label>
                        <textarea name="catatan" id="modalCatatan" class="form-control form-control-sm" rows="3" placeholder="Contoh: Dokumen buram, file bukan transkrip resmi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.btn-verifikasi').on('click', function() {
            let npm = $(this).data('npm');
            let nama = $(this).data('nama');
            let status = $(this).data('status');
            let catatan = $(this).data('catatan');

            $('#modalNamaMhs').text(nama + ' (' + npm + ')');
            $('#modalStatus').val(status);
            $('#modalCatatan').val(catatan);
            
            let actionUrl = "{{ url('/admin/transkrip/verifikasi') }}/" + npm;
            $('#formVerifikasi').attr('action', actionUrl);
        });
    });
</script>
@endsection