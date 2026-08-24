@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">Rekap Absensi Mahasiswa</h3>
            <p class="text-muted small mb-0">Kelola kuantitas ketidakhadiran, sinkronisasi via Spatie Excel, serta manajemen nomor surat peringatan resmi.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('absensi.export', ['ta' => $taAktif]) }}" class="btn btn-sm btn-outline-secondary fw-semibold shadow-sm px-3 py-2">
                <i class="fa-solid fa-file-excel me-2 text-success"></i>Export Template
            </a>
            
            <button type="button" class="btn btn-sm btn-dark fw-semibold shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                <i class="fa-solid fa-file-import me-2"></i>Import Excel
            </button>

            <form id="formSyncAbsensi" action="{{ route('absensi.sync') }}" method="POST" style="display: inline;">
                @csrf
                <input type="hidden" name="ta_target" value="{{ $taAktif }}">
                <button type="button" onclick="konfirmasiSyncAbsensi()" class="btn btn-sm btn-success fw-semibold shadow-sm px-3 py-2">
                    <i class="fa-solid fa-sync me-2"></i>Sinkronkan Data
                </button>
            </form>
        </div>
    </div>

    {{-- BARIS FORM FILTER TERINTEGRASI --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('absensi.index') }}" method="GET" class="row g-2 align-items-center">
                {{-- Cari Kata Kunci --}}
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Cari NPM, Nama, atau Kelas..." value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Filter Program Studi --}}
                <div class="col-md-2">
                    <select name="prodi" class="form-select form-select-sm">
                        <option value="">-- Semua Prodi --</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->kodeProdi }}" {{ request('prodi') == $p->kodeProdi ? 'selected' : '' }}>{{ $p->namaProdi }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Status SP / Aman (BARU) --}}
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="Aman" {{ request('status') == 'Aman' ? 'selected' : '' }}>Aman (-)</option>
                        <option value="SP1" {{ request('status') == 'SP1' ? 'selected' : '' }}>SP1 (Hijau)</option>
                        <option value="SP2" {{ request('status') == 'SP2' ? 'selected' : '' }}>SP2 (Kuning)</option>
                        <option value="SP3" {{ request('status') == 'SP3' ? 'selected' : '' }}>SP3 (Merah)</option>
                        <option value="DO" {{ request('status') == 'DO' ? 'selected' : '' }}>DO (Hitam)</option>
                    </select>
                </div>
              
                {{-- Filter Tahun Akademik --}}
                <div class="col-md-3">
                    <select name="ta" id="filterTaAbsensi" class="form-select form-select-sm" style="width: 100%;">
                        @foreach($tahunAkademiks as $ta)
                            @php
                                $valTa = strtolower($ta->semesterAkademik);
                                $isGanjil = (strpos($valTa, 'ganjil') !== false || strpos($valTa, '1') !== false || strpos($valTa, 'gasal') !== false);
                                $teksSemester = $isGanjil ? 'Ganjil' : 'Genap';
                            @endphp
                            <option value="{{ $ta->tahunAkademik }}" {{ $taAktif == $ta->tahunAkademik ? 'selected' : '' }}>
                                {{ $ta->tahunAkademik }} ({{ $teksSemester }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tombol Submit Form Filter --}}
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter Tampilan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light text-secondary fw-semibold">
                        <tr>
                            <th width="4%" class="text-center">No</th>
                            <th width="12%">NPM</th>
                            <th width="20%">Nama & Kelas</th>
                            <th width="7%" class="text-center">Telat</th>
                            <th width="7%" class="text-center">Alpa</th>
                            <th width="6%" class="text-center">Izin</th>
                            <th width="6%" class="text-center">Sakit</th>
                            <th width="6%" class="text-center">Dispen</th>
                            <th width="12%" class="text-center">Status Absensi</th>
                            <th width="16%">Status Surat / Usulan Resmi</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absensis as $index => $abs)
                        @php
                            $statusDb = trim($abs->statusAbsensi);
                            $statusTampil = ($statusDb === '-' || empty($statusDb)) ? 'Aman' : $statusDb;

                            // Aturan Pewarnaan Baris Tabel
                            $bgRowClass = '';
                            if ($statusDb === 'SP1') {
                                $bgRowClass = 'table-success text-dark';
                            } elseif ($statusDb === 'SP2') {
                                $bgRowClass = 'table-warning text-dark';
                            } elseif ($statusDb === 'SP3') {
                                $bgRowClass = 'table-danger text-dark';
                            } elseif ($statusDb === 'DO') {
                                $bgRowClass = 'bg-dark text-white';
                            }
                        @endphp
                        <tr class="{{ $bgRowClass }}">
                            <td class="text-center text-muted">{{ $absensis->firstItem() + $index }}</td>
                            <td class="fw-bold">{{ $abs->npm }}</td>
                            <td>
                                <div class="fw-semibold">{{ $abs->nama }}</div>
                                <span class="badge bg-primary-subtle text-primary mt-1" style="font-size: 11px;">Kelas: {{ $abs->kelas }}</span>
                            </td>
                            <td class="text-center fw-medium">{{ $abs->terlambat }} Min</td>
                            <td class="text-center fw-bold">{{ $abs->alpa }} Jam</td>
                            <td class="text-center fw-medium">{{ $abs->izin }} J</td>
                            <td class="text-center fw-medium">{{ $abs->sakit }} J</td>
                            <td class="text-center fw-medium">{{ $abs->dispensasi }} J</td>
                            
                            <td class="text-center">
                                @if($statusDb === 'SP1')
                                    <span class="badge bg-success px-2 py-1 rounded-pill w-100 text-white">SP1</span>
                                @elseif($statusDb === 'SP2')
                                    <span class="badge bg-warning px-2 py-1 rounded-pill w-100 text-dark">SP2</span>
                                @elseif($statusDb === 'SP3')
                                    <span class="badge bg-danger px-2 py-1 rounded-pill w-100 text-white">SP3</span>
                                @elseif($statusDb === 'DO')
                                    <span class="badge bg-secondary px-2 py-1 rounded-pill w-100 text-white">DO</span>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill w-100">Aman</span>
                                @endif
                            </td>
                            
                            <td>
                                @if(strpos(strtolower($abs->statusSurat), 'belum dibuat') !== false)
                                    <span class="text-muted italic"><i class="fa-solid fa-clock-rotate-left me-1"></i>Belum dibuat</span>
                                @elseif(strpos($abs->statusSurat, 'sudah dibuat') !== false)
                                    <span class="small fw-semibold text-break">
                                        <i class="fa-solid fa-envelope-open-text text-primary me-1"></i>{{ $abs->statusSurat }}
                                    </span>
                                    <button type="button" class="btn btn-xs btn-link p-0 ms-1 text-muted text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalBuatSurat{{ $abs->id }}">
                                        <i class="fa-solid fa-pen small"></i>
                                    </button>
                                @else
                                    @if($abs->statusSurat == 'Ada')
                                        <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check"></i> Ada Surat</span>
                                    @elseif($abs->statusSurat == 'Ditolak')
                                        <span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark"></i> Ditolak</span>
                                    @else
                                        <span class="text-muted"><i class="fa-solid fa-minus"></i> Tidak Ada</span>
                                    @endif
                                @endif

                                @if(in_array($statusDb, ['SP1', 'SP2', 'SP3','DO']) && strpos(strtolower($abs->statusSurat), 'belum dibuat') !== false)
                                    <button type="button" class="btn btn-xs btn-link p-0 ms-1 text-decoration-none fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#modalBuatSurat{{ $abs->id }}">
                                        [Input No]
                                    </button>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalInputAbsen{{ $abs->id }}" title="Edit Manual">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form id="formHapusAbsen{{ $abs->id }}" action="{{ route('absensi.delete', $abs->id) }}" method="POST" style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button type="button" onclick="konfirmasiHapusAbsen('{{ $abs->id }}', '{{ $abs->nama }}')" class="btn btn-sm btn-outline-danger py-1 px-2" title="Hapus Riwayat">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-clipboard-user d-block fs-3 mb-2"></i> Belum ada rekap data absensi mahasiswa.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer bg-white border-0 py-3">
                {{ $absensis->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL IMPORT VIA EXCEL --}}
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('absensi.import') }}" method="POST" enctype="multipart/form-data" class="modal-content text-dark">
            @csrf
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" style="font-size: 15px;"><i class="fa-solid fa-file-excel me-2"></i>Import via Spatie Simple Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3" style="font-size: 13px;">
                <div class="alert alert-warning border-0 small mb-3">
                    <b>Skema Dual Alur Spatie:</b><br>
                    1. Jika status SP konstan &rarr; Akumulasi nilai otomatis ter-<b>update</b>.<br>
                    2. Jika status SP berubah &rarr; Sistem otomatis melakukan <b>insert</b> data riwayat baru.
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold">Pilih Dokumen Excel (.xlsx)</label>
                    <input type="file" name="file_excel" class="form-control form-control-sm" accept=".xlsx" required>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="fa-solid fa-upload me-1"></i> Unggah & Proses</button>
            </div>
        </form>
    </div>
