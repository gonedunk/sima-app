@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="fw-bold text-primary mb-0">Plotting Kelas Mahasiswa</h3>
                @if(auth()->user()->level === 'superadmin')
                    <span class="badge bg-danger">Superadmin (Semua Akses)</span>
                @else
                    <span class="badge bg-primary">Admin Jurusan</span>
                @endif
            </div>
            <p class="text-muted small mb-0">Manajemen pembagian kelas, semester berjalan, dan status keaktifan mahasiswa.</p>
        </div>
        <div class="d-flex align-items-center gap-2">

            <!-- 1. Tombol Cetak Serah Terima per Kelas (mPDF) -->
            @if($kelasMahasiswas->count() > 0)
                <a href="{{ route('kelas-mahasiswa.cetak-serah-terima-kelas', $kelasMahasiswas->first()->id) }}" 
                   target="_blank" 
                   class="btn btn-sm btn-danger fw-semibold shadow-sm px-3 py-2"
                   title="Cetak Bukti Serah Terima Ijazah untuk Seluruh Kelas Ini">
                    <i class="fa-solid fa-print me-2"></i>Cetak Serah Terima Kelas
                </a>
            @endif

            <!-- 2. Tombol Mengarah ke Form Cetak Universal -->
            <a href="{{ route('rekap-universal.cetak') }}" class="btn btn-sm btn-outline-danger fw-semibold shadow-sm px-3 py-2">
                <i class="fa-solid fa-file-pdf me-2"></i>Cetak Laporan
            </a>

            <!-- 3. Tombol Sync Data -->
            <form id="formSyncMahasiswa" action="{{ route('kelas-mahasiswa.sync') }}" method="POST" class="d-inline">
                @csrf
                <button type="button" onclick="konfirmasiSync()" class="btn btn-sm btn-primary fw-semibold shadow-sm px-3 py-2">
                    <i class="fa-solid fa-rotate me-2"></i>Tarik Data Mahasiswa Baru
                </button>
            </form>
        </div>
    </div>

    <!-- Card Filter Pencarian -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form id="formFilterPencarian" action="{{ route('kelas-mahasiswa.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="elSearch" name="search" class="form-control border-start-0" placeholder="Cari NPM, Nama, atau Kelas..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="elProdi" name="prodi" class="form-select form-select-sm">
                        <option value="">-- Semua Program --</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->kodeProdi }}" {{ request('prodi') == $p->kodeProdi ? 'selected' : '' }}>
                                {{ $p->namaProdi }} {{ isset($p->namaJurusan) ? '('.$p->namaJurusan.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="elTa" name="ta" class="form-select form-select-sm" style="width: 100%;">
                        <option value="">-- Semua TA --</option>
                        @foreach($tahunAkademiks as $ta)
                            @php
                                $valTa = strtolower($ta->semesterAkademik);
                                $isGanjil = (strpos($valTa, 'ganjil') !== false || strpos($valTa, '1') !== false || strpos($valTa, 'gasal') !== false);
                                $teksSemester = $isGanjil ? 'Ganjil' : 'Genap';
                            @endphp
                            <option value="{{ $ta->tahunAkademik }}" {{ $taAktif == $ta->tahunAkademik ? 'selected' : '' }}>
                                {{ $ta->tahunAkademik }} ({{ $teksSemester }}) {{ ($setting->ta_aktif ?? '') == $ta->tahunAkademik ? '[Aktif]' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Induk Aksi Massal -->
    <form id="formAksiMassal" action="{{ route('kelas-mahasiswa.promosi-massal') }}" method="POST">
        @csrf
        
        <input type="hidden" name="filter_search" id="hidSearch" value="">
        <input type="hidden" name="filter_prodi" id="hidProdi" value="">
        <input type="hidden" name="filter_ta" id="hidTa" value="">
        <input type="hidden" name="pilih_semua_filter" id="inputPilihSemuaFilter" value="0">

        <!-- Bar Notifikasi Aksi Massal -->
        <div class="card border-0 bg-light-subtle shadow-sm rounded-3 mb-3 p-3 border-start border-primary border-3 d-none" id="boxAksiMassal">
            <div class="row align-items-center g-3">
                <div class="col-md-3">
                    <span class="fw-bold text-dark d-block small mb-1" id="textTerpilih">0 Mahasiswa Aktif Terpilih</span>
                    <span class="text-muted small d-block mb-1" id="infoPaginasi">Pilih aksi kenaikan tingkat atau penonaktifan status massal.</span>
                    
                    <div id="btnPilihSemuaHalamanWrapper" class="d-none mt-1">
                        <button type="button" id="btnPilihSemuaHalaman" class="btn btn-xs btn-link text-primary p-0 fw-bold small text-decoration-none">
                            <i class="fa-solid fa-square-check me-1"></i>Pilih seluruh <strong>{{ $kelasMahasiswas->total() }}</strong> mahasiswa dalam filter ini (Semua Halaman)
                        </button>
                        <button type="button" id="btnBatalkanSemuaHalaman" class="btn btn-xs btn-link text-danger p-0 fw-bold small text-decoration-none d-none">
                            <i class="fa-solid fa-square-minus me-1"></i>Batalkan pilihan semua halaman
                        </button>
                    </div>
                </div>

                <!-- Bagian Kenaikan Kelas -->
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white fw-bold small">Target TA Baru</span>
                        @php
                            $taSistem = $setting->ta_aktif ?? '';
                            $taBerikutnya = '';
                            if (preg_match('/(\d{4})\/(\d{4})/', $taSistem, $matches)) {
                                $taBerikutnya = ((int)$matches[1] + 1) . '/' . ((int)$matches[2] + 1);
                            } else {
                                $taBerikutnya = ((int)date('Y')) . '/' . ((int)date('Y') + 1);
                            }
                        @endphp
                        <select name="tahunAkademikBaru" id="targetTaMassal" class="form-select" style="width: 45%;">
                            @foreach($tahunAkademiks as $ta)
                                @php
                                    $valTa = strtolower($ta->semesterAkademik);
                                    $isGanjil = (strpos($valTa, 'ganjil') !== false || strpos($valTa, '1') !== false || strpos($valTa, 'gasal') !== false);
                                    $teksSemester = $isGanjil ? 'Ganjil' : 'Genap';
                                @endphp
                                <option value="{{ $ta->tahunAkademik }}" {{ $taBerikutnya == $ta->tahunAkademik ? 'selected' : '' }}>
                                    {{ $ta->tahunAkademik }} ({{ $teksSemester }})
                                </option>
                            @endforeach
                        </select>
                        <button type="button" onclick="eksekusiKenaikanMassal()" class="btn btn-sm btn-success fw-bold px-3">
                            <i class="fa-solid fa-graduation-cap me-1"></i> Naik Kelas
                        </button>
                    </div>
                </div>

                <!-- Bagian Kelulusan / Non-Aktif Massal -->
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <select name="statusKeterangan" id="statusKeteranganMassal" class="form-select" style="width: 50%;">
                            <option value="Lulus" selected>Lulus (Tamat)</option>
                            <option value="DO">DO (Drop Out)</option>
                            <option value="Undur Diri">Undur Diri</option>
                            <option value="Mengulang 1 Tahun">Mengulang 1 Tahun</option>
                        </select>
                        <button type="button" onclick="eksekusiLulusMassal()" class="btn btn-sm btn-warning text-white fw-bold px-3">
                            <i class="fa-solid fa-user-slash me-1"></i> Set Status
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL UTAMA: MAHASISWA AKTIF -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light text-secondary fw-semibold">
                            <tr>
                                <th width="4%" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAllMaster">
                                </th>
                                <th width="4%" class="text-center">No</th>
                                <th width="11%">NPM</th>
                                <th width="20%">Nama Lengkap</th>
                                <th width="8%" class="text-center">Kelas</th>
                                <th width="6%" class="text-center">Sem</th>
                                <th width="15%">Program / Jurusan</th>
                                <th width="10%" class="text-center">TA</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="12%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kelasMahasiswas as $index => $km)
                            @php
                                // Logika Status & Semester Akhir
                                $statusKet = strtolower(trim($km->statusKeterangan ?? ''));
                                $isLulus   = (strpos($statusKet, 'lulus') !== false);
                                
                                $namaProdi = strtoupper($km->namaProdi ?? $km->prodi ?? '');
                                $semester  = (int) ($km->semester ?? 0);
                                $isSemesterAkhir = false;

                                // Deteksi D4/S1 vs D3
                                $isD4 = (
                                    strpos($namaProdi, 'D4') !== false || 
                                    strpos($namaProdi, 'D-IV') !== false || 
                                    strpos($namaProdi, 'STR') !== false ||
                                    strpos($namaProdi, 'SARJANA TERAPAN') !== false ||
                                    strpos($namaProdi, 'SEKTOR PUBLIK') !== false
                                );

                                if (isset($km->is_semester_akhir)) {
                                    $isSemesterAkhir = (bool) $km->is_semester_akhir;
                                } else {
                                    $isSemesterAkhir = $isD4 ? ($semester >= 8) : ($semester >= 6);
                                }

                                $bisaCetak = $isLulus && $isSemesterAkhir;

                                $pesanDisabled = '';
                                if (!$bisaCetak) {
                                    if (!$isLulus && !$isSemesterAkhir) {
                                        $pesanDisabled = 'Belum Lulus & Belum Semester Akhir ('.($isD4 ? 'Sem 8' : 'Sem 6').')';
                                    } elseif (!$isLulus) {
                                        $pesanDisabled = 'Status keaktifan mahasiswa belum "Lulus"';
                                    } else {
                                        $pesanDisabled = $isD4 
                                            ? 'Mahasiswa D4 belum mencapai semester akhir (Minimal Sem 8)' 
                                            : 'Mahasiswa D3 belum mencapai semester akhir (Minimal Sem 6)';
                                    }
                                }
                            @endphp
                            <tr class="{{ $km->keterangan != 'A' ? 'table-light text-muted' : '' }}">
                                <td class="text-center">
                                    @if($km->keterangan == 'A')
                                        <input type="checkbox" name="ids[]" value="{{ $km->id }}" class="form-check-input sub-chk-mhs">
                                    @else
                                        <input type="checkbox" class="form-check-input bg-secondary border-0" disabled title="Mahasiswa Non-Aktif tidak dapat diikutkan aksi massal">
                                        <i class="fa-solid fa-lock text-muted small d-block" style="font-size: 10px;"></i>
                                    @endif
                                </td>
                                <td class="text-center text-muted">{{ $kelasMahasiswas->firstItem() + $index }}</td>
                                <td class="fw-bold {{ $km->keterangan == 'A' ? 'text-dark' : 'text-secondary text-decoration-line-through' }}">{{ $km->npm }}</td>
                                <td>{{ $km->nama }}</td>
                                <td class="text-center fw-semibold text-primary">{{ $km->kelas }}</td>
                                <td class="text-center fw-bold">{{ $km->semester }}</td>
                                <td>
                                    <span class="d-block text-truncate fw-semibold" style="max-width: 160px;" title="{{ $km->namaProdi ?? $km->prodi }}">
                                        {{ $km->namaProdi ?? $km->prodi }}
                                    </span>
                                    <small class="text-muted d-block text-truncate" style="max-width: 160px;" title="{{ $km->namaJurusan ?? $km->jurusan }}">
                                        {{ $km->namaJurusan ?? $km->jurusan }}
                                    </small>
                                </td>
                                <td class="text-center text-muted">{{ $km->tahunAkademik }}</td>
                                <td class="text-center">
                                    @if($km->keterangan == 'A')
                                        <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">Aktif</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill">Non-Aktif ({{ $km->statusKeterangan }})</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- LINK CETAK BUKTI SERAH TERIMA INDIVIDU (DomPDF) -->
                                        @if($bisaCetak)
                                            <a href="{{ route('kelas-mahasiswa.cetak-serah-terima-id', $km->id) }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-danger py-1 px-2" 
                                               title="Cetak Bukti Serah Terima Ijazah & Transkrip">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" disabled style="cursor: not-allowed; opacity: 0.4;" title="{{ $pesanDisabled }}">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </button>
                                        @endif

                                        <!-- Tombol Edit & Hapus -->
                                        <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalEditKelas{{ $km->id }}" title="Edit Plotting">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" onclick="konfirmasiHapusLangsung('{{ $km->id }}', '{{ $km->nama }}')" class="btn btn-sm btn-outline-danger py-1 px-2" title="Hapus Data">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open d-block fs-3 mb-2"></i> Belum ada data mahasiswa pada filter ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form> 

    <!-- Pagination Tabel Utama -->
    <div class="d-flex justify-content-between align-items-center mt-3 mb-5">
        <div class="small text-muted">
            Menampilkan {{ $kelasMahasiswas->firstItem() ?? 0 }} sampai {{ $kelasMahasiswas->lastItem() ?? 0 }} dari total {{ $kelasMahasiswas->total() }} data
        </div>
        <div>
            {{ $kelasMahasiswas->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <hr class="my-5 border-2 text-muted opacity-25">

    <!-- TABEL KEDUA: MAHASISWA NON-AKTIF / RIWAYAT TA SEBELUMNYA -->
    <div class="mb-3">
        <h4 class="fw-bold text-danger mb-1"><i class="fa-solid fa-user-slash me-2"></i>Data Mahasiswa Non-Aktif & Log TA Sebelumnya</h4>
        <p class="text-muted small mb-0">Daftar rekonsiliasi data dari tabel <code>tbkelasmahasiswa</code> yang berstatus Non-Aktif (NA) atau riwayat tertinggal.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-dark text-white fw-semibold">
                        <tr>
                            <th width="4%" class="text-center">No</th>
                            <th width="10%">NPM</th>
                            <th width="18%">Nama Lengkap</th>
                            <th width="7%" class="text-center">Kelas</th>
                            <th width="7%" class="text-center">Smstr</th>
                            <th width="12%">Program</th>
                            <th width="12%">Jurusan</th>
                            <th width="6%" class="text-center">Masuk</th>
                            <th width="10%" class="text-center">TA</th>
                            <th width="14%">Keterangan Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $noNa = 1; @endphp
                        @forelse($mahasiswaNonAktif as $na)
                        <tr class="table-danger-subtle text-dark">
                            <td class="text-center text-muted fw-bold">{{ $noNa++ }}</td>
                            <td class="fw-bold text-danger text-decoration-line-through">{{ $na->npm }}</td>
                            <td class="fw-semibold text-dark">{{ $na->nama }}</td>
                            <td class="text-center fw-bold">{{ $na->kelas }}</td>
                            <td class="text-center font-monospace">{{ $na->semester }}</td>
                            <td><span class="small d-block text-truncate" style="max-width: 120px;" title="{{ $na->namaProdi ?? $na->prodi }}">{{ $na->namaProdi ?? $na->prodi }}</span></td>
                            <td><span class="small d-block text-truncate" style="max-width: 120px;" title="{{ $na->namaJurusan ?? $na->jurusan }}">{{ $na->namaJurusan ?? $na->jurusan }}</span></td>
                            <td class="text-center text-muted">{{ $na->tahunMasuk }}</td>
                            <td class="text-center small fw-semibold text-secondary">{{ $na->tahunAkademik }}</td>
                            <td>
                                <span class="badge bg-danger text-white rounded-1 px-2 py-1 small">
                                    {{ $na->keterangan }} - {{ $na->statusKeterangan ?? 'Tanpa Alasan' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-xs btn-warning py-1 px-2 text-white" data-bs-toggle="modal" data-bs-target="#modalEditKelasNa{{ $na->id }}" title="Edit Status">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted bg-light">
                                <i class="fa-solid fa-circle-check d-block fs-4 text-success mb-2"></i> Tidak ditemukan mahasiswa non-aktif pada log TA berjalan ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Form Satuan untuk Hapus Plotting -->
<form id="formHapusSatuan" action="" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<!-- Modal Edit Kelas -->
@foreach($kelasMahasiswas as $km)
<div class="modal fade" id="modalEditKelas{{ $km->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditKelas{{ $km->id }}" action="{{ route('kelas-mahasiswa.update', $km->id) }}" method="POST" class="modal-content text-dark">
            @csrf
            @method('PUT')
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-warning">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Plotting Kelas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body text-start">
                <input type="hidden" name="npm" value="{{ $km->npm }}">
                <input type="hidden" name="nama" value="{{ $km->nama }}">
                <input type="hidden" name="prodi" value="{{ $km->prodi }}">
                <input type="hidden" name="jurusan" value="{{ $km->jurusan }}">
                <input type="hidden" name="tahunMasuk" value="{{ $km->tahunMasuk }}">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama / NPM</label>
                    <input type="text" class="form-control form-control-sm bg-light text-dark" value="{{ $km->nama }} ({{ $km->npm }})" disabled>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Kelas <span class="text-danger">*</span></label>
                        <input type="text" name="kelas" class="form-control form-control-sm" value="{{ $km->kelas }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Semester <span class="text-danger">*</span></label>
                        <input type="number" name="semester" class="form-control form-control-sm" value="{{ $km->semester }}" required>
                    </div>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Tahun Akademik <span class="text-danger">*</span></label>
                        <select name="tahunAkademik" id="tahunAkademik{{ $km->id }}" class="form-select form-select-sm select2-ta" style="width: 100%;" required>
                            <option value="">-- Pilih TA --</option>
                            @foreach($tahunAkademiks as $ta)
                                @php
                                    $valTa = strtolower($ta->semesterAkademik);
                                    $isGanjil = (strpos($valTa, 'ganjil') !== false || strpos($valTa, '1') !== false || strpos($valTa, 'gasal') !== false);
                                    $teksSemester = $isGanjil ? 'Ganjil' : 'Genap';
                                @endphp
                                <option value="{{ $ta->tahunAkademik }}" {{ ($km->tahunAkademik ?? $setting->ta_aktif) == $ta->tahunAkademik ? 'selected' : '' }}>
                                    {{ $ta->tahunAkademik }} ({{ $teksSemester }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Status Keaktifan</label>
                        <select name="keterangan" class="form-select form-select-sm select-keterangan-mhs" data-id="{{ $km->id }}">
                            <option value="A" {{ $km->keterangan == 'A' ? 'selected' : '' }}>Aktif (A)</option>
                            <option value="NA" {{ $km->keterangan == 'NA' ? 'selected' : '' }}>Non-Aktif (NA)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-2 div-status-keterangan" id="divStatusKeterangan{{ $km->id }}" style="{{ $km->keterangan == 'A' ? 'display: none;' : '' }}">
                    <label class="form-label small fw-bold text-danger">Alasan Non-Aktif <span class="text-danger">*</span></label>
                    <select name="statusKeterangan" id="statusKeterangan{{ $km->id }}" class="form-select select2-status" style="width: 100%;">
                        <option value="">-- Pilih Alasan --</option>
                        <option value="Lulus" {{ strpos(strtolower($km->statusKeterangan ?? ''), 'lulus') !== false ? 'selected' : '' }}>Lulus (Tamat)</option>
                        <option value="DO" {{ $km->statusKeterangan == 'DO' ? 'selected' : '' }}>DO (Drop Out)</option>
                        <option value="SO" {{ $km->statusKeterangan == 'SO' ? 'selected' : '' }}>SO (Skorsing)</option>
                        <option value="Undur Diri" {{ $km->statusKeterangan == 'Undur Diri' ? 'selected' : '' }}>Undur Diri</option>
                        <option value="Mengulang 1 Tahun" {{ $km->statusKeterangan == 'Mengulang 1 Tahun' ? 'selected' : '' }}>Mengulang 1 Tahun</option>
                        <option value="Menunggu Ujian" {{ $km->statusKeterangan == 'Menunggu Ujian' ? 'selected' : '' }}>Menunggu Ujian</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-warning fw-semibold text-white">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
    const boxAksiMassal = document.getElementById('boxAksiMassal');
    const textTerpilih = document.getElementById('textTerpilih');
    const btnPilihSemuaHalamanWrapper = document.getElementById('btnPilihSemuaHalamanWrapper');
    const btnPilihSemuaHalaman = document.getElementById('btnPilihSemuaHalaman');
    const btnBatalkanSemuaHalaman = document.getElementById('btnBatalkanSemuaHalaman');
    const inputPilihSemuaFilter = document.getElementById('inputPilihSemuaFilter');

    const totalDataKeseluruhan = {{ $kelasMahasiswas->total() }};

    function refreshHitunganMassal() {
        const semuaDicentang = document.querySelectorAll('.sub-chk-mhs:checked').length;
        const totalAktifHalamanIni = document.querySelectorAll('.sub-chk-mhs:not(:disabled)').length;

        if (semuaDicentang > 0) {
            if (boxAksiMassal) boxAksiMassal.classList.remove('d-none');
            
            if (inputPilihSemuaFilter && inputPilihSemuaFilter.value === "1") {
                if (textTerpilih) textTerpilih.textContent = 'Semua ' + totalDataKeseluruhan + ' Mahasiswa Terfilter Terpilih (Lintas Halaman)';
            } else {
                if (textTerpilih) textTerpilih.textContent = semuaDicentang + ' Mahasiswa Aktif Terpilih';
            }

            if (semuaDicentang === totalAktifHalamanIni && totalDataKeseluruhan > totalAktifHalamanIni) {
                if (btnPilihSemuaHalamanWrapper) btnPilihSemuaHalamanWrapper.classList.remove('d-none');
            } else {
                if (inputPilihSemuaFilter && inputPilihSemuaFilter.value !== "1") {
                    if (btnPilihSemuaHalamanWrapper) btnPilihSemuaHalamanWrapper.classList.add('d-none');
                }
            }
        } else {
            resetOpsiPilihanSemuaHalaman();
            if (boxAksiMassal) boxAksiMassal.classList.add('d-none');
        }
    }

    function resetOpsiPilihanSemuaHalaman() {
        if (inputPilihSemuaFilter) inputPilihSemuaFilter.value = "0";
        if (btnPilihSemuaHalaman) btnPilihSemuaHalaman.classList.remove('d-none');
        if (btnBatalkanSemuaHalaman) btnBatalkanSemuaHalaman.classList.add('d-none');
        if (btnPilihSemuaHalamanWrapper) btnPilihSemuaHalamanWrapper.classList.add('d-none');
    }

    if (btnPilihSemuaHalaman) {
        btnPilihSemuaHalaman.addEventListener('click', function(e) {
            e.preventDefault();
            if (inputPilihSemuaFilter) inputPilihSemuaFilter.value = "1";
            this.classList.add('d-none');
            if (btnBatalkanSemuaHalaman) btnBatalkanSemuaHalaman.classList.remove('d-none');
            refreshHitunganMassal();
        });
    }

    if (btnBatalkanSemuaHalaman) {
        btnBatalkanSemuaHalaman.addEventListener('click', function(e) {
            e.preventDefault();
            if (inputPilihSemuaFilter) inputPilihSemuaFilter.value = "0";
            this.classList.add('d-none');
            if (btnPilihSemuaHalaman) btnPilihSemuaHalaman.classList.remove('d-none');
            refreshHitunganMassal();
        });
    }

    document.addEventListener('change', function(event) {
        if (event.target && event.target.id === 'checkAllMaster') {
            const apakahChecked = event.target.checked;
            document.querySelectorAll('.sub-chk-mhs').forEach(function(checkbox) {
                if (!checkbox.disabled) checkbox.checked = apakahChecked;
            });
            if (!apakahChecked) resetOpsiPilihanSemuaHalaman();
            refreshHitunganMassal();
        }

        if (event.target && event.target.classList.contains('sub-chk-mhs')) {
            const masterCheck = document.getElementById('checkAllMaster');
            const totalAktif = document.querySelectorAll('.sub-chk-mhs:not(:disabled)').length;
            const totalDicentang = document.querySelectorAll('.sub-chk-mhs:checked').length;
            
            if (masterCheck) masterCheck.checked = (totalAktif === totalDicentang && totalAktif > 0);
            if (totalDicentang < totalAktif) resetOpsiPilihanSemuaHalaman();
            refreshHitunganMassal();
        }

        if (event.target && event.target.classList.contains('select-keterangan-mhs')) {
            const id = event.target.getAttribute('data-id');
            const statusValue = event.target.value;
            const targetDiv = document.getElementById('divStatusKeterangan' + id);
            const selectElement = document.getElementById('statusKeterangan' + id);

            if (statusValue === 'NA') {
                if (targetDiv) targetDiv.style.display = 'block';
                if (selectElement) selectElement.setAttribute('required', 'required');
            } else {
                if (targetDiv) targetDiv.style.display = 'none';
                if (selectElement) {
                    selectElement.removeAttribute('required');
                    selectElement.value = '';
                }
                if (typeof $ !== 'undefined' && jQuery.fn.select2) {
                    $('#statusKeterangan' + id).val('').trigger('change');
                }
            }
        }
    });

    if (typeof $ !== 'undefined') {
        $('#elTa').select2({ theme: 'bootstrap-5', placeholder: "-- Semua TA --", allowClear: true });
        $('#targetTaMassal').select2({ theme: 'bootstrap-5', placeholder: "-- Pilih Target TA --" });

        $('.modal').on('shown.bs.modal', function () {
            let currentModal = $(this);
            currentModal.find('.select2-status').select2({
                dropdownParent: currentModal,
                theme: 'bootstrap-5',
                placeholder: "-- Pilih Alasan --",
                allowClear: true
            });
            currentModal.find('.select2-ta').select2({
                dropdownParent: currentModal,
                theme: 'bootstrap-5',
                placeholder: "-- Pilih TA --"
            });
        });

        $('.modal').on('hidden.bs.modal', function () {
            let currentModal = $(this);
            if (currentModal.find('.select2-status').data('select2')) currentModal.find('.select2-status').select2('destroy');
            if (currentModal.find('.select2-ta').data('select2')) currentModal.find('.select2-ta').select2('destroy');
        });
    }
});

function syncFilterValues() {
    const urlParams = new URLSearchParams(window.location.search);
    document.getElementById('hidSearch').value = urlParams.get('search') || '';
    document.getElementById('hidProdi').value = urlParams.get('prodi') || '';
    document.getElementById('hidTa').value = urlParams.get('ta') || '';
}

function eksekusiKenaikanMassal() {
    const formAksi = document.getElementById('formAksiMassal');
    formAksi.action = "{{ route('kelas-mahasiswa.promosi-massal') }}";
    syncFilterValues();

    const inputPilihSemua = document.getElementById('inputPilihSemuaFilter');
    const isPilihSemuaActive = inputPilihSemua && inputPilihSemua.value === "1";
    const totalData = {{ $kelasMahasiswas->total() }};
    const terpilihLokal = document.querySelectorAll('.sub-chk-mhs:checked').length;
    
    let infoTeks = isPilihSemuaActive 
        ? `Seluruh ${totalData} mahasiswa yang berada dalam filter saat ini akan dinaikkan tingkatnya (LINTAS HALAMAN).`
        : `Sebanyak ${terpilihLokal} mahasiswa yang dicentang akan diproses kenaikan kelasnya.`;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Eksekusi Kenaikan Kelas Massal?',
            text: infoTeks,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Ya, Proses Sekarang!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Memproses Kenaikan Massal...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                formAksi.submit();
            }
        });
    } else {
        if (confirm(infoTeks)) formAksi.submit();
    }
}

function eksekusiLulusMassal() {
    const formAksi = document.getElementById('formAksiMassal');
    formAksi.action = "{{ route('kelas-mahasiswa.lulus-massal') }}";
    syncFilterValues();

    const statusKet = document.getElementById('statusKeteranganMassal').value;
    const terpilihLokal = document.querySelectorAll('.sub-chk-mhs:checked').length;

    if (terpilihLokal === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Peringatan', 'Silakan pilih minimal satu mahasiswa untuk diubah statusnya.', 'warning');
        } else {
            alert('Silakan pilih minimal satu mahasiswa.');
        }
        return;
    }

    let infoTeks = `Sebanyak ${terpilihLokal} mahasiswa yang dicentang akan diubah status keaktifannya menjadi NA (${statusKet}).`;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Proses Status Kelulusan / Non-Aktif?',
            text: infoTeks,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Ya, Ubah Status!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Memproses Perubahan Status...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                formAksi.submit();
            }
        });
    } else {
        if (confirm(infoTeks)) formAksi.submit();
    }
}

function konfirmasiSync() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Tarik Data Mahasiswa?',
            text: "Sistem akan memeriksa dan menarik data mahasiswa baru dari Master Mahasiswa yang belum terdaftar di plotting kelas.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Sedang Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                document.getElementById('formSyncMahasiswa').submit();
            }
        });
    } else {
        if (confirm('Tarik Data Mahasiswa baru?')) document.getElementById('formSyncMahasiswa').submit();
    }
}

function konfirmasiHapusLangsung(id, nama) {
    let urlHapus = "{{ route('kelas-mahasiswa.destroy', ':id') }}".replace(':id', id);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Plotting Kelas?',
            text: `Apakah Anda yakin ingin menghapus plotting kelas untuk "${nama}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                let formHapus = document.getElementById('formHapusSatuan');
                formHapus.action = urlHapus;
                formHapus.submit();
            }
        });
    } else {
        if (confirm(`Hapus plotting kelas untuk "${nama}"?`)) {
            let formHapus = document.getElementById('formHapusSatuan');
            formHapus.action = urlHapus;
            formHapus.submit();
        }
    }
}
</script>
@endsection