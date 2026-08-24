<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMA PRO')</title>
    
    <!-- CSS Global -->
    <link class="main-css" rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">

    <style>
        body { background-color: #f8fafc; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; align-items: stretch; margin-top: 56px; }
        
        #sidebar {
            min-width: 260px; 
            max-width: 260px;
            background: #ffffff; 
            border-right: 1px solid #dee2e6;
            height: calc(100vh - 56px); 
            position: fixed;
            top: 56px; 
            bottom: 0; 
            left: 0; 
            z-index: 1040;
            transition: all 0.3s ease;
            overflow-y: auto; 
        }
        
        #sidebar.active { min-width: 80px; max-width: 80px; }
        #sidebar.active .menu-text, 
        #sidebar.active .nav-header,
        #sidebar.active .arrow-icon { display: none !important; }
        
        #sidebar.active .sidebar-header-wrapper {
            justify-content: center !important;
            border-bottom: none !important;
            padding-bottom: 0 !important;
            margin-bottom: 1.5rem !important;
        }
        #sidebar.active .sidebar-header-wrapper button {
            width: 100%;
            text-align: center;
        }

        #sidebar.active .list-group-item { 
            text-align: center; 
            padding-left: 0; 
            padding-right: 0; 
        }
        #sidebar.active .list-group-item i { 
            margin: 0 !important; 
            font-size: 1.2rem;
            display: inline-block;
            width: 100%;
        }
        #sidebar.active #btn-logout {
            text-align: center !important;
            padding-left: 0;
            padding-right: 0;
        }

        .submenu {
            background-color: #f8fafc;
            border-left: 3px solid #0d6efd;
            padding-left: 10px;
        }
        .submenu .list-group-item {
            padding: 0.5rem 1rem 0.5rem 1.5rem;
            font-size: 0.9rem;
        }
        .arrow-icon {
            transition: transform 0.2s ease;
        }
        .collapsed[aria-expanded="false"] .arrow-icon {
            transform: rotate(0deg);
        }
        [aria-expanded="true"] .arrow-icon {
            transform: rotate(90deg);
        }

        #content {
            width: 100%; padding: 30px;
            min-height: calc(100vh - 56px); 
            margin-left: 260px; 
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        #content.active { margin-left: 80px; }
        
        @media (max-width: 768px) {
            #sidebar { margin-left: -260px; }
            #sidebar.active { margin-left: 0; }
            #content { margin-left: 0 !important; }
            
            #sidebar.active .menu-text, 
            #sidebar.active .nav-header,
            #sidebar.active .arrow-icon { display: inline-block !important; }
            #sidebar.active .sidebar-header-wrapper { justify-content: space-between !important; }
        }

        .transition-link:hover {
            color: #1d4ed8 !important;
            text-decoration: underline !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.25rem 0.5rem !important;
            min-height: calc(1.5em + 0.5rem + 2px) !important;
            font-size: 0.875rem !important;
        }
    </style>
    @yield('styles')