</div>

{{-- GENERATE LOOP MODAL EDIT DAN SURAT --}}
@foreach($absensis as $abs)
{{-- MODAL INPUT/EDIT MANUAL ABSENSI --}}
<div class="modal fade" id="modalInputAbsen{{ $abs->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('absensi.update', $abs->id) }}" method="POST" class="modal-content text-dark">
            @csrf
            @method('PUT')
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Update Manual Absensi</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3" style="font-size: 13px;">
                <div class="bg-light p-2 border rounded mb-3 small">
                    Mhs: <b>{{ $abs->nama }}</b> ({{ $abs->npm }})<br>
                    Kelas: <b>{{ $abs->kelas }}</b> | TA: <b>{{ $abs->tahunAkademik }}</b>
                </div>
                <div class="row g-2">
                    <div class="col-6 mb-2">
                        <label class="form-label fw-bold mb-1">Terlambat (Menit)</label>
                        <input type="number" name="terlambat" class="form-control form-control-sm" value="{{ $abs->terlambat }}" required min="0">
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label fw-bold mb-1">Alpa (Jam)</label>
                        <input type="number" name="alpa" class="form-control form-control-sm" value="{{ $abs->alpa }}" required min="0">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-bold mb-1">Izin (Jam)</label>
                        <input type="number" name="izin" class="form-control form-control-sm" value="{{ $abs->izin }}" required min="0">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-bold mb-1">Sakit (Jam)</label>
                        <input type="number" name="sakit" class="form-control form-control-sm" value="{{ $abs->sakit }}" required min="0">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-bold mb-1">Dispensasi</label>
                        <input type="number" name="dispensasi" class="form-control form-control-sm" value="{{ $abs->dispensasi }}" required min="0">
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

