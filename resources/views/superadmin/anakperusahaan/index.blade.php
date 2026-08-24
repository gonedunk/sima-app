@extends('layouts.app') {{-- Sesuaikan dengan nama layout utama Anda --}}

{{-- Load CSS Lokal --}}
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
    <style>
        /* Desain kustom badge kategori agar kontras dan tidak berwarna putih */
        .badge-kategori-custom {
            background-color: #e2e8f0 !important;
            color: #1e293b !important;
            border: 1px solid #cbd5e1;
            font-size: 0.8rem;
            font-weight: 600;
        }
        /* Mengatur tata letak kontrol pencarian dan filter */
        .search-filter-wrapper {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .clear-search-btn {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 4;
            color: #a0aec0;
        }
        .clear-search-btn:hover {
            color: #e53e3e;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Judul Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Manajemen Unit / Anak Perusahaan</h1>
            <p class="text-muted mb-0">Kelola unit kerja dan anak perusahaan yang terdaftar di bawah organisasi induk.</p>
        </div>
        <a href="{{ route('superadmin.perusahaan.index') }}" class="btn btn-outline-primary shadow-sm">
            <i class="fas fa-building mr-1"></i> Kelola Perusahaan Induk
        </a>
    </div>

    <!-- Alert Notifikasi Flash Session -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm d-none-temp" id="flash-success" data-message="{{ session('success') }}" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm d-none-temp" id="flash-error" data-message="{{ session('error') }}" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Menampilkan Error Validasi Spesifik -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Gagal menyimpan data!</strong> Harap periksa kembali inputan Anda:
            <ul class="mb-0 mt-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- PANEL PENCARIAN & FILTER -->
    <div class="card shadow mb-4">
        <div class="card-body search-filter-wrapper py-3">
            <form action="{{ route('superadmin.unit.index') }}" method="GET" id="form-pencarian">
                <div class="row align-items-end">
                    <!-- Dropdown Filter Kategori -->
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-dark">Filter Kategori Organisasi Induk:</label>
                        <select name="kategori" id="filter-kategori" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Tampilkan Semua Kategori --</option>
                            @php
                                $kategoriMaster = DB::table('induk_organisasi')->pluck('kategori')->unique()->filter();
                            @endphp
                            @foreach($kategoriMaster as $kat)
                                <option value="{{ $kat }}" {{ request('kategori') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Input Pencarian Global dengan tombol Clear Real-URL -->
                    <div class="col-md-8">
                        <label class="small font-weight-bold text-dark">Cari Induk / Unit Perusahaan:</label>
                        <div class="input-group">
                            <div class="position-relative flex-grow-1">
                                <input type="text" name="search" id="search-input" class="form-control pr-5" 
                                       placeholder="Ketik nama unit, wilayah, sektor, atau nama induk..." 
                                       value="{{ request('search') }}">
                                
                                @if(request('search') || request('kategori'))
                                    <a href="{{ route('superadmin.unit.index') }}" class="clear-search-btn" title="Bersihkan Pencarian">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                @endif
                            </div>
                            <div class="input-group-append">
                                <button class="btn btn-primary px-4" type="submit">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabel Data Grouping -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="min-width: 800px;" id="table-unit">
                    <thead>
                        <tr class="bg-light text-dark">
                            <th style="width: 30%;">Nama Unit / Anak Perusahaan</th>
                            <th style="width: 15%;">Wilayah</th>
                            <th style="width: 15%;">Sektor</th>
                            <th style="width: 25%;">Alamat Lengkap</th>
                            <th style="width: 15%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($perusahaanInduk as $induk)
                            <!-- BARIS HEADER GRUP: PERUSAHAAN INDUK -->
                            <tr class="table-secondary font-weight-bold row-induk-group" 
                                data-kategori-induk="{{ $induk->kategori }}" 
                                data-nama-induk="{{ strtolower($induk->nama_induk) }}"
                                style="background-color: #f1f3f5;">
                                <td colspan="4" class="align-middle py-3">
                                    <span class="badge badge-kategori-custom px-3 py-2 mr-2">
                                        {{ $induk->kategori }}
                                    </span>
                                    <span class="text-dark font-weight-bold" style="font-size: 1.05rem;">{{ $induk->nama_induk }}</span>
                                </td>
                                <td class="text-center align-middle py-3">
                                    <button type="button" 
                                            class="btn btn-sm btn-success px-3 btn-tambah-anak shadow-sm" 
                                            data-id-induk="{{ $induk->id_induk }}" 
                                            data-nama-induk="{{ $induk->nama_induk }}">
                                        <i class="fas fa-plus-circle mr-1"></i> Tambah Unit
                                    </button>
                                </td>
                            </tr>

                            <!-- BARIS DAFTAR ANAK PERUSAHAAN -->
                            @if($induk->anak_perusahaan->isEmpty())
                                <tr class="row-anak-item row-empty-info" data-kategori-induk="{{ $induk->kategori }}">
                                    <td colspan="5" class="text-center text-muted py-3 italic">
                                        <small><i class="fas fa-info-circle mr-1"></i> Belum ada unit atau anak perusahaan yang terdaftar di bawah instansi ini.</small>
                                    </td>
                                </tr>
                            @else
                                @foreach($induk->anak_perusahaan as $anak)
                                    <tr class="row-anak-item" 
                                        data-kategori-induk="{{ $induk->kategori }}"
                                        data-search-content="{{ strtolower($anak->nama_unit . ' ' . $anak->wilayah . ' ' . $anak->sektor . ' ' . $anak->alamat_lengkap . ' ' . $induk->nama_induk) }}">
                                        <td class="align-middle pl-4 text-dark font-weight-500">
                                            <i class="fas fa-caret-right text-muted mr-2"></i> {{ $anak->nama_unit }}
                                        </td>
                                        <td class="align-middle">{{ $anak->wilayah ?? '-' }}</td>
                                        <td class="align-middle">{{ $anak->sektor ?? '-' }}</td>
                                        <td class="align-middle text-wrap">{{ $anak->alamat_lengkap ?? '-' }}</td>
                                        <td class="text-center align-middle">
                                            <!-- Tombol Edit -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-info btn-circle shadow-sm btn-trigger-edit" 
                                                    data-target-modal="#modalEditAnak{{ $anak->id_unit }}"
                                                    title="Edit Unit">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>

                                            <!-- Form Hapus -->
                                            <form action="{{ route('superadmin.unit.destroy', $anak->id_unit) }}" method="POST" class="d-inline ml-1 form-hapus-unit">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger btn-circle shadow-sm btn-konfirmasi-hapus" 
                                                        title="Hapus Unit">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- MODAL EDIT ANAK PERUSAHAAN -->
                                    <div class="modal fade" id="modalEditAnak{{ $anak->id_unit }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content text-left">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-edit mr-2"></i>Edit Unit / Anak Perusahaan
                                                    </h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('superadmin.unit.update', $anak->id_unit) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold">Perusahaan Induk</label>
                                                            <input type="text" class="form-control" value="{{ $induk->nama_induk }}" disabled readonly style="background-color: #f1f3f5;">
                                                            <input type="hidden" name="id_induk" value="{{ $induk->id_induk }}">
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold">Nama Unit / Anak Perusahaan <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="nama_unit" value="{{ $anak->nama_unit }}" required>
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold">Wilayah</label>
                                                            <input type="text" class="form-control" name="wilayah" value="{{ $anak->wilayah }}">
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold">Sektor</label>
                                                            <input type="text" class="form-control" name="sektor" value="{{ $anak->sektor }}">
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold">Alamat Lengkap</label>
                                                            <textarea class="form-control" name="alamat_lengkap" rows="3">{{ $anak->alamat_lengkap }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-info">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <span class="text-muted">Tidak ada data organisasi induk yang terdaftar.</span><br>
                                    <a href="{{ route('superadmin.perusahaan.index') }}" class="btn btn-sm btn-primary mt-2">Buat Perusahaan Induk</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINASI DUA LAJUR -->
        @if($perusahaanInduk instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $perusahaanInduk->hasPages())
            <div class="card-footer bg-white d-flex flex-column flex-md-row justify-content-between align-items-center py-3 border-top" id="pagination-container">
                <div class="text-muted small mb-2 mb-md-0">
                    Menampilkan {{ $perusahaanInduk->firstItem() }} sampai {{ $perusahaanInduk->lastItem() }} dari {{ $perusahaanInduk->total() }} Perusahaan Induk beserta unit kerjanya.
                </div>
                <div>
                    {{ $perusahaanInduk->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- MODAL TAMBAH ANAK PERUSAHAAN -->
<div class="modal fade" id="modalTambahAnak" tabindex="-1" role="dialog" aria-labelledby="modalTambahAnakLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalTambahAnakLabel">
                    <i class="fas fa-plus-circle mr-2"></i>Tambah Anak Perusahaan Baru
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('superadmin.unit.store') }}" method="POST">
                @csrf
                <div class="modal-body text-left">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Perusahaan Induk (Grup)</label>
                        <input type="text" id="tampil_nama_induk" class="form-control" readonly style="background-color: #e9ecef; font-weight: 500;">
                        <input type="hidden" name="id_induk" id="input_id_induk">
                    </div>

                    <div class="form-group mb-3">
                        <label for="nama_unit" class="font-weight-bold text-dark">Nama Anak Perusahaan / Unit <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_unit" required placeholder="Masukkan nama unit / anak perusahaan">
                    </div>

                    <div class="form-group mb-3">
                        <label for="wilayah" class="font-weight-bold text-dark">Wilayah</label>
                        <input type="text" class="form-control" name="wilayah" placeholder="Contoh: Palembang, Jakarta">
                    </div>

                    <div class="form-group mb-3">
                        <label for="sektor" class="font-weight-bold text-dark">Sektor</label>
                        <input type="text" class="form-control" name="sektor" placeholder="Contoh: Pendidikan, IT, Manufaktur">
                    </div>

                    <div class="form-group mb-3">
                        <label for="alamat_lengkap" class="font-weight-bold text-dark">Alamat Lengkap</label>
                        <textarea class="form-control" name="alamat_lengkap" rows="3" placeholder="Masukkan alamat lengkap kantor unit kerja"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Pemuatan JQuery Lokal v3.7.1 -->
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<!-- Pemuatan Bootstrap JS Lokal -->
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<!-- Pemuatan SweetAlert2 Lokal -->
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // ==========================================================
        // SWEETALERT2: INTEGRASI POPUP NOTIFIKASI LOKAL
        // ==========================================================
        const flashSuccess = document.getElementById('flash-success');
        const flashError = document.getElementById('flash-error');

        if (flashSuccess && typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: flashSuccess.getAttribute('data-message'),
                showConfirmButton: false,
                timer: 2000
            });
            flashSuccess.remove();
        }

        if (flashError && typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: flashError.getAttribute('data-message'),
                confirmButtonColor: '#3085d6',
            });
            flashError.remove();
        }

        // ==========================================================
        // PENANGANAN MODAL TAMBAH & EDIT UNIT
        // ==========================================================
        
        // 1. Modal Tambah Unit
        const tombolTambah = document.querySelectorAll('.btn-tambah-anak');
        tombolTambah.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const idInduk = this.getAttribute('data-id-induk');
                const namaInduk = this.getAttribute('data-nama-induk');

                document.getElementById('input_id_induk').value = idInduk;
                document.getElementById('tampil_nama_induk').value = namaInduk;

                const modalTambah = document.getElementById('modalTambahAnak');
                bukaModalManually(modalTambah);
            });
        });

        // 2. Modal Edit Unit
        const tombolEdit = document.querySelectorAll('.btn-trigger-edit');
        tombolEdit.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const targetSelector = this.getAttribute('data-target-modal');
                const modalEditTarget = document.querySelector(targetSelector);

                if (modalEditTarget) {
                    bukaModalManually(modalEditTarget);
                }
            });
        });

        // ==========================================================
        // SISTEM DETEKSI TOMBOL BATAL & TOMBOL SILANG (UNIVERSAL)
        // ==========================================================
        document.addEventListener('click', function(e) {
            const closeButton = e.target.closest('[data-dismiss="modal"]');
            
            if (closeButton) {
                e.preventDefault();
                const modalTerbuka = closeButton.closest('.modal');
                
                if (modalTerbuka) {
                    tutupModalManually(modalTerbuka);
                }
            }
        });

        // ==========================================================
        // FUNGSI STANDARISASI MODAL ENGINE
        // ==========================================================
        function bukaModalManually(modalElement) {
            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                $(modalElement).modal('show');
            } else {
                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                modalElement.setAttribute('aria-modal', 'true');
                modalElement.removeAttribute('aria-hidden');
                document.body.classList.add('modal-open');
                
                if (!document.querySelector('.modal-backdrop')) {
                    var backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
            }
        }

        function tutupModalManually(modalElement) {
            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                $(modalElement).modal('hide');
            } else {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                modalElement.setAttribute('aria-hidden', 'true');
                modalElement.removeAttribute('aria-modal');
                
                const sisaModalTerbuka = document.querySelectorAll('.modal.show');
                if (sisaModalTerbuka.length === 0) {
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                }
            }
        }

        // ==========================================================
        // SWEETALERT2: KONFIRMASI HAPUS SEBELUM SUBMIT FORM
        // ==========================================================
        const btnHapus = document.querySelectorAll('.btn-konfirmasi-hapus');
        btnHapus.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('.form-hapus-unit');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: "Data unit/anak perusahaan ini akan dihapus permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                } else {
                    if (confirm('Apakah Anda yakin ingin menghapus data unit ini?')) {
                        form.submit();
                    }
                }
            });
        });

    });
</script>
@endsection