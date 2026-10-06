<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Berkas Alumni - SIMA PRO</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
    
    <style>
        body {
            background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .portal-container {
            width: 100%;
            max-width: 480px;
            padding: 20px 15px;
        }
        .portal-card {
            border: none;
            border-radius: 16px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .brand-title {
            color: #4f46e5;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .form-label {
            color: #4b5563;
            font-weight: 600;
        }
        .form-control {
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            padding: 10px 14px;
        }
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .btn-cari {
            background: linear-gradient(90deg, #4f46e5 0%, #2563eb 100%);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 11px;
            font-weight: 700;
        }
        .btn-cari:hover {
            opacity: 0.9;
            color: white;
        }
        .alert-custom {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            border-radius: 6px;
        }
        .alumni-info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .btn-download-ijazah {
            background-color: #dc2626;
            color: #ffffff;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-download-ijazah:hover {
            background-color: #b91c1c;
            color: #ffffff;
        }
        .btn-download-transkrip {
            background-color: #2563eb;
            color: #ffffff;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-download-transkrip:hover {
            background-color: #1d4ed8;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="portal-container">
    <div class="card portal-card p-4 p-md-5">
        
        <div class="text-center mb-4">
            <h2 class="brand-title mb-1">PORTAL ALUMNI</h2>
            <p class="text-muted small fw-semibold">Jurusan Akuntansi<br>Politeknik Negeri Sriwijaya</p>
        </div>

        @if(session('error'))
            <div class="alert alert-custom small py-2 px-3 mb-3 d-flex align-items-center">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success small py-2 px-3 mb-3 d-flex align-items-center">
                <i class="fa-solid fa-circle-check me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

<form action="{{ route('portal.alumni.cari') }}" method="GET">
    <div class="mb-3">
        <label for="npm" class="form-label small text-uppercase">Nomor Pokok Mahasiswa (NPM)</label>
        <input type="text" name="npm" id="npm" class="form-control" value="{{ old('npm', isset($alumni) ? $alumni->npm : '') }}" placeholder="Masukkan NPM Anda" required autofocus>
    </div>
    
    <button type="submit" class="btn btn-cari w-100 shadow-sm text-uppercase mt-2">
        <i class="fa-solid fa-magnifying-glass me-2"></i>Cari Berkas
    </button>
</form>


        @if(isset($alumni))
            <div class="alumni-info-box mt-4 p-3">
                <div class="text-center mb-3">
                    <span class="badge bg-success mb-1">Data Ditemukan</span>
                    <h6 class="fw-bold mb-0 text-dark">{{ $alumni->nama ?? $alumni->nama_lengkap }}</h6>
                    <small class="text-muted">NPM: {{ $alumni->npm }}</small>
                </div>

                <div class="d-grid gap-2">
                    {{-- Penyesuaian Kolom: ijazah_gdrive_id --}}
                    @if(!empty($alumni->ijazah_gdrive_id))
                        <a href="{{ route('portal.alumni.download', ['npm' => $alumni->npm, 'jenis' => 'ijazah']) }}" target="_blank" class="btn btn-download-ijazah btn-sm py-2">
                            <i class="fa-solid fa-file-pdf me-2"></i>Download Ijazah
                        </a>
                    @else
                        <button class="btn btn-secondary btn-sm py-2" disabled>
                            <i class="fa-solid fa-file-pdf me-2"></i>Ijazah Belum Tersedia
                        </button>
                    @endif

                    {{-- Penyesuaian Kolom: transkrip_gdrive_id --}}
                    @if(!empty($alumni->transkrip_gdrive_id))
                        <a href="{{ route('portal.alumni.download', ['npm' => $alumni->npm, 'jenis' => 'transkrip']) }}" target="_blank" class="btn btn-download-transkrip btn-sm py-2">
                            <i class="fa-solid fa-file-lines me-2"></i>Download Transkrip Nilai
                        </a>
                    @else
                        <button class="btn btn-secondary btn-sm py-2" disabled>
                            <i class="fa-solid fa-file-lines me-2"></i>Transkrip Belum Tersedia
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <div class="text-center mt-4 pt-2 border-top">
            <a href="{{ route('login') }}" class="text-decoration-none small fw-semibold text-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Halaman Login
            </a>
        </div>

    </div>
</div>

</body>
</html>