{{-- MODAL INPUT NO SURAT RESMI --}}
<div class="modal fade" id="modalBuatSurat{{ $abs->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form action="{{ route('absensi.buatsurat', $abs->id) }}" method="POST" class="modal-content text-dark">
            @csrf
            @method('PUT')
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-stamp me-2"></i>No Usulan Surat SP</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 text-start" style="font-size: 13px;">
                <div class="bg-light p-2 border rounded mb-2 small">
                    Mhs: <b>{{ $abs->nama }}</b><br>
                    Status: <span class="badge bg-danger">{{ ($abs->statusAbsensi == '-') ? 'Aman' : $abs->statusAbsensi }}</span>
                </div>
                <div>
                    <label class="form-label fw-bold mb-1">Nomor Surat Resmi</label>
                    <input type="text" name="nomor_surat" class="form-control form-control-sm" placeholder="Contoh: 045/PL11.5/Ak/2026" required>
                </div>
            </div>
            <div class="modal-footer bg-light py-1">
                <button type="button" class="btn btn-xs btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-xs btn-primary fw-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && jQuery.fn.select2) {
        $('#filterTaAbsensi').select2({ theme: 'bootstrap-5', placeholder: "-- Pilih TA --" });
    }
});

function konfirmasiSyncAbsensi() {
    let taAktifTeks = "{{ $taAktif }}";
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Sinkronisasi Absensi?',
            text: `Sistem akan menarik mahasiswa aktif ke data rekap absensi pada Tahun Akademik ${taAktifTeks}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Ambil Data!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Menyinkronkan data absensi...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                document.getElementById('formSyncAbsensi').submit();
            }
        });
    } else {
        if (confirm(`Apakah Anda yakin ingin menarik data absensi mahasiswa baru untuk TA ${taAktifTeks}?`)) {
            document.getElementById('formSyncAbsensi').submit();
        }
    }
}

function konfirmasiHapusAbsen(id, namaMhs) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Data Absensi?',
            text: `Apakah Anda yakin ingin menghapus seluruh rekap data absensi untuk ${namaMhs}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus Data',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formHapusAbsen' + id).submit();
            }
        });
    } else {
        if (confirm(`Apakah Anda yakin ingin menghapus data absensi milik ${namaMhs}?`)) {
            document.getElementById('formHapusAbsen' + id).submit();
        }
    }
}
</script>
@endsection