@extends('layouts.app')

@section('content')
<div class="container-fluid p-4" style="background-color: #f8fafc; min-height: 100vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold" style="color: #2563eb;">Panel Kontrol Admin</h3>
            <p class="text-muted small mb-0">Statistik akademik real-time Jurusan Akuntansi Politeknik Negeri Sriwijaya.</p>
        </div>
        <div>
            <span class="badge shadow-sm px-3 py-2 fw-semibold text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border-radius: 8px;">
                <i class="fa-solid fa-calendar-day me-2"></i>TA Aktif: {{ $settingAktif->ta_aktif ?? 'Belum Diatur' }} ({{ $settingAktif->semester_aktif ?? '-' }})
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white transition-hover">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); width: 56px; height: 56px;">
                        <i class="fa-solid fa-user-check fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Mahasiswa Aktif</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $mhsAktif ?? 0 }} <span class="fs-6 fw-normal text-muted">Orang</span></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white transition-hover">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); width: 56px; height: 56px;">
                        <i class="fa-solid fa-user-slash fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Mhs Non-Aktif</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $mhsNonAktif ?? 0 }} <span class="fs-6 fw-normal text-muted">Orang</span></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white transition-hover">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); width: 56px; height: 56px;">
                        <i class="fa-solid fa-graduation-cap fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Total Program</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalProdi ?? 0 }} <span class="fs-6 fw-normal text-muted">Prodi</span></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white transition-hover">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); width: 56px; height: 56px;">
                        <i class="fa-solid fa-users fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Sistem User</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalUser ?? 0 }} <span class="fs-6 fw-normal text-muted">User</span></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-chart-simple text-primary me-2"></i>Sebaran Mahasiswa Aktif per Program
            </h5>
            <span class="badge bg-light text-primary border px-2 py-1 small rounded font-monospace" style="font-size: 11px;">
                TA: {{ $settingAktif->ta_aktif ?? '-' }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: #f1f5f9; color: #475569;">
                        <tr>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase">Kode Program</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Nama Program (Prodi)</th>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase text-end">Jumlah Mahasiswa Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="text-dark">
                        @forelse($sebaranMhsProdi as $sebaran)
<tr>
    <!-- Menampilkan Kode Program -->
    <td class="px-4 py-3 font-monospace fw-bold text-primary">{{ $sebaran->kodeProdi }}</td>
    <!-- Menampilkan Nama Program -->
    <td class="fw-semibold text-dark">{{ $sebaran->namaProdi }}</td>
    <!-- Menampilkan Total Hitungan Mahasiswa Aktif -->
    <td class="px-4 text-end fw-bold fs-5 text-success">
        {{ $sebaran->total_aktif }} <span class="fs-6 fw-normal text-muted">Mhs</span>
    </td>
</tr>
@empty
<tr>
    <td colspan="3" class="text-center py-5 text-muted">
        <i class="fa-solid fa-database fa-2x mb-3 text-opacity-25 text-secondary"></i>
        <p class="mb-0 small fw-semibold">Tidak ada data sebaran mahasiswa untuk tahun akademik ini.</p>
    </td>
</tr>
@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
    .transition-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06) !important;
    }
</style>
@endsection