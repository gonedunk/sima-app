@extends('layouts.app') {{-- Sesuaikan dengan nama induk template layout Anda --}}

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4 class="fw-bold text-dark"><i class="fa-solid fa-user-gear me-2"></i>Pengaturan Profil</h4>
            <p class="text-muted">Kelola informasi data diri, foto profil, dan keamanan akun Anda.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-primary"><i class="fa-solid fa-id-card me-2"></i>Data Diri & Foto</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('put')

                        <div class="row align-items-center mb-4">
                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                @if($user->foto && \Storage::disk('public')->exists($user->foto))
                                    <img src="{{ asset('storage/' . $user->foto) }}" alt="Foto Profil" class="img-thumbnail rounded-circle object-fit-cover" style="width: 120px; height: 120px; border: 2px solid #0d6efd;">
                                @else
                                    <div class="img-thumbnail rounded-circle d-flex align-items-center justify-content-center mx-auto bg-light text-secondary shadow-sm" style="width: 120px; height: 120px; font-size: 2.5rem;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-9">
                                <label for="foto" class="form-label fw-semibold text-secondary">Ubah Foto Profil</label>
                                <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto">
                                <div class="form-text">Format yang didukung: JPG, JPEG, PNG. Maksimal 2MB.</div>
                                @error('foto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Username</label>
                                <input type="text" class="form-control bg-light" value="{{ $user->username }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Email Berkemas</label>
                                <input type="text" class="form-control bg-light" value="{{ $user->email }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label fw-semibold text-secondary">Nama Lengkap</label>
                            <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}">
                            @error('nama_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold text-secondary">Level Akses</label>
                                <input type="text" class="form-control bg-light" value="{{ strtoupper($user->level) }}" readonly>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold text-secondary">Kode Prodi</label>
                                <input type="text" class="form-control bg-light" value="{{ $user->kode_prodi ?? '-' }}" readonly>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary px-4 rounded-3">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-danger"><i class="fa-solid fa-lock me-2"></i>Ubah Password</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('put')

                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold text-secondary">Password Sekarang</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-secondary">Password Baru</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-semibold text-secondary">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>

                        <button type="submit" class="btn btn-danger w-100 rounded-3">
                            <i class="fa-solid fa-key me-2"></i>Perbarui Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection