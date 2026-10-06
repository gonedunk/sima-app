@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title fw-bold text-dark mb-4">⚙️ Konfigurasi Waktu Kerja</h5>

            <!-- NAV TABS -->
            <ul class="nav nav-tabs mb-4 border-bottom-0" id="konfigurasiTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary tab-indikator {{ !in_array(request('tab'), ['libur-lembur', 'bulan-puasa']) ? 'active' : '' }}" 
                            id="jam-kerja-tab" data-bs-toggle="tab" data-bs-target="#jam-kerja-panel" type="button" role="tab" aria-controls="jam-kerja-panel" aria-selected="{{ !in_array(request('tab'), ['libur-lembur', 'bulan-puasa']) ? 'true' : 'false' }}">
                        🕒 Jam Kerja Wajib
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary tab-indikator {{ request('tab') == 'libur-lembur' ? 'active' : '' }}" 
                            id="libur-lembur-tab" data-bs-toggle="tab" data-bs-target="#libur-lembur-panel" type="button" role="tab" aria-controls="libur-lembur-panel" aria-selected="{{ request('tab') == 'libur-lembur' ? 'true' : 'false' }}">
                        📅 Tanggal Libur
                    </button>
                </li>
                <!-- NAV TAB BARU: BULAN PUASA -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary tab-indikator {{ request('tab') == 'bulan-puasa' ? 'active' : '' }}" 
                            id="bulan-puasa-tab" data-bs-toggle="tab" data-bs-target="#bulan-puasa-panel" type="button" role="tab" aria-controls="bulan-puasa-panel" aria-selected="{{ request('tab') == 'bulan-puasa' ? 'true' : 'false' }}">
                        🌙 Jadwal Bulan Puasa
                    </button>
                </li>
            </ul>

            <!-- TAB PANELS -->
            <div class="tab-content" id="konfigurasiTabContent">
                
                <!-- PANEL 1: JAM KERJA WAJIB -->
                <div class="tab-pane fade {{ !in_array(request('tab'), ['libur-lembur', 'bulan-puasa']) ? 'show active' : '' }}" id="jam-kerja-panel" role="tabpanel" aria-labelledby="jam-kerja-tab">
                    <div class="row g-4">
                        <!-- Form Tambah/Ubah Jam Kerja -->
                        <div class="col-lg-4">
                            <div class="card bg-light border-0">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-3" id="form-title-jam">➕ Tambah Jam Kerja Wajib</h6>
                                    
                                    <form action="{{ route('jam-kerja.storeJamWajib') }}" method="POST" id="formJamKerja">
                                        @csrf
                                        <input type="hidden" name="_method" id="form-method-jam" value="POST">

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-muted">Kode Bulan</label>
                                            <input type="text" name="kode_bulan" id="input-kode-bulan" class="form-control text-uppercase" placeholder="Contoh: 2026-A atau JULI" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-muted">Jam Datang Wajib</label>
                                            <input type="time" name="jam_datang" id="input-jam-datang" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-muted">Jam Pulang Wajib</label>
                                            <input type="time" name="jam_pulang" id="input-jam-pulang" class="form-control" required>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-muted">Jam Lembur Maksimal</label>
                                            <input type="time" name="jam_lembur_maks" id="input-jam-lembur-maks" class="form-control" required>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" id="btn-submit-jam" class="btn btn-primary fw-semibold py-2">
                                                <i class="fas fa-check-circle me-1"></i> Simpan Data
                                            </button>
                                            <button type="button" id="btn-batal-jam" class="btn btn-outline-secondary fw-semibold py-2 d-none" onclick="resetFormJam()">
                                                Batal Ubah
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Jam Kerja Wajib -->
                        <div class="col-lg-8">
                            <h6 class="fw-bold text-dark mb-3">📋 Daftar Aturan Jam Kerja Wajib</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border-top">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 8%">No</th>
                                            <th style="width: 22%">Kode Bulan</th>
                                            <th>Jam Datang</th>
                                            <th>Jam Pulang</th>
                                            <th>Lembur Maks</th>
                                            <th style="width: 18%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($dataJamWajib as $key => $jw)
                                        <tr>
                                            <td class="text-muted fw-semibold">{{ $key + 1 }}</td>
                                            <td class="fw-bold text-primary text-uppercase">{{ $jw->kodeBulan }}</td>
                                            <td class="fw-semibold text-dark">{{ $jw->jamDatangWajib }}</td>
                                            <td class="fw-semibold text-dark">{{ $jw->jamPulangWajib }}</td>
                                            <td class="fw-semibold text-danger">{{ $jw->jamLemburMaks }}</td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="editDataJam('{{ $jw->id }}', '{{ $jw->kodeBulan }}', '{{ $jw->jamDatangWajib }}', '{{ $jw->jamPulangWajib }}', '{{ $jw->jamLemburMaks }}')" 
                                                            title="Ubah Data">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="hapusDataJam('{{ $jw->id }}', '{{ $jw->kodeBulan }}')" 
                                                            title="Hapus Data">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                <form id="delete-form-jam-{{ $jw->id }}" action="{{ route('jam-kerja.destroyJamWajib', $jw->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="far fa-folder-open fs-3 d-block mb-2"></i> Belum ada data jam kerja wajib.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL 2: TANGGAL LIBUR -->
                <div class="tab-pane fade {{ request('tab') == 'libur-lembur' ? 'show active' : '' }}" id="libur-lembur-panel" role="tabpanel" aria-labelledby="libur-lembur-tab">
                    <div class="row g-4">
                        <!-- Form Tambah/Ubah Tanggal Libur -->
                        <div class="col-lg-4">
                            <div class="card bg-light border-0">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-3" id="form-title-libur">➕ Tambah Tanggal Libur</h6>
                                    
                                    <form action="{{ route('jam-kerja.storeLibur') }}" method="POST" id="formLiburLembur">
                                        @csrf
                                        <input type="hidden" name="_method" id="form-method-libur" value="POST">

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-muted">Pilih Tanggal</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="far fa-calendar-alt"></i></span>
                                                <input type="date" name="tanggal" id="input-tanggal-libur" class="form-control border-start-0 bg-white" required>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-muted">Keterangan Libur / Acara</label>
                                            <textarea name="keterangan" id="input-keterangan" rows="3" class="form-control" placeholder="Contoh: Libur Idul Fitri / Libur Tahun Baru" required></textarea>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" id="btn-submit-libur" class="btn btn-primary fw-semibold py-2">
                                                <i class="fas fa-check-circle me-1"></i> Simpan Data
                                            </button>
                                            <button type="button" id="btn-batal-libur" class="btn btn-outline-secondary fw-semibold py-2 d-none" onclick="resetFormLibur()">
                                                Batal Ubah
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Tampil Data Libur -->
                        <div class="col-lg-8">
                            <h6 class="fw-bold text-dark mb-3">📋 Daftar Libur Khusus</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border-top">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 10%">No</th>
                                            <th style="width: 25%">Tanggal</th>
                                            <th>Keterangan</th>
                                            <th style="width: 20%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($dataLibur as $key => $item)
                                        <tr>
                                            <td class="text-muted fw-semibold">{{ $key + 1 }}</td>
                                            <td class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}</td>
                                            <td>
                                                <span class="badge bg-danger me-1">Libur</span>
                                                <span class="text-dark align-middle">{{ $item->keterangan }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="editDataLibur('{{ $item->id }}', '{{ $item->tanggal }}', '{{ addslashes($item->keterangan) }}')" 
                                                            title="Ubah Data">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="hapusDataLibur('{{ $item->id }}', '{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}')" 
                                                            title="Hapus Data">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                <form id="delete-form-libur-{{ $item->id }}" action="{{ route('jam-kerja.destroyLibur', $item->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="far fa-folder-open fs-3 d-block mb-2"></i> Belum ada data tanggal libur.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL 3: OPERASIONAL RENTANG BULAN PUASA -->
                <div class="tab-pane fade {{ request('tab') == 'bulan-puasa' ? 'show active' : '' }}" id="bulan-puasa-panel" role="tabpanel" aria-labelledby="bulan-puasa-tab">
                    <div class="row g-4">
                        <!-- Form Tambah/Ubah Rentang Bulan Puasa -->
                        <div class="col-lg-4">
                            <div class="card bg-light border-0">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-3" id="form-title-puasa">➕ Tambah Jadwal Bulan Puasa</h6>
                                    
                                    <form action="{{ route('jam-kerja.storeBulanPuasa') }}" method="POST" id="formBulanPuasa">
                                        @csrf
                                        <input type="hidden" name="_method" id="form-method-puasa" value="POST">

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-muted">Dari Tanggal</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="far fa-calendar-alt"></i></span>
                                                <input type="date" name="dari_tanggal" id="input-dari-tanggal" class="form-control border-start-0 bg-white" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-muted">Sampai Tanggal</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="far fa-calendar-check"></i></span>
                                                <input type="date" name="sampai_tanggal" id="input-sampai-tanggal" class="form-control border-start-0 bg-white" required>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-muted">Keterangan / Tahun Hijriah</label>
                                            <textarea name="keterangan" id="input-keterangan-puasa" rows="3" class="form-control" placeholder="Contoh: Bulan Puasa Ramadhan 1447 H" required></textarea>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" id="btn-submit-puasa" class="btn btn-primary fw-semibold py-2">
                                                <i class="fas fa-check-circle me-1"></i> Simpan Data
                                            </button>
                                            <button type="button" id="btn-batal-puasa" class="btn btn-outline-secondary fw-semibold py-2 d-none" onclick="resetFormPuasa()">
                                                Batal Ubah
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Tampil Data Bulan Puasa -->
                        <div class="col-lg-8">
                            <h6 class="fw-bold text-dark mb-3">📋 Daftar Periode Bulan Puasa</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border-top">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 8%">No</th>
                                            <th style="width: 42%">Rentang Tanggal</th>
                                            <th>Keterangan</th>
                                            <th style="width: 18%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($dataPuasa as $key => $puasa)
                                        <tr>
                                            <td class="text-muted fw-semibold">{{ $key + 1 }}</td>
                                            <td class="text-dark">
                                                <span class="fw-bold text-success">{{ \Carbon\Carbon::parse($puasa->dariTanggal)->translatedFormat('d M Y') }}</span> 
                                                <span class="text-muted px-1">s.d</span> 
                                                <span class="fw-bold text-success">{{ \Carbon\Carbon::parse($puasa->sampaiTanggal)->translatedFormat('d M Y') }}</span>
                                            </td>
                                            <td class="fw-semibold text-secondary">
                                                <i class="fas fa-moon text-warning me-1"></i> {{ $puasa->keterangan }}
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="editDataPuasa('{{ $puasa->id }}', '{{ $puasa->dariTanggal }}', '{{ $puasa->sampaiTanggal }}', '{{ addslashes($puasa->keterangan) }}')" 
                                                            title="Ubah Data">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="hapusDataPuasa('{{ $puasa->id }}', '{{ \Carbon\Carbon::parse($puasa->dariTanggal)->translatedFormat('d M Y') }}')" 
                                                            title="Hapus Data">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                <form id="delete-form-puasa-{{ $puasa->id }}" action="{{ route('jam-kerja.destroyBulanPuasa', $puasa->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="far fa-folder-open fs-3 d-block mb-2"></i> Belum ada data aturan periode bulan puasa.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
<style>
    .nav-tabs .nav-link {
        border: none !important;
        background: transparent !important;
        padding: 12px 20px;
        transition: all 0.2s ease-in-out;
    }
    .nav-tabs .nav-link:hover {
        color: #0d6efd !important;
    }
</style>
@endsection

@section('scripts')
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. SET INDIKATOR LINE ACTIVE TAB ---
        const tabs = document.querySelectorAll('.tab-indikator');
        
        function updateTabBorder() {
            tabs.forEach(tab => {
                if (tab.classList.contains('active')) {
                    tab.classList.add('border-bottom', 'border-primary', 'border-3');
                    tab.classList.remove('text-secondary');
                    tab.classList.add('text-primary');
                } else {
                    tab.classList.remove('border-bottom', 'border-primary', 'border-3');
                    tab.classList.remove('text-primary');
                    tab.classList.add('text-secondary');
                }
            });
        }

        updateTabBorder();

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                setTimeout(updateTabBorder, 60);
            });
        });

        // --- 3. ALERTER NOTIFIKASI ---
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                showConfirmButton: false,
                timer: 2000
            });
        @endif

        // --- 4. ENGINE FORCE UPPERCASE PADA KODE BULAN ---
        const inputKodeBulan = document.getElementById('input-kode-bulan');
        if(inputKodeBulan) {
            inputKodeBulan.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });

    // =========================================================================
    // JS ENGINE: TAB 1 - JAM KERJA WAJIB (TIDAK DIGANGGU GUGAT)
    // =========================================================================
    function editDataJam(id, kodeBulan, jamDatang, jamPulang, jamLemburMaks) {
        document.getElementById('form-title-jam').innerHTML = "📝 Ubah Jam Kerja Wajib";
        document.getElementById('form-method-jam').value = "PUT";
        
        let updateUrl = "{{ route('jam-kerja.updateJamWajib', ':id') }}";
        updateUrl = updateUrl.replace(':id', id);
        document.getElementById('formJamKerja').action = updateUrl;

        document.getElementById('input-kode-bulan').value = kodeBulan.toUpperCase();
        document.getElementById('input-jam-datang').value = jamDatang.substring(0, 5);
        document.getElementById('input-jam-pulang').value = jamPulang.substring(0, 5);
        document.getElementById('input-jam-lembur-maks').value = jamLemburMaks.substring(0, 5);

        document.getElementById('btn-batal-jam').classList.remove('d-none');
        document.getElementById('btn-submit-jam').className = "btn btn-warning text-white fw-semibold py-2";
        document.getElementById('btn-submit-jam').innerHTML = "<i class='fas fa-edit me-1'></i> Perbarui Data";
        
        document.getElementById('formJamKerja').scrollIntoView({ behavior: 'smooth' });
    }

    function resetFormJam() {
        document.getElementById('form-title-jam').innerHTML = "➕ Tambah Jam Kerja Wajib";
        document.getElementById('form-method-jam').value = "POST";
        document.getElementById('formJamKerja').action = "{{ route('jam-kerja.storeJamWajib') }}";
        
        document.getElementById('input-kode-bulan').value = "";
        document.getElementById('input-jam-datang').value = "";
        document.getElementById('input-jam-pulang').value = "";
        document.getElementById('input-jam-lembur-maks').value = "";

        document.getElementById('btn-batal-jam').classList.add('d-none');
        document.getElementById('btn-submit-jam').className = "btn btn-primary fw-semibold py-2";
        document.getElementById('btn-submit-jam').innerHTML = "<i class='fas fa-check-circle me-1'></i> Simpan Data";
    }

    function hapusDataJam(id, kodeBulan) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data jam kerja wajib bulan " + kodeBulan.toUpperCase() + " akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-jam-' + id).submit();
            }
        });
    }

    // =========================================================================
    // JS ENGINE: TAB 2 - TANGGAL LIBUR
    // =========================================================================
    function editDataLibur(id, tanggal, keterangan) {
        document.getElementById('form-title-libur').innerHTML = "📝 Ubah Data Tanggal Libur";
        document.getElementById('form-method-libur').value = "PUT";
        
        let updateUrl = "{{ route('jam-kerja.updateLibur', ':id') }}";
        updateUrl = updateUrl.replace(':id', id);
        document.getElementById('formLiburLembur').action = updateUrl;

        document.getElementById('input-keterangan').value = keterangan;
        document.getElementById('input-tanggal-libur').value = tanggal;

        document.getElementById('btn-batal-libur').classList.remove('d-none');
        document.getElementById('btn-submit-libur').className = "btn btn-warning text-white fw-semibold py-2";
        document.getElementById('btn-submit-libur').innerHTML = "<i class='fas fa-edit me-1'></i> Perbarui Data";
        
        document.getElementById('formLiburLembur').scrollIntoView({ behavior: 'smooth' });
    }

    function resetFormLibur() {
        document.getElementById('form-title-libur').innerHTML = "➕ Tambah Tanggal Libur";
        document.getElementById('form-method-libur').value = "POST";
        document.getElementById('formLiburLembur').action = "{{ route('jam-kerja.storeLibur') }}";
        
        document.getElementById('input-keterangan').value = "";
        document.getElementById('input-tanggal-libur').value = "";

        document.getElementById('btn-batal-libur').classList.add('d-none');
        document.getElementById('btn-submit-libur').className = "btn btn-primary fw-semibold py-2";
        document.getElementById('btn-submit-libur').innerHTML = "<i class='fas fa-check-circle me-1'></i> Simpan Data";
    }

    function hapusDataLibur(id, labelTanggal) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data libur tanggal " + labelTanggal + " akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-libur-' + id).submit();
            }
        });
    }

    // =========================================================================
    // JS ENGINE BARU: TAB 3 - BULAN PUASA (tbbulanpuasa)
    // =========================================================================
    function editDataPuasa(id, dariTanggal, sampaiTanggal, keterangan) {
        document.getElementById('form-title-puasa').innerHTML = "📝 Ubah Jadwal Bulan Puasa";
        document.getElementById('form-method-puasa').value = "PUT";
        
        let updateUrl = "{{ route('jam-kerja.updateBulanPuasa', ':id') }}";
        updateUrl = updateUrl.replace(':id', id);
        document.getElementById('formBulanPuasa').action = updateUrl;

        document.getElementById('input-dari-tanggal').value = dariTanggal;
        document.getElementById('input-sampai-tanggal').value = sampaiTanggal;
        document.getElementById('input-keterangan-puasa').value = keterangan;

        document.getElementById('btn-batal-puasa').classList.remove('d-none');
        document.getElementById('btn-submit-puasa').className = "btn btn-warning text-white fw-semibold py-2";
        document.getElementById('btn-submit-puasa').innerHTML = "<i class='fas fa-edit me-1'></i> Perbarui Data";
        
        document.getElementById('formBulanPuasa').scrollIntoView({ behavior: 'smooth' });
    }

    function resetFormPuasa() {
        document.getElementById('form-title-puasa').innerHTML = "➕ Tambah Jadwal Bulan Puasa";
        document.getElementById('form-method-puasa').value = "POST";
        document.getElementById('formBulanPuasa').action = "{{ route('jam-kerja.storeBulanPuasa') }}";
        
        document.getElementById('input-dari-tanggal').value = "";
        document.getElementById('input-sampai-tanggal').value = "";
        document.getElementById('input-keterangan-puasa').value = "";

        document.getElementById('btn-batal-puasa').classList.add('d-none');
        document.getElementById('btn-submit-puasa').className = "btn btn-primary fw-semibold py-2";
        document.getElementById('btn-submit-puasa').innerHTML = "<i class='fas fa-check-circle me-1'></i> Simpan Data";
    }

    function hapusDataPuasa(id, labelTanggalMulai) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data rentang bulan puasa mulai " + labelTanggalMulai + " akan dihapus beserta aturan operasionalnya!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-puasa-' + id).submit();
            }
        });
    }
</script>
@endsection