</head>
<body>

    {{-- TOPBAR UTAMA --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm" style="z-index: 1050;">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold m-0 ms-3 ms-md-4" href="#">
                SIMA PRO
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('profile.edit') }}" class="text-decoration-none d-flex align-items-center bg-white bg-opacity-25 px-3 py-1 rounded-pill small link-light transition-link">
                    @if(Auth::user()->foto && \Storage::disk('public')->exists(Auth::user()->foto))
                        <img src="{{ asset('storage/' . Auth::user()->foto) }}" alt="Avatar" class="rounded-circle me-2 object-fit-cover" style="width: 24px; height: 24px; border: 1px solid #fff;">
                    @else
                        <i class="fa-solid fa-user-shield me-2"></i>
                    @endif
                    <span>{{ Auth::user()->nama_lengkap ?? 'Admin' }}</span>
                </a>

                <button type="button" class="btn btn-outline-light border-0 px-2 py-1 d-md-none sidebar-toggle-btn" style="font-size: 1.25rem; box-shadow: none;">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="wrapper">
        {{-- SIDEBAR NAVIGASI --}}
        <nav id="sidebar" class="p-3">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom sidebar-header-wrapper">
                <span class="fw-bold text-secondary text-uppercase small nav-header m-0">Navigasi Utama</span>
                <button type="button" class="btn btn-sm btn-link text-secondary p-0 border-0 sidebar-toggle-btn" style="font-size: 1.15rem; text-decoration: none; box-shadow: none;">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            
            <div class="list-group list-group-flush">
                <a href="/index" class="list-group-item list-group-item-action border-0 rounded-3 mb-1 {{ Request::is('index*') ? 'active text-white' : 'text-muted' }}">
                    <i class="fa-solid fa-gauge"></i> <span class="menu-text ms-2">Dashboard</span>
                </a>

                @if(Auth::user()->level === 'superadmin' || Auth::user()->level === 'admin')
                    @php 
                        $isAkademikActive = Request::routeIs('mahasiswa*') || 
                                            Request::routeIs('kelas-mahasiswa*') || 
                                            Request::routeIs('absensi*') || 
                                            Request::routeIs('dosen*') || 
                                            Request::routeIs('admin.plottingkelasdosen*') || 
                                            Request::routeIs('admin.ajardosen*') ||
                                            Request::routeIs('admin.data-magang*') || 
                                            Request::routeIs('admin.nilai-magang*') ||
                                            Request::routeIs('jurusan.ijazah*') ||
                                            Request::routeIs('transkrip.admin*');
                    @endphp
                    <a href="#menuAkademik" data-bs-toggle="collapse" aria-expanded="{{ $isAkademikActive ? 'true' : 'false' }}" class="list-group-item list-group-item-action border-0 rounded-3 mb-1 d-flex justify-content-between align-items-center text-muted {{ !$isAkademikActive ? 'collapsed' : '' }}">
                        <div>
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span class="menu-text ms-2">Data Akademik</span>
                        </div>
                        <i class="fa-solid fa-chevron-right small arrow-icon"></i>
                    </a>
                    
                    <div class="collapse submenu rounded-3 mb-1 {{ $isAkademikActive ? 'show' : '' }}" id="menuAkademik">
                        <a href="{{ route('mahasiswa.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('mahasiswa*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-users me-2"></i> Master Mahasiswa
                        </a>
                        <a href="{{ route('kelas-mahasiswa.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('kelas-mahasiswa*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-circle-nodes me-2"></i> Plotting & Status Mhs
                        </a>
                        <a href="{{ route('absensi.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('absensi*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-clipboard-user me-2"></i> Rekap Absensi Mhs
                        </a>
                        <a href="{{ route('dosen.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('dosen*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-users-gear"></i> Data Dosen & Tendik
                        </a>
                        <a href="{{ route('admin.plottingkelasdosen.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('admin.plottingkelasdosen*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-calendar-day me-2"></i> Plotting Kelas Dosen
                        </a>
                        <a href="{{ route('admin.ajardosen.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('admin.ajardosen*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-clock-rotate-left me-2"></i> Input Jam Ajar Dosen
                        </a>
                        <a href="{{ route('admin.data-magang.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('admin.data-magang*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-user-tie me-2"></i> Data Mhs Magang
                        </a>
                        <a href="{{ route('admin.nilai-magang.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('admin.nilai-magang*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-star me-2"></i> Nilai Instansi Magang
                        </a>
                        <a href="{{ route('jurusan.ijazah.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('jurusan.ijazah*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-file-contract me-2"></i> Kelola Scan Ijazah
                        </a>
                        <a href="{{ route('transkrip.admin.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('transkrip.admin*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-file-circle-check me-2"></i> Verifikasi Transkrip
                        </a>
                    </div>

                    @php 
                        $isBarangActive = Request::routeIs('barang.index') || 
                                          Request::routeIs('barang-masuk.index') || 
                                          Request::routeIs('barang-keluar.index') ||
                                          Request::routeIs('sirkulasi.*');
                    @endphp
                    <a href="#menuBarang" data-bs-toggle="collapse" aria-expanded="{{ $isBarangActive ? 'true' : 'false' }}" class="list-group-item list-group-item-action border-0 rounded-3 mb-1 d-flex justify-content-between align-items-center text-muted {{ !$isBarangActive ? 'collapsed' : '' }}">
                        <div>
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span class="menu-text ms-2">Data Barang</span>
                        </div>
                        <i class="fa-solid fa-chevron-right small arrow-icon"></i>
                    </a>
                    <div class="collapse submenu rounded-3 mb-1 {{ $isBarangActive ? 'show' : '' }}" id="menuBarang">
                        <a href="{{ route('barang.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('barang.index') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-list-ul me-2"></i> Master Barang
                        </a>
                        <a href="{{ route('barang-masuk.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('barang-masuk.index') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Data Barang Masuk
                        </a>
                        <a href="{{ route('barang-keluar.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('barang-keluar.index') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Data Barang Keluar
                        </a>
                        <a href="{{ route('sirkulasi.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('sirkulasi.*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-arrows-rotate me-2"></i> Sirkulasi Stok Opname
                        </a>
                    </div>

                    @php 
                        $isLemburActive = Request::routeIs('lembur.index') || 
                                          Request::routeIs('lembur.edit') || 
                                          Request::routeIs('rekaplembur*') || 
                                          Request::routeIs('lembur.history*');
                    @endphp
                    <a href="#menuLembur" data-bs-toggle="collapse" aria-expanded="{{ $isLemburActive ? 'true' : 'false' }}" class="list-group-item list-group-item-action border-0 rounded-3 mb-1 d-flex justify-content-between align-items-center text-muted {{ !$isLemburActive ? 'collapsed' : '' }}">
                        <div>
                            <i class="fa-solid fa-business-time"></i>
                            <span class="menu-text ms-2">Lembur</span>
                        </div>
                        <i class="fa-solid fa-chevron-right small arrow-icon"></i>
                    </a>
                    <div class="collapse submenu rounded-3 mb-1 {{ $isLemburActive ? 'show' : '' }}" id="menuLembur">
                        <a href="{{ route('lembur.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('lembur.index', 'lembur.edit') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-business-time me-2"></i> Manajemen Lembur
                        </a>
                        <a href="{{ route('rekaplembur.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('rekaplembur*') || Request::routeIs('lembur.history*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-file-invoice-dollar me-2"></i> Rekap Jam Lembur
                        </a>
                    </div>
                @endif

                @if(Auth::user()->level === 'superadmin')
                    @php
                        $isSistemActive = Request::routeIs('user*') || Request::routeIs('pimpinan*') || Request::routeIs('tahun-akademik*') || Request::routeIs('pangkat*') || Request::routeIs('kelas.*') || Request::routeIs('kelas') || Request::is('kelas/*') || Request::routeIs('jamajar*') || Request::routeIs('superadmin.kurikulum*') || Request::routeIs('setting*') || Request::routeIs('superadmin.pengelolajurusan*') || Request::routeIs('jam-kerja*') || Request::routeIs('superadmin.perusahaan*') || Request::routeIs('superadmin.unit*');
                    @endphp
                    <a href="#menuSistem" data-bs-toggle="collapse" aria-expanded="{{ $isSistemActive ? 'true' : 'false' }}" class="list-group-item list-group-item-action border-0 rounded-3 mb-1 d-flex justify-content-between align-items-center text-muted {{ !$isSistemActive ? 'collapsed' : '' }}">
                        <div>
                            <i class="fa-solid fa-sliders"></i>
                            <span class="menu-text ms-2">Sistem Utama</span>
                        </div>
                        <i class="fa-solid fa-chevron-right small arrow-icon"></i>
                    </a>

                    <div class="collapse submenu rounded-3 mb-1 {{ $isSistemActive ? 'show' : '' }}" id="menuSistem">
                        <a href="{{ route('user.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('user*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-users-gear me-2"></i> Manajemen User
                        </a>
                        <a href="{{ route('pimpinan.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('pimpinan*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-user-shield me-2"></i> Pimpinan Polsri
                        </a>
                        <a href="{{ route('tahun-akademik.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('tahun-akademik*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-calendar-check me-2"></i> Master Tahun Akademik
                        </a>
                        <a href="{{ route('pangkat.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('pangkat*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-id-card-clip"></i> Pangkat & Golongan
                        </a>
                        <a href="{{ route('kelas.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ (Request::routeIs('kelas.*') || Request::routeIs('kelas') || Request::is('kelas/*')) ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-school me-2"></i> Manajemen Kelas
                        </a>
                        <a href="{{ route('jamajar.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('jamajar*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-clock me-2"></i> Manajemen Jam Ajar
                        </a>
                        <a href="{{ route('superadmin.kurikulum.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('superadmin.kurikulum*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-book-bookmark me-2"></i> Manajemen Kurikulum
                        </a>
                        <a href="{{ route('superadmin.pengelolajurusan.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('superadmin.pengelolajurusan*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-sitemap me-2"></i> Pengelola Jurusan
                        </a>
                        <a href="{{ route('jam-kerja.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('jam-kerja*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-business-time me-2"></i> Jam Kerja Tendik
                        </a>
                        <a href="{{ route('superadmin.perusahaan.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('superadmin.perusahaan*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-building me-2"></i> Induk Organisasi
                        </a>
                        <a href="{{ route('superadmin.unit.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('superadmin.unit*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-folder-tree me-2"></i> Anak Perusahaan
                        </a>
                        <a href="{{ route('setting.index') }}" class="list-group-item list-group-item-action border-0 rounded-3 {{ Request::routeIs('setting*') ? 'text-primary fw-bold' : 'text-muted' }}">
                            <i class="fa-solid fa-sliders me-2"></i> Pengaturan Sistem
                        </a>
                    </div>
                @endif

                <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="mt-4 pt-3 border-top">
                    @csrf
                    <button type="button" id="btn-logout" class="btn btn-outline-danger w-100 text-start rounded-3">
                        <i class="fa-solid fa-door-open"></i> <span class="menu-text ms-2">Keluar</span>
                    </button>
                </form>
            </div>
        </nav>

        <main id="content">
            <div class="flex-grow-1">
                @yield('content')
            </div>

            <footer class="bg-white border text-secondary p-3 mt-4 rounded-3 shadow-sm">
                <div class="container-fluid p-0">
                    <div class="row align-items-center justify-content-between small">
                        <div class="col-12 col-md-auto text-center text-md-start mb-2 mb-md-0">
                            <span class="fw-bold text-dark text-uppercase" style="font-size: 12px;">SIMA PRO</span> 
                            <span class="mx-1">&copy;</span> 2024 &ndash; {{ date('Y') }}. Jurusan Akuntansi Polsri.
                        </div>
                        <div class="col-12 col-md-auto text-center text-md-end">
                            <span class="badge bg-light text-primary border px-2 py-1 me-2 font-monospace">v1.0</span>
                            <a href="mailto:gonethekill@gmail.com" class="text-decoration-none text-muted transition-link">
                                <i class="fa-solid fa-envelope text-danger me-1"></i> IT Support: <span class="fw-semibold text-primary">gonethekill@gmail.com</span>
                            </a>
                        </div>
                    </div>
                </div>
            </footer>
        </main>
    </div>

    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            if (window.jQuery && $.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            }

            $(document).on('hidden.bs.modal', '.modal', function () {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('overflow', 'auto');
            });

            $('.sidebar-toggle-btn').on('click', function () {
                if(!$('#sidebar').hasClass('active')) {
                    $('.submenu').collapse('hide');
                }
                
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            $('#btn-logout').on('click', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Apakah Anda ingin keluar?',
                    text: "Sesi kerja Anda pada sistem SIMA PRO akan berakhir.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545', 
                    cancelButtonColor: '#6c757d',  
                    confirmButtonText: '<i class="fa-solid fa-door-open me-1"></i> Ya, Keluar',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#logoutForm').submit();
                    }
                });
            });
        });
    </script>

    @if(session('success'))
    <script>
        if (!window.location.href.includes('dosen') && !window.location.href.includes('jamajar') && !window.location.href.includes('ajardosen') && !window.location.href.includes('kelas') && !window.location.href.includes('manajemen-lembur') && !window.location.href.includes('rekaplembur') && !window.location.href.includes('pimpinan') && !window.location.href.includes('tahun-akademik') && !window.location.href.includes('barang') && !window.location.href.includes('databarangmasuk')) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{!! session('success') !!}",
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Selesai'
            });
        }
    </script>
    @endif

    @if(session('error'))
    <script>
        if (!window.location.href.includes('dosen') && !window.location.href.includes('jamajar') && !window.location.href.includes('ajardosen') && !window.location.href.includes('kelas') && !window.location.href.includes('manajemen-lembur') && !window.location.href.includes('rekaplembur') && !window.location.href.includes('pimpinan') && !window.location.href.includes('tahun-akademik') && !window.location.href.includes('barang') && !window.location.href.includes('databarangmasuk')) {
            Swal.fire({
                icon: 'error',
                title: 'Proses Gagal!',
                text: "{!! session('error') !!}",
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Tutup'
            });
        }
    </script>
    @endif

    @yield('scripts')
</body>
</html>