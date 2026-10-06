@extends('layouts.app')

@section('styles')
    <!-- Select2 CSS Lokal -->
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    
    <style>
        /* Mengatur Z-Index Select2 berada di atas Modal Bootstrap */
        .select2-container--open {
            z-index: 1060 !important;
        }
        .select2-container {
            width: 100% !important;
        }
        /* Penyesuaian agar tampilan Select2 presisi dengan Bootstrap form-control-sm */
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
<div class="container-fluid p-4">
    
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">Master Data Mahasiswa</h3>
            <p class="text-muted small mb-0">Manajemen biodata induk dan data registrasi awal mahasiswa Jurusan Akuntansi.</p>
        </div>
        <div class="d-flex gap-2 mb-3">
            <a href="{{ route('mahasiswa.export') }}" class="btn btn-sm btn-outline-success fw-semibold shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Unduh Template Excel
            </a>
            
            <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportMhs">
                <i class="fa-solid fa-file-import me-1"></i> Import Mahasiswa Baru
            </button>
        </div>
        <div>
            <button class="btn btn-primary btn-sm fw-semibold shadow-sm px-3 py-2" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalTambahMhs">
                <i class="fa-solid fa-user-plus me-2"></i>Tambah Mahasiswa
            </button>
        </div>
    </div>

    <!-- FILTER HALAMAN UTAMA -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-2 align-items-center">
                <div class="col-6 col-md-2">
                    <select name="ta" class="form-select form-select-sm select2-filter">
                        <option value="">-- Semua Angkatan --</option>
                        @foreach($tahunAkademiks as $ta)
                            <option value="{{ $ta->tahunAkademik }}" {{ request('ta') == $ta->tahunAkademik ? 'selected' : '' }}>
                                Angkatan {{ $ta->tahunAkademik }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-6 col-md-3">
                    <select name="prodi" class="form-select form-select-sm select2-filter">
                        <option value="">-- Semua Prodi --</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->kodeProdi }}" {{ request('prodi') == $p->kodeProdi ? 'selected' : '' }}>
                                {{ $p->namaProdi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Cari berdasarkan NPM, Nama, atau No. Registrasi..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-sm btn-dark fw-semibold"><i class="fa-solid fa-filter me-1"></i> Saring</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL DATA MAHASISWA -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-dark">
                    <thead style="background-color: #f1f5f9; color: #475569; font-size: 12px;">
                        <tr>
                            <th class="border-0 px-3 py-3 text-center fw-bold text-uppercase" style="width: 50px;">No</th>
                            <th class="border-0 py-3 fw-bold text-uppercase">No. Registrasi</th>
                            <th class="border-0 py-3 fw-bold text-uppercase">NPM</th>
                            <th class="border-0 py-3 fw-bold text-uppercase" style="min-width: 200px;">Nama Lengkap</th>
                            <th class="border-0 py-3 fw-bold text-uppercase text-center">L/P</th>
                            <th class="border-0 py-3 fw-bold text-uppercase">Program / Prodi</th>
                            <th class="border-0 py-3 fw-bold text-uppercase text-center">Kelas Awal</th>
                            <th class="border-0 py-3 fw-bold text-uppercase">Jalur Masuk</th>
                            <th class="border-0 py-3 fw-bold text-uppercase text-center">KIP</th>
                            <th class="border-0 py-3 fw-bold text-uppercase">Kontak</th>
                            <th class="border-0 py-3 fw-bold text-uppercase text-center">Agama</th>
                            <th class="border-0 py-3 fw-bold text-uppercase">Ket.</th>
                            <th class="border-0 px-3 py-3 text-center fw-bold text-uppercase" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 13px;">
                        @forelse($mahasiswas as $index => $mhs)
                        <tr>
                            <td class="px-3 py-3 text-center text-muted font-monospace small">
                                {{ ($mahasiswas->currentPage() - 1) * $mahasiswas->perPage() + $index + 1 }}
                            </td>
                            <td class="font-monospace text-secondary">{{ $mhs->noRegistrasi ?? '-' }}</td>
                            <td class="fw-bold text-primary font-monospace">{{ $mhs->npm ?? '-' }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $mhs->nama }}</div>
                                <span class="text-muted font-monospace" style="font-size: 11px;">Angkatan {{ $mhs->tahunAkademik }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ ($mhs->jenisKelamin == 'L' || $mhs->jenisKelamin == 'Laki-laki') ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} rounded-circle px-2">
                                    {{ ($mhs->jenisKelamin == 'L' || $mhs->jenisKelamin == 'Laki-laki') ? 'L' : 'P' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-1">{{ $mhs->namaProdi ?? 'Prodi: '.$mhs->kodeProdi }}</div>
                                <span class="badge bg-light text-primary border px-2 py-0.5 fw-semibold font-monospace">
                                    Kelas: {{ $mhs->namaProgram ?? $mhs->program ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center font-monospace fw-bold">{{ $mhs->kelas ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark border px-2 py-1">{{ $mhs->namaJalur ?? $mhs->jalur ?? '-' }}</span></td>
                            <td class="text-center">
                                @if($mhs->kip == 'Ya' || $mhs->kip == 'Y')
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 10px;"><i class="fa-solid fa-id-card me-1"></i> KIP</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <div class="small"><i class="fa-solid fa-envelope text-muted me-1"></i>{{ $mhs->email ?? '-' }}</div>
                                <div class="small text-muted font-monospace"><i class="fa-solid fa-phone text-muted me-1"></i>{{ $mhs->hp ?? $mhs->telpon ?? '-' }}</div>
                            </td>
                            <td class="text-center small">{{ $mhs->namaAgama ?? $mhs->agama ?? '-' }}</td>
                            <td class="text-muted small">{{ $mhs->keterangan ?? '-' }}</td>
                            <td class="px-3 text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary border shadow-sm btn-edit-mhs" 
                                        style="border-radius: 4px; margin-right: 2px;"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEditMhs"
                                        data-id="{{ $mhs->id }}"
                                        data-npm="{{ $mhs->npm }}"
                                        data-no_reg="{{ $mhs->noRegistrasi }}"
                                        data-nama="{{ $mhs->nama }}"
                                        data-jk="{{ $mhs->jenisKelamin }}"
                                        data-prodi="{{ $mhs->kodeProdi }}"
                                        data-program="{{ $mhs->program }}"
                                        data-kelas="{{ $mhs->kelas }}"
                                        data-ta="{{ $mhs->tahunAkademik }}"
                                        data-jalur="{{ $mhs->jalur }}"
                                        data-agama="{{ $mhs->agama }}"
                                        data-kip="{{ $mhs->kip }}"
                                        data-hp="{{ $mhs->hp }}"
                                        data-telpon="{{ $mhs->telpon }}"
                                        data-email="{{ $mhs->email }}"
                                        data-jurusan="{{ $mhs->kodeJurusan }}"
                                        data-keterangan="{{ $mhs->keterangan }}">
                                        <i class="fa-solid fa-pen-to-square text-dark"></i>
                                    </button>
                                    
                                    <form action="{{ route('mahasiswa.destroy', $mhs->id) }}" method="POST" class="form-delete d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger border shadow-sm btn-delete-mhs" 
                                            style="border-radius: 4px;" 
                                            data-nama="{{ $mhs->nama }}">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center py-5 text-muted">Data Kosong.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($mahasiswas->hasPages())
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted">Menampilkan {{ $mahasiswas->firstItem() }} sampai {{ $mahasiswas->lastItem() }} dari {{ $mahasiswas->total() }} total mahasiswa</span>
            <div>{{ $mahasiswas->withQueryString()->links('pagination::bootstrap-5') }}</div>
        </div>
        @endif
    </div>
</div>

<!-- MODAL TAMBAH MAHASISWA -->
<div class="modal fade" id="modalTambahMhs" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="{{ route('mahasiswa.store') }}" method="POST" class="modal-content text-dark">
            @csrf
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-user-plus me-2"></i>Form Tambah Mahasiswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3" style="font-size: 13px;">
                <div class="col-md-6"><label class="fw-semibold mb-1">NPM <span class="text-danger">*</span></label><input type="text" name="npm" class="form-control form-control-sm" required placeholder="Contoh: 06213050xxxx"></div>
                <div class="col-md-6"><label class="fw-semibold mb-1">No. Registrasi</label><input type="text" name="noRegistrasi" class="form-control form-control-sm"></div>
                <div class="col-md-12"><label class="fw-semibold mb-1">Nama Lengkap <span class="text-danger">*</span></label><input type="text" name="nama" class="form-control form-control-sm" required></div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-1">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select name="jenisKelamin" class="form-select form-select-sm select2-tambah" required>
                        <option value="L">Laki-Laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-1">Program Studi <span class="text-danger">*</span></label>
                    <select name="kodeProdi" class="form-select form-select-sm select2-tambah" required>
                        @foreach($prodis as $p)<option value="{{ $p->kodeProdi }}">{{ $p->namaProdi }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Program Kelas Waktu</label>
                    <select name="program" class="form-select form-select-sm select2-tambah">
                        @foreach($allPrograms as $prog)<option value="{{ $prog->kodeProgram }}">{{ $prog->namaProgram }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="fw-semibold mb-1">Kelas Awal</label><input type="text" name="kelas" class="form-control form-control-sm" placeholder="Contoh: 1 AMB"></div>
                <div class="col-md-4"><label class="fw-semibold mb-1">Angkatan (Tahun Masuk)</label><input type="number" name="tahunAkademik" class="form-control form-control-sm" value="{{ date('Y') }}"></div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Jalur Masuk</label>
                    <select name="jalur" class="form-select form-select-sm select2-tambah">
                        @foreach($allJalurs as $j)<option value="{{ $j->kodeJalur }}">{{ $j->namaJalur }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Agama</label>
                    <select name="agama" class="form-select form-select-sm select2-tambah">
                        @foreach($allAgamas as $ag)<option value="{{ $ag->kodeAgama }}">{{ $ag->namaAgama }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Penerima KIP?</label>
                    <select name="kip" class="form-select form-select-sm select2-tambah">
                        <option value="Tidak">Tidak</option>
                        <option value="Ya">Ya</option>
                    </select>
                </div>
                <div class="col-md-4"><label class="fw-semibold mb-1">No. Handphone</label><input type="text" name="hp" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="fw-semibold mb-1">No. Telp Rumah</label><input type="text" name="telpon" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="fw-semibold mb-1">Email Aktif</label><input type="email" name="email" class="form-control form-control-sm"></div>
                <div class="col-md-6"><label class="fw-semibold mb-1">Kode Jurusan</label><input type="text" name="kodeJurusan" class="form-control form-control-sm" value="AK"></div>
                <div class="col-md-6"><label class="fw-semibold mb-1">Keterangan Tambahan</label><input type="text" name="keterangan" class="form-control form-control-sm"></div>
            </div>
            <div class="modal-footer bg-light"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-sm btn-primary">Simpan Data</button></div>
        </form>
    </div>
</div>

<!-- MODAL EDIT MAHASISWA -->
<div class="modal fade" id="modalEditMhs" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="" method="POST" id="formEditMhs" class="modal-content text-dark">
            @csrf
            @method('PUT')
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-pen me-2"></i>Edit Biodata Mahasiswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3" style="font-size: 13px;">
                
                <div class="col-md-6"><label class="fw-semibold mb-1">NPM <span class="text-danger">*</span></label><input type="text" name="npm" id="edit_npm" class="form-control form-control-sm" required></div>
                <div class="col-md-6"><label class="fw-semibold mb-1">No. Registrasi</label><input type="text" name="noRegistrasi" id="edit_noReg" class="form-control form-control-sm"></div>
                <div class="col-md-12"><label class="fw-semibold mb-1">Nama Lengkap <span class="text-danger">*</span></label><input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" required></div>
                
                <div class="col-md-6">
                    <label class="fw-semibold mb-1">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select name="jenisKelamin" id="edit_jk" class="form-select form-select-sm select2-edit" required>
                        <option value="L">Laki-Laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-1">Program Studi <span class="text-danger">*</span></label>
                    <select name="kodeProdi" id="edit_prodi" class="form-select form-select-sm select2-edit" required>
                        @foreach($prodis as $p)<option value="{{ $p->kodeProdi }}">{{ $p->namaProdi }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Program Kelas Waktu</label>
                    <select name="program" id="edit_program" class="form-select form-select-sm select2-edit">
                        <option value="">-- Pilih Program --</option>
                        @foreach($allPrograms as $prog)<option value="{{ $prog->kodeProgram }}">{{ $prog->namaProgram }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="fw-semibold mb-1">Kelas Awal</label><input type="text" name="kelas" id="edit_kelas" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="fw-semibold mb-1">Angkatan</label><input type="number" name="tahunAkademik" id="edit_ta" class="form-control form-control-sm"></div>
                
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Jalur Masuk</label>
                    <select name="jalur" id="edit_jalur" class="form-select form-select-sm select2-edit">
                        <option value="">-- Pilih Jalur --</option>
                        @foreach($allJalurs as $j)<option value="{{ $j->kodeJalur }}">{{ $j->namaJalur }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Agama</label>
                    <select name="agama" id="edit_agama" class="form-select form-select-sm select2-edit">
                        <option value="">-- Pilih Agama --</option>
                        @foreach($allAgamas as $ag)<option value="{{ $ag->kodeAgama }}">{{ $ag->namaAgama }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-1">Penerima KIP?</label>
                    <select name="kip" id="edit_kip" class="form-select form-select-sm select2-edit">
                        <option value="Tidak">Tidak</option>
                        <option value="Ya">Ya</option>
                    </select>
                </div>
                
                <div class="col-md-4"><label class="fw-semibold mb-1">No. Handphone</label><input type="text" name="hp" id="edit_hp" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="fw-semibold mb-1">No. Telp Rumah</label><input type="text" name="telpon" id="edit_telpon" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="fw-semibold mb-1">Email</label><input type="email" name="email" id="edit_email" class="form-control form-control-sm"></div>
                <div class="col-md-6"><label class="fw-semibold mb-1">Kode Jurusan</label><input type="text" name="kodeJurusan" id="edit_jurusan" class="form-control form-control-sm"></div>
                <div class="col-md-6"><label class="fw-semibold mb-1">Keterangan</label><input type="text" name="keterangan" id="edit_keterangan" class="form-control form-control-sm"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-dark">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORT MAHASISWA -->
<div class="modal fade" id="modalImportMhs" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalImportMhsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('mahasiswa.import') }}" method="POST" enctype="multipart/form-data" class="modal-content text-dark">
            @csrf
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-success" id="modalImportMhsLabel">
                    <i class="fa-solid fa-file-excel me-2"></i>Import Data Mahasiswa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start" style="font-size: 13px;">
                <div class="alert alert-info border-0 shadow-sm rounded-3 py-2 mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i> 
                    <strong>Petunjuk:</strong> Silakan klik tombol <strong>"Unduh Template Excel"</strong> terlebih dahulu untuk mendapatkan format kolom yang pas. Isilah data mahasiswa baru di file tersebut tanpa mengubah nama header kolomnya.
                </div>
                
                <div class="mb-2">
                    <label class="form-label fw-bold">Pilih Berkas Excel (.xlsx) <span class="text-danger">*</span></label>
                    <input type="file" name="file_excel" class="form-control form-control-sm" accept=".xlsx" required>
                    <div class="form-text small text-muted">Ukuran file maksimal yang diizinkan adalah 4 MB.</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success fw-semibold">
                    <i class="fa-solid fa-upload me-1"></i> Mulai Proses Import
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Select2 JS Lokal -->
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // 1. Inisialisasi Select2 Filter di Halaman Utama
            $('.select2-filter').select2({
                width: '100%'
            });

            // 2. Inisialisasi Select2 Modal Tambah
            $('.select2-tambah').select2({
                dropdownParent: $('#modalTambahMhs'),
                width: '100%'
            });

            // 3. Inisialisasi Select2 Modal Edit
            $('.select2-edit').select2({
                dropdownParent: $('#modalEditMhs'),
                width: '100%'
            });

            // SCRIPT POPULASI DATA MODAL EDIT (Trigger Update pada Select2)
            $('.btn-edit-mhs').on('click', function() {
                const id = $(this).attr('data-id');
                const baseUrl = window.location.origin;
                $('#formEditMhs').attr('action', `${baseUrl}/admin/mahasiswa/update/${id}`);
                
                $('#edit_npm').val($(this).attr('data-npm') || '');
                $('#edit_noReg').val($(this).attr('data-no_reg') || '');
                $('#edit_nama').val($(this).attr('data-nama') || '');
                
                // Set value & trigger change agar Select2 memperbarui tampilannya
                $('#edit_jk').val($(this).attr('data-jk') || 'L').trigger('change');
                $('#edit_prodi').val($(this).attr('data-prodi') || '').trigger('change');
                $('#edit_program').val($(this).attr('data-program') || '').trigger('change');
                
                $('#edit_kelas').val($(this).attr('data-kelas') || '');
                $('#edit_ta').val($(this).attr('data-ta') || '');
                
                $('#edit_jalur').val($(this).attr('data-jalur') || '').trigger('change');
                $('#edit_agama').val($(this).attr('data-agama') || '').trigger('change');
                
                const kipValue = $(this).attr('data-kip');
                $('#edit_kip').val((kipValue === 'Ya' || kipValue === 'Y') ? 'Ya' : 'Tidak').trigger('change');
                
                $('#edit_hp').val($(this).attr('data-hp') || '');
                $('#edit_telpon').val($(this).attr('data-telpon') || '');
                $('#edit_email').val($(this).attr('data-email') || '');
                $('#edit_jurusan').val($(this).attr('data-jurusan') || 'AK');
                $('#edit_keterangan').val($(this).attr('data-keterangan') || '');
            });

            // SCRIPT SWEETALERT2 UNTUK HAPUS DATA
            $('.btn-delete-mhs').on('click', function() {
                const namaMhs = $(this).attr('data-nama');
                const form = $(this).closest('.form-delete');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Data mahasiswa "${namaMhs}" akan dihapus permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection