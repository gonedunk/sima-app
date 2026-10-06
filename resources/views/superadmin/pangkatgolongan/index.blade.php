@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Manajemen Pangkat & Golongan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
        <li class="breadcrumb-item active">Pangkat Golongan</li>
    </ol>

    {{-- Notifikasi Sukses / Gagal --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> <strong>Gagal menyimpan data:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Daftar Pangkat Golongan
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fas fa-plus me-1"></i> Tambah Data
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th>Golongan/Ruang</th>
                            <th>Nama Pangkat</th>
                            <th>Jabatan Akademik</th>
                            <th class="text-center">Kelas Jabatan</th>
                            <th class="text-center">AKM</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pangkatGolongan as $index => $pg)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="fw-bold">{{ $pg->golonganRuang }}</td>
                                <td>{{ $pg->pangkat }}</td>
                                <td>{{ $pg->jabatanAkademik ?? '-' }}</td>
                                <td class="text-center">{{ $pg->kelasJabatan ?? '-' }}</td>
                                <td class="text-center">{{ $pg->akm ?? '-' }}</td>
                                <td class="text-center">
                                    {{-- Tombol Edit --}}
                                    <button class="btn btn-warning btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEdit{{ $pg->id }}" 
                                            title="Ubah">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('pangkat.destroy', $pg->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pangkat/golongan {{ $pg->golonganRuang }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- MODAL EDIT DATA --}}
                            <div class="modal fade" id="modalEdit{{ $pg->id }}" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('pangkat.update', $pg->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title" id="modalEditLabel"><i class="fas fa-edit me-1"></i> Ubah Pangkat Golongan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label for="golonganRuang{{ $pg->id }}" class="form-label fw-semibold">Golongan Ruang <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="golonganRuang{{ $pg->id }}" name="golonganRuang" value="{{ $pg->golonganRuang }}" placeholder="Contoh: III/c" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="pangkat{{ $pg->id }}" class="form-label fw-semibold">Nama Pangkat <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="pangkat{{ $pg->id }}" name="pangkat" value="{{ $pg->pangkat }}" placeholder="Contoh: Penata" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="jabatanAkademik{{ $pg->id }}" class="form-label fw-semibold">Jabatan Akademik</label>
                                                    <input type="text" class="form-control" id="jabatanAkademik{{ $pg->id }}" name="jabatanAkademik" value="{{ $pg->jabatanAkademik }}" placeholder="Contoh: Lektor">
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="kelasJabatan{{ $pg->id }}" class="form-label fw-semibold">Kelas Jabatan</label>
                                                        <input type="number" class="form-control" id="kelasJabatan{{ $pg->id }}" name="kelasJabatan" value="{{ $pg->kelasJabatan }}" placeholder="Contoh: 9">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="akm{{ $pg->id }}" class="form-label fw-semibold">AKM</label>
                                                        <input type="number" step="0.01" class="form-control" id="akm{{ $pg->id }}" name="akm" value="{{ $pg->akm }}" placeholder="Contoh: 100.00">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data pangkat golongan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH DATA --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('pangkat.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTambahLabel"><i class="fas fa-plus me-1"></i> Tambah Pangkat Golongan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="golonganRuang" class="form-label fw-semibold">Golongan Ruang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="golonganRuang" name="golonganRuang" value="{{ old('golonganRuang') }}" placeholder="Contoh: IV/a" required>
                    </div>
                    <div class="mb-3">
                        <label for="pangkat" class="form-label fw-semibold">Nama Pangkat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pangkat" name="pangkat" value="{{ old('pangkat') }}" placeholder="Contoh: Pembina" required>
                    </div>
                    <div class="mb-3">
                        <label for="jabatanAkademik" class="form-label fw-semibold">Jabatan Akademik</label>
                        <input type="text" class="form-control" id="jabatanAkademik" name="jabatanAkademik" value="{{ old('jabatanAkademik') }}" placeholder="Contoh: Lektor Kepala">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kelasJabatan" class="form-label fw-semibold">Kelas Jabatan</label>
                            <input type="number" class="form-control" id="kelasJabatan" name="kelasJabatan" value="{{ old('kelasJabatan') }}" placeholder="Contoh: 11">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="akm" class="form-label fw-semibold">AKM</label>
                            <input type="number" step="0.01" class="form-control" id="akm" name="akm" value="{{ old('akm') }}" placeholder="Contoh: 150.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection