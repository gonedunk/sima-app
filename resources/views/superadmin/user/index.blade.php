@extends('layouts.app')

@section('content')
<div class="container-fluid p-4" style="background-color: #f8fafc; min-height: 100vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold" style="color: #4f46e5;">Manajemen User</h3>
            <p class="text-muted small mb-0">Kelola hak akses, level akun, dan penempatan program dosen/staff.</p>
        </div>
        <button class="btn text-white fw-bold shadow-sm px-3 py-2 btn-tambah-custom" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus me-1"></i> Tambah User
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-custom-success small py-2 px-3 mb-3 d-flex align-items-center shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white border-0 pt-3 pb-2">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-users-gear text-primary me-2"></i>Daftar Pengguna Sistem
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: #f1f5f9; color: #475569;">
                        <tr>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase">Nama Lengkap</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Username</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Email</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Level</th>
                            <th class="border-0 py-3 small fw-bold text-uppercase">Program</th>
                            <th class="border-0 px-4 py-3 small fw-bold text-uppercase text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-dark">
                        @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-3 fw-bold text-dark">{{ $user->nama_lengkap }}</td>
                            <td class="text-secondary font-monospace">{{ $user->username }}</td>
                            <td class="text-muted">{{ $user->email ?? '-' }}</td>
                            <td>
                                @if($user->level == 'superadmin')
                                    <span class="badge px-3 py-1.5 rounded-pill shadow-xs font-semibold" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; font-size: 11px;">
                                        {{ $user->level }}
                                    </span>
                                @else
                                    <span class="badge px-3 py-1.5 rounded-pill shadow-xs font-semibold" style="background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; font-size: 11px;">
                                        {{ $user->level }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($user->namaProdi)
                                    <span class="fw-semibold text-dark">{{ $user->namaProdi }}</span>
                                    <br><small class="text-muted" style="font-size: 11px;">Kode: {{ $user->kode_prodi }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center px-4">
                                <button class="btn btn-sm text-white me-1 btn-edit btn-action-edit-custom" 
                                        data-id="{{ $user->id }}"
                                        data-nama="{{ $user->nama_lengkap }}"
                                        data-username="{{ $user->username }}"
                                        data-email="{{ $user->email }}"
                                        data-level="{{ $user->level }}"
                                        data-prodi="{{ $user->kode_prodi }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                
                                <form id="delete-form-{{ $user->id }}" action="/user/delete/{{ $user->id }}" method="POST" class="d-inline form-delete-container">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm text-white btn-action-hapus btn-action-delete-custom" data-id="{{ $user->id }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-user-slash fa-2x mb-3 text-opacity-25 text-secondary"></i>
                                <p class="mb-0 small fw-semibold">Belum ada data user tersimpan di sistem.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%); border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Tambah User Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/user/store" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control focus-purple" placeholder="Contoh: Dr. Erlangga, M.Kom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Username</label>
                        <input type="text" name="username" class="form-control focus-purple" placeholder="Masukkan username login" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Email</label>
                        <input type="email" name="email" class="form-control focus-purple" placeholder="nama@polsri.ac.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Password</label>
                        <input type="password" name="password" class="form-control focus-purple" placeholder="••••••••" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Level</label>
                            <select name="level" class="form-select focus-purple" required>
                                @foreach($users->pluck('level')->unique() as $lvl)
                                    <option value="{{ $lvl }}">{{ $lvl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Program</label>
                            <select name="kode_prodi" class="form-select focus-purple">
                                <option value="">-- Pilih Program --</option>
                                @foreach($prodis as $prodi)
                                    <option value="{{ $prodi->kodeProdi }}">{{ $prodi->namaProdi }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-light fw-semibold text-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%); border: none;">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Ubah Data User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditAction" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit_nama" class="form-control focus-purple" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control focus-purple" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control focus-purple">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Password Baru <span class="text-muted small fw-normal text-none">(Kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="password" class="form-control focus-purple" placeholder="••••••••">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Level</label>
                            <select name="level" id="edit_level" class="form-select focus-purple" required>
                                @foreach($users->pluck('level')->unique() as $lvl)
                                    <option value="{{ $lvl }}">{{ $lvl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase tracking-wider">Program</label>
                            <select name="kode_prodi" id="edit_prodi" class="form-select focus-purple">
                                <option value="">-- Pilih Program --</option>
                                @foreach($prodis as $prodi)
                                    <option value="{{ $prodi->kodeProdi }}">{{ $prodi->namaProdi }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-light fw-semibold text-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold text-white px-4" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none;">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    /* Desain Tombol Tambah Gradien */
    .btn-tambah-custom {
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        border: none;
        border-radius: 8px;
        transition: opacity 0.2s ease;
    }
    .btn-tambah-custom:hover {
        opacity: 0.9;
    }

    /* Kustomisasi Warna Tombol Aksi */
    .btn-action-edit-custom {
        background-color: #6366f1; /* Ungu Indigo Soft */
        border: none;
    }
    .btn-action-edit-custom:hover {
        background-color: #4f46e5;
    }
    .btn-action-delete-custom {
        background-color: #3b82f6; /* Biru Royal Soft */
        border: none;
    }
    .btn-action-delete-custom:hover {
        background-color: #2563eb;
    }

    /* Efek Fokus Input Form Berpendar Ungu */
    .focus-purple {
        border-radius: 8px;
        border: 1.5px solid #e5e7eb;
    }
    .focus-purple:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    /* Notifikasi alert khusus */
    .alert-custom-success {
        background-color: #f0fdf4;
        border-left: 4px solid #22c55e;
        color: #166534;
        border-radius: 6px;
    }
</style>

<script>
    $(document).ready(function() {
        
        // 1. LOGIK RE-MAPPING DATA PADA MODAL EDIT
        $(document).on('click', '.btn-edit', function() {
            let id = $(this).data('id');
            let nama = $(this).data('nama');
            let username = $(this).data('username');
            let email = $(this).data('email');
            let level = $(this).data('level');
            let prodi = $(this).data('prodi');

            $('#formEditAction').attr('action', '/user/update/' + id);
            $('#edit_nama').val(nama);
            $('#edit_username').val(username);
            $('#edit_email').val(email);
            
            $('#edit_level').val(level);
            $('#edit_prodi').val(prodi);

            $('#modalEdit').modal('show');
        });

        // 2. LOGIK INTERCEPTOR TOMBOL HAPUS (SweetAlert2)
        $(document).on('click', '.btn-action-hapus', function(e) {
            e.preventDefault();
            
            let userId = $(this).data('id');
            let targetForm = $('#delete-form-' + userId);

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data user ini akan terhapus secara permanen dari sistem!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6', // Menyesuaikan tema biru royal untuk konfirmasi
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (targetForm.length) targetForm.submit();
                }
            });
        });

    });
</script>
@endsection