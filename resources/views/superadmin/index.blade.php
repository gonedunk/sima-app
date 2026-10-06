@extends('layouts.app')

@section('content')
<div class="container-fluid p-4" style="background-color: #f8fafc; min-height: 100vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold" style="color: #4f46e5;">Dashboard Analitik</h3>
            <p class="text-muted small mb-0">Selamat datang kembali, <span class="fw-semibold text-dark">{{ Auth::user()->nama_lengkap }}</span></p>
        </div>
        <div>
            <span class="badge shadow-sm px-3 py-2 fw-semibold text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%); border-radius: 8px;">
                <i class="fa-solid fa-calendar-day me-2"></i>Tahun Akademik: {{ $settingAktif->ta_aktif ?? 'Belum Diatur' }} ({{ $settingAktif->semester_aktif ?? '-' }})
            </span>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white transition-hover">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); width: 56px; height: 56px;">
                        <i class="fa-solid fa-users fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Total Pengguna</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalUser ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white transition-hover">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); width: 56px; height: 56px;">
                        <i class="fa-solid fa-graduation-cap fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Total Program</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalProdi ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white transition-hover">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); width: 56px; height: 56px;">
                        <i class="fa-solid fa-clock-rotate-left fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Log Masuk Terbaru</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ count($logLogins ?? []) }} <span class="fs-6 fw-normal text-muted">Aktivitas</span></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-shield-halved text-primary me-2"></i>Aktivitas Log Masuk Pengguna
            </h5>
            <span class="badge bg-light text-muted border px-2 py-1 small rounded">Real-time</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: #f1f5f9; color: #475569;">
                        <tr>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase">Nama Pengguna</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Level</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Waktu Masuk</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Alamat IP</th>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase">Perangkat / Browser</th>
                        </tr>
                    </thead>
                    <tbody class="text-dark">
                        @forelse($logLogins ?? [] as $log)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark">{{ $log->nama_lengkap }}</div>
                                <div class="text-muted small" style="font-size: 11px;">ID User: {{ $log->user_id }} | <span class="fst-italic">{{ $log->username }}</span></div>
                            </td>
                            <td>
                                <span class="badge text-capitalize px-2 py-1 rounded" style="background-color: #e0e7ff; color: #4338ca; font-size: 11px; font-weight: 600;">
                                    {{ $log->level }}
                                </span>
                            </td>
                            <td class="text-secondary small fw-medium">
                                {{ \Carbon\Carbon::parse($log->waktu_login)->translatedFormat('d M Y, H:i') }} WIB
                            </td>
                            <td>
                                <code class="px-2 py-1 rounded bg-light text-primary small border" style="font-size: 11px;">{{ $log->ip_address }}</code>
                            </td>
                            <td class="px-4 text-muted small" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $log->user_agent }}">
                                {{ $log->user_agent }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fa-2x mb-3 text-opacity-50 text-secondary"></i>
                                <p class="mb-0 small fw-semibold">Belum ada riwayat aktivitas login terdeteksi.</p>
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
    /* Animasi kecil untuk mempercantik hover pada card */
    .transition-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06) !important;
    }
</style>
@endsection