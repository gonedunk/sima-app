@extends('layouts.app')

@section('styles')
    <!-- CSS Select2 & SweetAlert2 Lokal dari folder public/css -->
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
    
    <style>
        /* Mengatur agar dropdown Select2 tampil di atas modal Bootstrap */
        .select2-container--open {
            z-index: 1060 !important;
        }
        .select2-container {
            width: 100% !important;
        }
        /* Penyesuaian tampilan Select2 agar selaras dengan Bootstrap form-control-sm */
        .select2-container--default .select2-selection--single {
            height: 31px !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.25rem !important;
            padding-top: 1px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            padding-left: 8px !important;
            font-size: 13px !important;
            color: #212529 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 28px !important;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">Manajemen Data Pendidik & Kependidikan</h3>
            <p class="text-muted small mb-0">Kelola profil pegawai menggunakan integrasi Select2 & SweetAlert2 secara lokal (Offline).</p>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-primary fw-semibold shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahPegawai">
                <i class="fa-solid fa-user-plus me-2"></i>Tambah Pegawai Baru
            </button>
        </div>
    </div>

    {{-- BARIS FILTER DATA --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('dosen.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Cari Nama, NIP, atau NIDN..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="level" class="form-select form-select-sm select2-filter">
                        <option value="">-- Semua Kategori Pegawai --</option>
                        <option value="01" {{ request('level') == '01' ? 'selected' : '' }}>Tenaga Pendidik (Dosen)</option>
                        <option value="02" {{ request('level') == '02' ? 'selected' : '' }}>Tenaga Kependidikan (Tendik)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="statusPegawai" class="form-select form-select-sm select2-filter">
                        <option value="">-- Semua Status Pegawai --</option>
                        <option value="LB" {{ request('statusPegawai') == 'LB' ? 'selected' : '' }}>LB (Luar Biasa)</option>
                        <option value="PPPK" {{ request('statusPegawai') == 'PPPK' ? 'selected' : '' }}>PPPK</option>
                        <option value="CPNS" {{ request('statusPegawai') == 'CPNS' ? 'selected' : '' }}>CPNS</option>
                        <option value="PNS" {{ request('statusPegawai') == 'PNS' ? 'selected' : '' }}>PNS</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-sm btn-dark fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL UTAMA --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light text-secondary fw-semibold">
                        <tr>
                            <th width="4%" class="text-center">No</th>
                            <th width="10%">Kategori</th>
                            <th width="18%">NIP / NIDN</th>
                            <th width="22%">Nama Lengkap</th>
                            <th width="14%">Pangkat / Jabatan</th>
                            <th width="12%">Pendidikan Akhir</th>
                            <th width="10%">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pegawais as $index => $peg)
                        <tr>
                            <td class="text-center text-muted">{{ $pegawais->firstItem() + $index }}</td>
                            <td>
                                @if($peg->level == '01')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 w-100 text-center">Dosen</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 w-100 text-center text-dark">Tendik</span>
                                @endif
                            </td>
                            <td>
                                <div class="small"><b>NIP:</b> {{ $peg->nip ?? '-' }}</div>
                                <div class="small mt-1 text-muted"><b>NIDN:</b> {{ $peg->nidn ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $peg->nama }}</div>
                                <span class="text-muted" style="font-size: 11px;">Agama: {{ $peg->namaAgama ?? '-' }} | Gender: {{ $peg->jenisKelamin }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-secondary">{{ $peg->namaJabatan ?? '-' }}</div>
                                <span class="badge bg-light text-dark border mt-1" style="font-size: 11px;">Gol: {{ $peg->golongan ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $peg->pendidikan ?? '-' }}</div>
                                <div class="text-muted small text-truncate" style="max-width: 140px;" title="{{ $peg->universitas }}">{{ $peg->universitas ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-dark px-2 py-1">{{ $peg->statusPegawai ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalEditPegawai{{ $peg->id }}">
                                        <i class="fa-solid fa-user-gear"></i>
                                    </button>
                                    <form id="formHapusPegawai{{ $peg->id }}" action="{{ route('dosen.delete', $peg->id) }}" method="POST" style="display:none;">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button type="button" onclick="konfirmasiHapusPegawai('{{ $peg->id }}', '{{ $peg->nama }}')" class="btn btn-sm btn-outline-danger py-1 px-2">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Arsip rekaman data pegawai tidak ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 py-3">
                {{ $pegawais->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH PEGAWAI --}}
<div class="modal fade" id="modalTambahPegawai" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('dosen.store') }}" method="POST" class="modal-content text-dark">
            @csrf
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" style="font-size: 15px;"><i class="fa-solid fa-user-plus me-2"></i>Form Registrasi Pegawai Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3" style="font-size: 13px;">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Kategori Pegawai <span class="text-danger">*</span></label>
                        <select name="level" class="form-select form-select-sm select2-modal" style="width:100%;" required>
                            <option value="01">Tenaga Pendidik (Dosen)</option>
                            <option value="02">Tenaga Kependidikan (Tendik)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama lengkap..." required>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">NIP</label>
                        <input type="text" name="nip" class="form-control form-control-sm" placeholder="Masukkan NIP...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">NIDN</label>
                        <input type="text" name="nidn" class="form-control form-control-sm" placeholder="Masukkan NIDN...">
                    </div>
                </div>

                <hr class="my-2 text-muted">

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Status Kepegawaian</label>
                        <select name="statusPegawai" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="">-- Pilih Status --</option>
                            <option value="LB">LB (Luar Biasa)</option>
                            <option value="PPPK">PPPK</option>
                            <option value="CPNS">CPNS</option>
                            <option value="PNS">PNS</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Golongan / Ruang</label>
                        <select name="golongan" class="form-select form-select-sm select2-modal select-golongan" style="width:100%;">
                            <option value="">-- Pilih Golongan --</option>
                            @foreach($golongans as $g)
                                <option value="{{ $g->golonganRuang }}" data-jabatan="{{ $g->jabatanAkademik }}">{{ $g->golonganRuang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Jabatan Akademik</label>
                        <select name="namaJabatan" class="form-select form-select-sm select2-modal select-jabatan" style="width:100%;">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($golongans->unique('jabatanAkademik') as $g)
                                <option value="{{ $g->jabatanAkademik }}">{{ $g->jabatanAkademik }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">TMT Golongan</label>
                        <input type="date" name="tmtGolongan" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">TMT CPNS</label>
                        <input type="date" name="tmt_cpns" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">TMT Jabatan</label>
                        <input type="date" name="tmtJabatan" class="form-control form-control-sm">
                    </div>
                </div>

                <hr class="my-2 text-muted">

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Pendidikan Akhir</label>
                        <select name="pendidikan" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="">-- Pilih --</option>
                            <option value="D-3">D-3</option>
                            <option value="S-1">S-1</option>
                            <option value="S-2">S-2</option>
                            <option value="S-3">S-3</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold mb-1">Universitas Kelulusan</label>
                        <select name="universitas" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="">-- Cari Nama Perguruan Tinggi --</option>
                            @foreach($universitases as $uni)
                                <option value="{{ $uni->namaPt }}">{{ $uni->namaPt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Jenis Kelamin</label>
                        <select name="jenisKelamin" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Agama</label>
                        <select name="agama" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="">-- Pilih Agama --</option>
                            @foreach($agamas as $ag)
                                <option value="{{ $ag->kodeAgama }}">{{ $ag->namaAgama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary fw-bold">Simpan Pegawai</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT PEGAWAI --}}
@foreach($pegawais as $peg)
<div class="modal fade" id="modalEditPegawai{{ $peg->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('dosen.update', $peg->id) }}" method="POST" class="modal-content text-dark">
            @csrf 
            @method('PUT')
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-gear me-2"></i>Ubah Data Profil Pegawai</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3" style="font-size: 13px;">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Kategori Pegawai</label>
                        <select name="level" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="01" {{ $peg->level == '01' ? 'selected' : '' }}>Tenaga Pendidik (Dosen)</option>
                            <option value="02" {{ $peg->level == '02' ? 'selected' : '' }}>Tenaga Kependidikan (Tendik)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Nama Lengkap & Gelar</label>
                        <input type="text" name="nama" class="form-control form-control-sm" value="{{ $peg->nama }}" required>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">NIP</label>
                        <input type="text" name="nip" class="form-control form-control-sm" value="{{ $peg->nip }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">NIDN</label>
                        <input type="text" name="nidn" class="form-control form-control-sm" value="{{ $peg->nidn }}">
                    </div>
                </div>

                <hr class="my-2 text-muted">

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Status Kepegawaian</label>
                        <select name="statusPegawai" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="LB" {{ $peg->statusPegawai == 'LB' ? 'selected' : '' }}>LB (Luar Biasa)</option>
                            <option value="PPPK" {{ $peg->statusPegawai == 'PPPK' ? 'selected' : '' }}>PPPK</option>
                            <option value="CPNS" {{ $peg->statusPegawai == 'CPNS' ? 'selected' : '' }}>CPNS</option>
                            <option value="PNS" {{ $peg->statusPegawai == 'PNS' ? 'selected' : '' }}>PNS</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Golongan</label>
                        <select name="golongan" class="form-select form-select-sm select2-modal select-golongan" style="width:100%;">
                            <option value="">-- Pilih --</option>
                            @foreach($golongans as $g)
                                <option value="{{ $g->golonganRuang }}" {{ $peg->golongan == $g->golonganRuang ? 'selected' : '' }} data-jabatan="{{ $g->jabatanAkademik }}">{{ $g->golonganRuang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Jabatan Akademik</label>
                        <select name="namaJabatan" class="form-select form-select-sm select2-modal select-jabatan" style="width:100%;">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($golongans->unique('jabatanAkademik') as $g)
                                <option value="{{ $g->jabatanAkademik }}" {{ $peg->namaJabatan == $g->jabatanAkademik ? 'selected' : '' }}>{{ $g->jabatanAkademik }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">TMT Golongan</label>
                        <input type="date" name="tmtGolongan" class="form-control form-control-sm" value="{{ $peg->tmtGolongan }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">TMT CPNS</label>
                        <input type="date" name="tmt_cpns" class="form-control form-control-sm" value="{{ $peg->tmt_cpns }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">TMT Jabatan</label>
                        <input type="date" name="tmtJabatan" class="form-control form-control-sm" value="{{ $peg->tmtJabatan }}">
                    </div>
                </div>

                <hr class="my-2 text-muted">

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Pendidikan Akhir</label>
                        <select name="pendidikan" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="D-3" {{ $peg->pendidikan == 'D-3' ? 'selected' : '' }}>D-3</option>
                            <option value="S-1" {{ $peg->pendidikan == 'S-1' ? 'selected' : '' }}>S-1</option>
                            <option value="S-2" {{ $peg->pendidikan == 'S-2' ? 'selected' : '' }}>S-2</option>
                            <option value="S-3" {{ $peg->pendidikan == 'S-3' ? 'selected' : '' }}>S-3</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold mb-1">Universitas</label>
                        <select name="universitas" class="form-select form-select-sm select2-modal" style="width:100%;">
                            @foreach($universitases as $uni)
                                <option value="{{ $uni->namaPt }}" {{ $peg->universitas == $uni->namaPt ? 'selected' : '' }}>{{ $uni->namaPt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Jenis Kelamin</label>
                        <select name="jenisKelamin" class="form-select form-select-sm select2-modal" style="width:100%;">
                            <option value="L" {{ $peg->jenisKelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ $peg->jenisKelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Agama</label>
                        <select name="agama" class="form-select form-select-sm select2-modal" style="width:100%;">
                            @foreach($agamas as $ag)
                                <option value="{{ $ag->kodeAgama }}" {{ $peg->agama == $ag->kodeAgama ? 'selected' : '' }}>{{ $ag->namaAgama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary fw-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@section('scripts')
<!-- JS Select2 & SweetAlert2 Lokal dari folder public/js -->
<script src="{{ asset('js/select2.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
$(document).ready(function() {
    // 1. Inisialisasi Select2 Filter di Halaman Utama
    $('.select2-filter').select2({
        width: '100%'
    });

    // 2. Inisialisasi Select2 di dalam Modal saat Modal Dibuka
    $('.modal').on('shown.bs.modal', function () {
        $(this).find('.select2-modal').select2({
            dropdownParent: $(this),
            width: '100%'
        });
    });

    // 3. Auto-select Jabatan Akademik berdasarkan Golongan
    $(document).on('change', '.select-golongan', function() {
        var container = $(this).closest('.modal-body');
        var jabatanOtomatis = $(this).find(':selected').data('jabatan');
        
        if (jabatanOtomatis) {
            container.find('.select-jabatan').val(jabatanOtomatis).trigger('change');
        } else {
            container.find('.select-jabatan').val('').trigger('change');
        }
    });

    // 4. Notifikasi SweetAlert2 dari Flash Session Controller
    @if(session('success'))
        Swal.fire({
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            title: 'Gagal!',
            text: "{{ session('error') }}",
            icon: 'error',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#dc3545'
        });
    @endif

    @if($errors->any())
        Swal.fire({
            title: 'Terjadi Kesalahan Validasi!',
            html: '{!! implode("<br>", $errors->all()) !!}',
            icon: 'error',
            confirmButtonText: 'Perbaiki',
            confirmButtonColor: '#dc3545'
        });
    @endif
});

// 5. Konfirmasi Hapus Data Pegawai
function konfirmasiHapusPegawai(id, namaPegawai) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Data Pegawai?',
            text: `Apakah Anda yakin ingin menghapus data fungsional milik ${namaPegawai}? Tindakan ini permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formHapusPegawai' + id).submit();
            }
        });
    } else {
        if (confirm(`Apakah Anda yakin ingin menghapus data pegawai ${namaPegawai}?`)) {
            document.getElementById('formHapusPegawai' + id).submit();
        }
    }
}
</script>
@endsection
@endsection