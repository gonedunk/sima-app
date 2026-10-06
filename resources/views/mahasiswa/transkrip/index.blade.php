@extends('layouts.app') {{-- Sesuaikan dengan master layout Anda --}}

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-file-signature text-primary me-2"></i>Upload & Verifikasi Transkrip Nilai
                    </h4>
                </div>
                <div class="card-body">
                    
                    {{-- Alert Success / Error --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @php
                        // Cek status verifikasi transkrip
                        $statusRaw = isset($transkrip) && $transkrip ? strtolower(trim($transkrip->status_verifikasi ?? '')) : '';
                        $isValidated = in_array($statusRaw, ['valid', 'setuju', 'disetujui', 'terverifikasi', 'acc']);
                    @endphp

                    <div class="row">
                        {{-- Form Upload Transkrip --}}
                        <div class="col-md-6 mb-4 mb-md-0">
                            <h5 class="fw-bold text-secondary mb-3">Unggah Berkas PDF</h5>

                            @if($isValidated)
                                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                                    <i class="fas fa-check-circle me-2 fs-5"></i>
                                    <div>
                                        <strong>Transkrip Telah Divalidasi!</strong><br>
                                        Berkas Anda sudah disetujui oleh admin. Fitur unggah ulang telah dikunci.
                                    </div>
                                </div>
                            @endif

                            <form action="{{ route('mahasiswa.transkrip.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                {{-- BAGIAN INPUT FILE TERGABUNG LENGKAP --}}
                                <div class="mb-3">
                                    <label for="file_transkrip" class="form-label fw-bold">Unggah Transkrip (PDF, Maks 5MB)</label>
                                    <input type="file" 
                                           name="file_transkrip" 
                                           id="file_transkrip" 
                                           class="form-control @error('file_transkrip') is-invalid @enderror" 
                                           accept="application/pdf" 
                                           onchange="validateFileSize(this)"
                                           {{ $isValidated ? 'disabled' : '' }}
                                           required>
                                    <small class="text-muted d-block mt-1">* Format berkas wajib PDF dengan ukuran maksimal 5 MB.</small>
                                    
                                    @error('file_transkrip')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary" {{ $isValidated ? 'disabled' : '' }}>
                                    <i class="fas fa-upload me-1"></i> Unggah Transkrip
                                </button>
                            </form>
                        </div>

                        {{-- Detail Status Transkrip --}}
                        <div class="col-md-6 ps-md-4 border-start-md">
                            <h5 class="fw-bold text-secondary mb-3">Status Verifikasi</h5>
                            
                            @if(isset($transkrip) && $transkrip && $transkrip->path_file)
                                <div class="p-3 bg-light rounded border">
                                    <div class="mb-2">
                                        <span class="text-muted">Status Saat Ini: </span>
                                        @if($isValidated)
                                            <span class="badge bg-success">Disetujui / Valid</span>
                                        @elseif(in_array($statusRaw, ['tolak', 'ditolak', 'invalid', 'revisi']))
                                            <span class="badge bg-danger">Ditolak / Invalid</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Belum Diperiksa</span>
                                        @endif
                                    </div>

                                    @if(!empty($transkrip->nama_file_asli))
                                        <div class="mb-2">
                                            <small class="text-muted d-block">Nama File:</small>
                                            <span class="fw-semibold text-dark fs-7">{{ $transkrip->nama_file_asli }}</span>
                                        </div>
                                    @endif

                                    @if(!empty($transkrip->catatan))
                                        <div class="mb-2 p-2 bg-white border border-danger-subtle rounded">
                                            <small class="text-danger fw-bold d-block"><i class="fas fa-info-circle me-1"></i>Catatan Admin:</small>
                                            <span class="text-danger fs-7">{{ $transkrip->catatan }}</span>
                                        </div>
                                    @endif

                                    <div class="mt-3">
                                        <a href="{{ route('mahasiswa.transkrip.file') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-file-pdf me-1"></i> Lihat Berkas Terunggah
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-1"></i> Anda belum mengunggah berkas transkrip nilai. Silakan gunakan form di samping untuk mengunggah.
                                </div>
                            @endif

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- SCRIPT JAVASCRIPT TERGABUNG --}}
@push('scripts')
<script>
function validateFileSize(input) {
    const maxKb = 5120; // 5 MB
    if (input.files && input.files[0]) {
        const fileSizeKb = input.files[0].size / 1024;
        if (fileSizeKb > maxKb) {
            alert("Ukuran file melebihi batas maksimal! Ukuran berkas yang Anda pilih adalah " + (fileSizeKb / 1024).toFixed(2) + " MB. Maksimal ukuran file yang diizinkan adalah 5 MB.");
            input.value = ''; // Reset input file
        }
    }
}
</script>
@endpush