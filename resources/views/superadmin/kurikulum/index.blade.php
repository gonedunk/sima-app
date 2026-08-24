@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Kurikulum Mata Kuliah</h1>
        <div>
            <!-- Tambahan Kelompok Tombol Excel (Export & Import) -->
            <a href="{{ route('superadmin.kurikulum.export') }}" class="btn btn-success shadow-sm me-2">
                <i class="fa-solid fa-file-excel me-1"></i> Export Excel
            </a>
            <button type="button" class="btn btn-outline-success shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                <i class="fa-solid fa-file-import me-1"></i> Import Excel
            </button>
            
            <button type="button" class="btn btn-primary shadow-sm" onclick="openTambahModal()">
                <i class="fa-solid fa-plus me-1"></i> Tambah Mata Kuliah
            </button>
        </div>
    </div>

    <!-- Tampilan Alert Success / Error untuk Feedback Import -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('file_excel'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i> {{ $errors->first('file_excel') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-3 bg-light">
            <form method="GET" action="{{ request()->url() }}" id="formFilterKurikulum">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Tahun Kurikulum</label>
                        <select name="filter_tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Tahun --</option>
                            @foreach($filterOptions['tahun'] ?? [] as $th)
                                <option value="{{ $th }}" {{ request('filter_tahun') == $th ? 'selected' : '' }}>{{ $th }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Semester</label>
                        <select name="filter_semester" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Smt --</option>
                            @for($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" {{ request('filter_semester') == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Program Studi</label>
                        <select name="filter_prodi" class="form-select form-select-sm select2-filter" onchange="this.form.submit()">
                            <option value="">-- Semua Program Studi --</option>
                            @foreach($prodi as $p)
                                <option value="{{ $p->kodeProdi }}" {{ request('filter_prodi') == $p->kodeProdi ? 'selected' : '' }}>{{ $p->namaProdi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="{{ request()->url() }}" class="btn btn-sm btn-outline-secondary w-100 w-md-auto">
                            <i class="fa-solid fa-rotate me-1"></i> Reset Filter
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="bulkActionCard" class="card shadow-sm border-0 bg-primary-subtle text-primary mb-3 d-none rounded-3">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="small fw-semibold">
                <i class="fa-solid fa-circle-check me-1"></i> <span id="selectedCount">0</span> Mata kuliah terpilih
            </span>
            <div>
                <button type="button" class="btn btn-sm btn-success me-1 shadow-sm" onclick="submitBulkAction('A')">
                    <i class="fa-solid fa-eye me-1"></i> Aktifkan Massal
                </button>
                <button type="button" class="btn btn-sm btn-danger shadow-sm" onclick="submitBulkAction('NA')">
                    <i class="fa-solid fa-eye-slash me-1"></i> Non-Aktifkan Massal
                </button>
            </div>
        </div>
    </div>

    <form id="formBulkStatus" action="{{ route('superadmin.kurikulum.bulkUpdateStatus') }}" method="POST">
        @csrf
        <input type="hidden" name="status_action" id="bulkStatusAction" value="">
        
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="tableKurikulum" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th class="text-center" style="width: 50px; max-width: 50px;">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input check-custom-all" type="checkbox" id="checkAllMk" onclick="toggleAllCheckboxes(this)">
                                    </div>
                                </th>
                                <th>Mata Kuliah</th>
                                <th class="text-center">Total SKS</th>
                                <th class="text-center">Total Jam</th>
                                <th class="text-center">Semester</th>
                                <th>Prodi</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse($kurikulum as $item)
                            <tr>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input sub-check-mk" type="checkbox" name="ids[]" value="{{ $item->id }}" onclick="evaluateCheckboxState()">
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary text-uppercase me-2">{{ $item->kodeMk }}</span>
                                    <span class="text-dark fw-medium">{{ $item->namaMk }}</span>
                                    <div class="ps-0 pt-1">
                                        <small class="text-muted fst-italic">{{ $item->namaMkInggris ?? '-' }}</small>
                                    </div>
                                </td>
                                <td class="text-center fw-semibold text-secondary">{{ $item->total }} SKS</td>
                                <td class="text-center fw-semibold text-secondary">{{ $item->totalJamPerMinggu ?? 0 }} Jam</td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill">Smt {{ $item->semester }}</span></td>
                                <td>{{ $item->prodi }}</td>
                                <td class="text-center">
                                    @if($item->statusKurikulum == 'A')
                                        <span class="badge bg-success-subtle text-success px-2 py-1">Aktif</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger px-2 py-1">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-warning me-1" onclick="openEditModal({{ json_encode($item) }})">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-single" data-url="{{ route('superadmin.kurikulum.destroy', $item->id) }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Tidak ada data kurikulum tersedia.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($kurikulum, 'links'))
                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
                        <div class="small text-muted">
                            Menampilkan {{ $kurikulum->firstItem() ?? 0 }} sampai {{ $kurikulum->lastItem() ?? 0 }} dari {{ $kurikulum->total() ?? 0 }} data
                        </div>
                        <div>
                            {{ $kurikulum->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>

<form id="formSingleDelete" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<!-- Modal Utama Tambah/Edit Mata Kuliah -->
<div class="modal fade" id="modalKurikulum" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalKurikulumLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalKurikulumLabel">Tambah Mata Kuliah</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formKurikulum" method="POST">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="POST">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode Mata Kuliah</label>
                            <input type="text" name="kodeMk" id="input_kodeMk" class="form-control form-control-sm text-uppercase" required autocomplete="off">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nama Mata Kuliah (Indonesia)</label>
                            <input type="text" name="namaMk" id="input_namaMk" class="form-control form-control-sm" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nama Mata Kuliah (Inggris)</label>
                            <input type="text" name="namaMkInggris" id="input_namaMkInggris" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jenis Mata Kuliah</label>
                            <select name="jenisMk" id="input_jenisMk" class="form-select form-select-sm" required>
                                <option value="">-- Otomatis --</option>
                                <option value="SIKAP">SIKAP</option>
                                <option value="PENGETAHUAN UMUM">PENGETAHUAN UMUM</option>
                                <option value="KETERAMPILAN UMUM">KETERAMPILAN UMUM</option>
                                <option value="KETERAMPILAN KHUSUS">KETERAMPILAN KHUSUS</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Semester</label>
                            <input type="number" name="semester" id="input_semester" class="form-control form-control-sm" min="1" max="8" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tahun Kurikulum</label>
                            <input type="text" name="tahunKurikulum" id="input_tahunKurikulum" class="form-control form-control-sm" placeholder="Contoh: 2026" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold d-block">Status Mata Kuliah</label>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="statusKurikulum" id="status_A" value="A" checked>
                                    <label class="form-check-label small" for="status_A">Aktif (A)</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="statusKurikulum" id="status_NA" value="NA">
                                    <label class="form-check-label small" for="status_NA">Non-Aktif (NA)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 rounded-3 mb-4">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3 small text-secondary text-uppercase"><i class="fa-solid fa-calculator me-1"></i> Distribusi SKS & Jam</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white align-middle mb-2 text-center small">
                                    <thead class="table-light text-muted fw-semibold">
                                        <tr>
                                            <th>Komponen Asal</th>
                                            <th style="width: 25%;">Teori (T)</th>
                                            <th style="width: 25%;">Praktik (P)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-start fw-medium ps-2">SKS Internal Prodi</td>
                                            <td><input type="number" name="sksProdiT" id="input_sksProdiT" class="form-control form-control-sm text-center border-0" value="0" min="0" oninput="hitungSKS()"></td>
                                            <td><input type="number" name="sksProdiP" id="input_sksProdiP" class="form-control form-control-sm text-center border-0" value="0" min="0" oninput="hitungSKS()"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-start fw-medium ps-2">SKS Luar Prodi</td>
                                            <td><input type="number" name="sksLpT" id="input_sksLpT" class="form-control form-control-sm text-center border-0" value="0" min="0" oninput="hitungSKS()"></td>
                                            <td><input type="number" name="sksLpP" id="input_sksLpP" class="form-control form-control-sm text-center border-0" value="0" min="0" oninput="hitungSKS()"></td>
                                        </tr>
                                        <tr class="table-light">
                                            <td class="text-start fw-medium ps-2 text-secondary">Alokasi Jam / Minggu</td>
                                            <td><input type="number" name="jamPerMingguT" id="input_jamT" class="form-control form-control-sm text-center border-0 bg-transparent" value="0" min="0" oninput="hitungJam()"></td>
                                            <td><input type="number" name="jamPerMingguP" id="input_jamP" class="form-control form-control-sm text-center border-0 bg-transparent" value="0" min="0" oninput="hitungJam()"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-0 small text-muted">Total SKS:</span>
                                        <input type="number" name="total" id="input_totalSKS" class="form-control fw-bold bg-white text-primary text-center border-0" value="0" readonly>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-0 small text-muted">Total Jam:</span>
                                        <input type="number" name="totalJamPerMinggu" id="input_totalJam" class="form-control fw-bold bg-white text-primary text-center border-0" value="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Program Studi</label>
                            <select name="prodi" id="input_prodi" class="form-select select2-modal" data-placeholder="-- Pilih Program Studi --" required style="width: 100%;">
                                <option value=""></option>
                                @foreach($prodi as $p)
                                    <option value="{{ $p->kodeProdi }}">{{ $p->namaProdi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jurusan</label>
                            <select name="jurusan" id="input_jurusan" class="form-select select2-modal" data-placeholder="-- Pilih Jurusan --" required style="width: 100%;">
                                <option value=""></option>
                                @foreach($jurusan as $j)
                                    <option value="{{ $j->kodeJurusan }}">{{ $j->namaJurusan }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="button" class="btn btn-sm btn-secondary me-1" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btnSimpan">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tambahan Baru: Modal Import Excel -->
<div class="modal fade" id="modalImportExcel" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalImportExcelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalImportExcelLabel"><i class="fa-solid fa-file-import me-2"></i>Import Data via Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('superadmin.kurikulum.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-3">
                        <i class="fa-solid fa-circle-info me-1"></i> <strong>Informasi:</strong> Jika <code>kodeMk</code> sudah ada di sistem, data baris tersebut otomatis akan diperbarui (update). Jika belum ada, akan disimpan sebagai data baru (insert).
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Berkas Excel (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file_excel" class="form-control form-control-sm" required accept=".xlsx, .xls, .csv">
                        <div class="form-text small text-muted">Maksimal ukuran file adalah 10 MB. Pastikan susunan header kolom sesuai format default.</div>
                    </div>
                    <div class="text-end text-muted small mt-2">
                        Belum punya template? Unduh template terformat dengan klik tombol <strong class="text-success">Export Excel</strong> terlebih dahulu.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const bootstrapModal = new bootstrap.Modal(document.getElementById('modalKurikulum'));
    var dTable;

    // 1. PURE NATIVE BYPASS FOR CHECK ALL
    function toggleAllCheckboxes(masterField) {
        let statusSistem = masterField.checked;
        let tumpukanCheckbox = document.querySelectorAll('.sub-check-mk');
        
        tumpukanCheckbox.forEach(function(checkbox) {
            checkbox.checked = statusSistem;
        });
        
        evaluateCheckboxState();
    }

    // 2. LIVE DOM SCANNER
    function evaluateCheckboxState() {
        let seluruhKotakCentang = document.querySelectorAll('.sub-check-mk');
        let totalTercentang = 0;

        seluruhKotakCentang.forEach(function(cb) {
            if(cb.checked) totalTercentang++;
        });

        let checkAllField = document.getElementById('checkAllMk');
        if(checkAllField) {
            checkAllField.checked = (totalTercentang === seluruhKotakCentang.length && seluruhKotakCentang.length > 0);
        }

        let panelAksiMassal = document.getElementById('bulkActionCard');
        let textJumlahTerpilih = document.getElementById('selectedCount');
        
        if (totalTercentang > 0) {
            if(textJumlahTerpilih) textJumlahTerpilih.innerText = totalTercentang;
            if(panelAksiMassal) panelAksiMassal.classList.remove('d-none');
        } else {
            if(panelAksiMassal) panelAksiMassal.classList.add('d-none');
        }
    }

    // 3. EXECUTE BULK ACTION POST VIA LARAVEL SECARA AKURAT
    function submitBulkAction(actionTarget) {
        let kataKerja = (actionTarget === 'A') ? 'Mengaktifkan' : 'Menonaktifkan';
        let warnaKonfirmasi = (actionTarget === 'A') ? '#198754' : '#dc3545';
        
        let totalTercentang = 0;
        document.querySelectorAll('.sub-check-mk').forEach(function(cb) {
            if(cb.checked) totalTercentang++;
        });

        if (totalTercentang === 0) {
            Swal.fire({ icon: 'warning', title: 'Peringatan', text: 'Pilih minimal satu data mata kuliah!' });
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Massal',
            text: `Apakah Anda yakin ingin ${kataKerja.toLowerCase()} ${totalTercentang} mata kuliah terpilih?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: warnaKonfirmasi,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Proses Massal',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('bulkStatusAction').value = actionTarget;
                document.getElementById('formBulkStatus').submit();
            }
        });
    }

    // 4. OTOMATISASI EVENT JURUSAN / KODE MATAKULIAH
    function sesuaikanJenisDanSemester(kodeMk) {
        let kode = kodeMk ? kodeMk.trim() : '';
        if (kode.length >= 5) {
            let charKelima = kode.charAt(4); 
            let jenisMk = "";
            switch (charKelima) {
                case '0': jenisMk = "SIKAP"; break;
                case '1': jenisMk = "PENGETAHUAN UMUM"; break;
                case '2': jenisMk = "KETERAMPILAN UMUM"; break;
                case '3': jenisMk = "KETERAMPILAN KHUSUS"; break;
                default: jenisMk = ""; break;
            }
            $('#input_jenisMk').val(jenisMk);
        } else {
            $('#input_jenisMk').val('');
        }

        if (kode.length >= 6) {
            let charKeenam = kode.charAt(5);
            if (['1','2','3','4','5','6','7','8'].includes(charKeenam)) {
                $('#input_semester').val(charKeenam);
            } else {
                $('#input_semester').val('');
            }
        } else {
            if ($('#methodField').val() === 'POST') {
                $('#input_semester').val('');
            }
        }
    }

    $(document).ready(function() {
        // INISIALISASI DATATABLES AMAN BERSAMA PAGINASI LARAVEL
        // target: [0, 7] disesuaikan karena struktur kolom berubah (sekarang aksi berada pada indeks 7)
        dTable = $('#tableKurikulum').DataTable({
            "paging": false,       
            "info": false,         
            "ordering": true,      
            "searching": true,     
            "columnDefs": [
                { "orderable": false, "targets": [0, 7] } 
            ],
            "language": {
                "search": "Cari Cepat:",
                "zeroRecords": "Tidak ada data yang sesuai dengan kata kunci pencarian"
            }
        });

        dTable.on('draw', function () {
            evaluateCheckboxState();
        });

        $('.select2-modal').select2({
            dropdownParent: $('#modalKurikulum'),
            theme: 'bootstrap-5'
        });

        $('.select2-filter').select2({
            theme: 'bootstrap-5'
        });

        $('#input_kodeMk').on('input change keyup', function() {
            sesuaikanJenisDanSemester($(this).val());
        });

        $('.btn-hapus-single').on('click', function() {
            let actionUrl = $(this).data('url');
            Swal.fire({
                title: 'Hapus Mata Kuliah?',
                text: "Data kurikulum yang dihapus tidak bisa dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    let formDelete = $('#formSingleDelete');
                    formDelete.attr('action', actionUrl);
                    formDelete.submit();
                }
            });
        });
    });

    function openTambahModal() {
        $('#modalKurikulumLabel').text('Tambah Mata Kuliah Baru');
        $('#formKurikulum').attr('action', "{{ route('superadmin.kurikulum.store') }}");
        $('#methodField').val('POST');
        $('#formKurikulum')[0].reset();
        $('#input_prodi').val('').trigger('change');
        $('#input_jurusan').val('').trigger('change');
        $('#input_jenisMk').val('');
        $('#status_A').prop('checked', true);
        bootstrapModal.show();
    }

    function openEditModal(data) {
        $('#modalKurikulumLabel').text('Ubah Data Mata Kuliah');
        $('#formKurikulum').attr('action', `/superadmin/kurikulum/${data.id}`);
        $('#methodField').val('PUT');

        $('#input_kodeMk').val(data.kodeMk);
        $('#input_namaMk').val(data.namaMk);
        $('#input_namaMkInggris').val(data.namaMkInggris || '');
        
        // Memastikan jika format DATE (YYYY-MM-DD), ambil tahunnya saja (4 digit) untuk input form
        let tahun = data.tahunKurikulum ? data.tahunKurikulum.toString() : '';
        if (tahun.length >= 4) {
            tahun = tahun.substring(0, 4);
        }
        $('#input_tahunKurikulum').val(tahun);

        $('#input_sksProdiT').val(data.sksProdiT);
        $('#input_sksProdiP').val(data.sksProdiP);
        $('#input_sksLpT').val(data.sksLpT);
        $('#input_sksLpP').val(data.sksLpP);
        $('#input_jamT').val(data.jamPerMingguT);
        $('#input_jamP').val(data.jamPerMingguP);
        
        sesuaikanJenisDanSemester(data.kodeMk);
        hitungSKS();
        hitungJam();

        $('#input_prodi').val(data.prodi).trigger('change');
        $('#input_jurusan').val(data.jurusan).trigger('change');

        if(data.statusKurikulum === 'A') {
            $('#status_A').prop('checked', true);
        } else {
            $('#status_NA').prop('checked', true);
        }
        bootstrapModal.show();
    }

    function hitungSKS() {
        let pT = parseInt($('#input_sksProdiT').val()) || 0;
        let pP = parseInt($('#input_sksProdiP').val()) || 0;
        let lT = parseInt($('#input_sksLpT').val()) || 0;
        let lP = parseInt($('#input_sksLpP').val()) || 0;
        $('#input_totalSKS').val(pT + pP + lT + lP);
    }

    function hitungJam() {
        let jT = parseInt($('#input_jamT').val()) || 0;
        let jP = parseInt($('#input_jamP').val()) || 0;
        $('#input_totalJam').val(jT + jP);
    }
</script>
@endsection