@extends('layouts.app')

@section('content')
<div class="container-fluid p-4" style="background-color: #f8fafc; min-height: 100vh;">
    
    <div class="mb-4">
        <h3 class="fw-bold" style="color: #4f46e5;">Pengaturan Sistem Global</h3>
        <p class="text-muted small">Tentukan tahun akademik dan semester yang berlaku aktif untuk seluruh modul SIMA PRO.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-custom-success small py-2 px-3 mb-4 d-flex align-items-center shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-md-7">
            <div class="card shadow-sm border-0 rounded-3 bg-white">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-sliders text-primary me-2"></i>Pilih Tahun Akademik Aktif
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('setting.update') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider mb-2">Daftar Tahun & Semester Akademik</label>
                            <select name="ta_dipilih" class="form-select form-select-lg text-dark fw-semibold focus-purple" required>
                                <option value="">-- Pilih Tahun Akademik --</option>
                                @foreach($daftarTA as $ta)
                                    @php
                                        // Membuat string penanda value untuk dicocokkan dengan data aktif saat ini
                                        $valueOption = $ta->tahunAkademik . '|' . $ta->semesterAkademik;
                                        $isCurrentActive = ($settingAktif && $settingAktif->ta_aktif == $ta->tahunAkademik && $settingAktif->semester_aktif == $ta->semesterAkademik);
                                    @endphp
                                    <option value="{{ $valueOption }}" {{ $isCurrentActive ? 'selected' : '' }}>
                                        Tahun Akademik {{ $ta->tahunAkademik }} — Semester {{ $ta->semesterAkademik }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted mt-2 small">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i> Perubahan ini akan langsung berdampak pada pengelompokan jadwal kuliah, beban ajar dosen, dan laporan mPDF.
                            </div>
                        </div>

                        <div class="border-top pt-3 text-end">
                            <button type="submit" class="btn btn-tambah-custom px-4 py-2 fw-bold text-white shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Terapkan Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-5">
            <div class="card border-0 shadow-sm rounded-3 p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);">
                <div class="mb-4">
                    <span class="badge bg-white rounded-pill px-3 py-2 fw-bold text-uppercase small" style="color: #4f46e5;">
                        Status Saat Ini
                    </span>
                </div>
                
                @if($settingAktif)
                    <h5 class="mb-1 text-white text-opacity-75 small text-uppercase tracking-wider">Tahun Akademik Aktif:</h5>
                    <h2 class="fw-bold mb-4 font-monospace">{{ $settingAktif->ta_aktif }}</h2>

                    <h5 class="mb-1 text-white text-opacity-75 Red small text-uppercase tracking-wider">Semester Aktif:</h5>
                    <h2 class="fw-bold mb-0 text-capitalize">{{ $settingAktif->semester_aktif }}</h2>
                @else
                    <div class="text-center py-4">
                        <i class="fa-solid fa-triangle-exclamation fa-3x mb-3 text-warning"></i>
                        <p class="mb-0 fw-bold">Belum ada Tahun Akademik global yang diaktifkan dalam sistem.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    /* Desain Tombol Submit Gradien Ungu-Biru */
    .btn-tambah-custom {
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        border: none;
        border-radius: 8px;
        transition: opacity 0.2s ease;
    }
    .btn-tambah-custom:hover {
        opacity: 0.9;
        color: white;
    }

    /* Efek Fokus Dropdown Berpendar Ungu */
    .focus-purple {
        border-radius: 8px;
        border: 1.5px solid #e5e7eb;
    }
    .focus-purple:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    /* Notifikasi alert khusus sukses */
    .alert-custom-success {
        background-color: #f0fdf4;
        border-left: 4px solid #22c55e;
        color: #166534;
        border-radius: 6px;
    }
</style>
@endsection