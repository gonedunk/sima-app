<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMA PRO</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{asset('css/all.min.css')}}">
    
    <style>
        body {
            /* Kombinasi Gradien Ungu dan Biru Modis */
            background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }
        .login-card {
            border: none;
            border-radius: 16px;
            background-color: #ffffff; /* Putih Bersih */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        .brand-title {
            color: #4f46e5; /* Ungu Utama */
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
            transition: all 0.2s ease-in-out;
        }
        /* Efek fokus input: border menjadi biru/ungu */
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .btn-login {
            background: linear-gradient(90deg, #4f46e5 0%, #2563eb 100%);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 11px;
            font-weight: 700;
            transition: opacity 0.2s ease;
        }
        .btn-login:hover {
            opacity: 0.9;
            color: white;
        }
        .alert-custom {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="card login-card p-4 p-md-5">
        
        <div class="text-center mb-4">
            <h2 class="brand-title mb-1">SIMA PRO</h2>
            <p class="text-muted small fw-semibold">Jurusan Akuntansi<br>Politeknik Negeri Sriwijaya</p>
        </div>

        @if($errors->has('loginError'))
            <div class="alert alert-custom small py-2 px-3 mb-3 d-flex align-items-center">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <div>{{ $errors->first('loginError') }}</div>
            </div>
        @endif

        <form action="/login" method="POST">
            @csrf
            
            <div class="mb-3">
                <label for="username" class="form-label small text-uppercase tracking-wider">Username</label>
                <div class="input-group">
                    <input type="text" name="username" id="username" class="form-control text-dark" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="mb-3">
    <label for="password" class="form-label fw-semibold text-dark">Password</label>
    <div class="input-group">
        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password Anda" required>
        
        <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword" style="background: #fff; color: #6c757d; border-color: #ced4da;">
            <i class="fa-solid fa-eye" id="eyeIcon"></i>
        </button>
    </div>
</div>

            <button type="submit" class="btn btn-login w-100 shadow-sm text-uppercase tracking-wide">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk Sistem
            </button>
        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                // Saling tukar tipe attribute antara password dan text
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Ganti ikon mata (Melihat / Terbuka vs Terbelah / Tertutup)
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                } else {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                }
            });
        }
    });
</script>
</body>
</html>