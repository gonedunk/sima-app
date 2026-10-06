@extends('layouts.app')

@section('title', 'Verifikasi Transkrip - SIMA PRO')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Page & Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold text-dark m-0">
            <i class="fa-solid fa-file-signature text-primary me-2"></i>Verifikasi Transkrip Mahasiswa
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <!-- Tombol Export CSV -->
            <a href="{{ route('transkrip.admin.export', request()->query()) }}" class="btn btn-sm btn-success fw-semibold">
                <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </a>

            <!-- Tombol Trigger Modal Import -->
            <button type="button" class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalImportMahasiswa">
                <i class="fa-solid fa-file-import me-1"></i> Import Mahasiswa (CSV)
            </button>

            <!-- Tombol Pembersihan Akun Mahasiswa Lulus -->
            <form action="{{ route('transkrip.admin.cleanup_users') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan akun mahasiswa yang sudah Lulus dari tabel users?\n\nTenang, data transkrip dan rekapitulasi nilai TIDAK akan terhapus.')" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning text-dark fw-semibold">
                    <i class="fa-solid fa-user-slash me-1"></i> Bersihkan Akun Lulus
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-warning alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body">
               
            <!-- Filter & Search Form -->
            <form method="GET" action="{{ route('transkrip.admin.index') }}" class="row g-2 mb-3">
                <!-- Filter Tahun Akademik (Genap) -->
                <div class="col-md-2">
                    <select name="tahun_akademik" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Thn Akademik --</option>
                        @foreach($listTahunAkademik as $ta)
                            <option value="{{ $ta }}" {{ request('tahun_akademik') == $ta ? 'selected' : '' }}>
                                {{ $ta }} (Genap)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="prodi" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Program --</option>
                        <option value="3050" {{ request('prodi') == '3050' ? 'selected' : '' }}>D3 Akuntansi</option>
                        <option value="4051" {{ request('prodi') == '4051' ? 'selected' : '' }}>D4 Akuntansi</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Verifikasi --</option>
                        <option value="Belum Diperiksa" {{ request('status') == 'Belum Diperiksa' ? 'selected' : '' }}>Belum Diperiksa</option>
                        <option value="Valid" {{ request('status') == 'Valid' ? 'selected' : '' }}>Valid</option>
                        <option value="Invalid" {{ request('status') == 'Invalid' ? 'selected' : '' }}>Invalid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="upload_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Berkas --</option>
                        <option value="sudah" {{ request('upload_status') == 'sudah' ? 'selected' : '' }}>Sudah Upload Transkrip</option>
                        <option value="belum" {{ request('upload_status') == 'belum' ? 'selected' : '' }}>Belum Upload Transkrip</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NPM atau Nama..." value="{{ request('search') }}">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i> Cari
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tabel Data -->
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle fs-7">
                    <thead class="table-primary text-nowrap">
                        <tr>
                            <th width="50">No</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program</th>
                            <th>File Transkrip</th>
                            <th>Catatan</th>
                            <th>Status Verifikasi</th>
                            <th width="100" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transkrip as $index => $item)
                        @php
                            $hasFile = $item->path_file && \Illuminate\Support\Facades\Storage::disk('public')->exists($item->path_file);
                        @endphp
                        <tr>
                            <td>{{ $transkrip->firstItem() + $index }}</td>
                            <td>{{ $item->npm }}</td>
                            <td class="fw-semibold">{{ $item->nama }}</td>
                            <td>{{ $item->prodi == '3050' ? 'D3 Akuntansi' : 'D4 Akuntansi' }}</td>
                            <td>
                                @if($hasFile)
                                <a href="{{ route('transkrip.admin.view_file', basename($item->path_file)) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2">
    								<i class="fa-solid fa-file-pdf me-1"></i> Lihat File
								</a>
                                @else
                                    <span class="badge bg-light text-muted border">Belum Upload</span>
                                @endif
                            </td>
                            <td>{{ $item->catatan ?? '-' }}</td>
                            <td>
                                @if($item->status_verifikasi == 'Valid')
                                    <span class="badge bg-success">Valid</span>
                                @elseif($item->status_verifikasi == 'Invalid')
                                    <span class="badge bg-danger">Invalid</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Diperiksa</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($hasFile)
                                    <button type="button" 
                                            class="btn btn-sm btn-info text-white py-0 px-2 btn-verifikasi"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalVerifikasi"
                                            data-npm="{{ $item->npm }}"
                                            data-nama="{{ $item->nama }}"
                                            data-status="{{ $item->status_verifikasi ?? 'Belum Diperiksa' }}"
                                            data-catatan="{{ $item->catatan }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                @else
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary text-white py-0 px-2 disabled" 
                                            disabled 
                                            title="Mahasiswa belum meng-upload transkrip">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Data mahasiswa tidak ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Single Pagination -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 pt-2 border-top gap-2">
                <div class="small text-muted">
                    Menampilkan <strong>{{ $transkrip->firstItem() ?? 0 }}</strong> - <strong>{{ $transkrip->lastItem() ?? 0 }}</strong> dari <strong>{{ $transkrip->total() }}</strong> data
                </div>
                <div>
                    {{ $transkrip->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import Mahasiswa + Progress Bar -->
<div class="modal fade" id="modalImportMahasiswa" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formImportExcel" action="{{ route('transkrip.admin.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-csv me-2"></i> Import Mahasiswa (Format CSV)</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseImportHeader"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_excel" class="form-label small fw-bold">Pilih File CSV (.csv)</label>
                        <input type="file" name="file_excel" id="file_excel" class="form-control form-control-sm" accept=".csv, text/csv" required>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-0 small">
                        <i class="fa-solid fa-circle-info me-1"></i> <strong>Ketentuan Import CSV:</strong>
                        <ul class="mb-0 ps-3 mt-1 text-muted">
                            <li>Header CSV: <code>npm</code>, <code>nama</code>, <code>email</code>, <code>program</code></li>
                            <li>Bisa langsung menggunakan file hasil <strong>Export CSV</strong> sistem.</li>
                            <li>Sistem otomatis membuat akun login (<strong>Username = NPM</strong>, <strong>Password = NPM</strong>).</li>
                        </ul>
                    </div>

                    <!-- Progress Bar Container -->
                    <div id="progressContainer" class="mt-3 d-none">
                        <div class="d-flex justify-content-between mb-1">
                            <span id="progressStatusText" class="fw-bold fs-7 text-primary">Mengunggah File...</span>
                            <span id="progressPercentText" class="fw-bold fs-7 text-primary">0%</span>
                        </div>
                        <div class="progress" style="height: 18px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                0%
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block text-center" id="progressSubText">Mohon tunggu, memproses baris data ke database...</small>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" id="btnCancelImport">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitImport">
                        <i class="fa-solid fa-upload me-1"></i> Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi -->
<div class="modal fade" id="modalVerifikasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formVerifikasi" method="POST" action="">
                @csrf
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-circle-check me-2"></i> Verifikasi Transkrip</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted m-0">Mahasiswa</label>
                        <div id="modalNamaMhs" class="fw-bold text-dark"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status Verifikasi</label>
                        <select name="status_verifikasi" id="modalStatus" class="form-select form-select-sm" required>
                            <option value="Belum Diperiksa">Belum Diperiksa</option>
                            <option value="Valid">Valid</option>
                            <option value="Invalid">Invalid</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Catatan / Alasan (Opsional)</label>
                        <textarea name="catatan" id="modalCatatan" class="form-control form-control-sm" rows="3" placeholder="Contoh: Dokumen buram, file bukan transkrip resmi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle Modal Verifikasi
        $('.btn-verifikasi').on('click', function() {
            let npm = $(this).data('npm');
            let nama = $(this).data('nama');
            let status = $(this).data('status');
            let catatan = $(this).data('catatan');

            $('#modalNamaMhs').text(nama + ' (' + npm + ')');
            $('#modalStatus').val(status);
            $('#modalCatatan').val(catatan);
            
            let actionUrl = "{{ url('/admin/transkrip/verifikasi') }}/" + npm;
            $('#formVerifikasi').attr('action', actionUrl);
        });

        // Handle AJAX Upload & Progress Bar
        $('#formImportExcel').on('submit', function(e) {
            e.preventDefault();

            let fileInput = $('#file_excel')[0].files[0];
            if (!fileInput) {
                alert('Pilih file CSV terlebih dahulu!');
                return;
            }

            // Validasi format file di sisi Javascript (harus .csv)
            let fileName = fileInput.name;
            let ext = fileName.split('.').pop().toLowerCase();
            if (ext !== 'csv') {
                alert('Format file harus berupa CSV (.csv)! Silakan pilih file dengan ekstensi .csv.');
                return;
            }

            let form = this;
            let formData = new FormData(form);
            let submitBtn = $('#btnSubmitImport');
            let cancelBtn = $('#btnCancelImport');
            let headerCloseBtn = $('#btnCloseImportHeader');
            let progressContainer = $('#progressContainer');
            let progressBar = $('#progressBar');
            let progressPercentText = $('#progressPercentText');
            let progressStatusText = $('#progressStatusText');

            // Reset UI
            progressBar.css('width', '0%').attr('aria-valuenow', 0).text('0%');
            progressPercentText.text('0%');
            progressStatusText.text('Mengunggah File CSV...');
            progressContainer.removeClass('d-none');

            submitBtn.prop('disabled', true);
            cancelBtn.prop('disabled', true);
            headerCloseBtn.prop('disabled', true);

            $.ajax({
                xhr: function() {
                    let xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            let percentComplete = Math.round((evt.loaded / evt.total) * 40);
                            progressBar.css('width', percentComplete + '%').attr('aria-valuenow', percentComplete).text(percentComplete + '%');
                            progressPercentText.text(percentComplete + '%');

                            if (percentComplete >= 40) {
                                progressStatusText.text('Memproses & Menyimpan Data ke Database...');
                            }
                        }
                    }, false);
                    return xhr;
                },
                type: 'POST',
                url: $(form).attr('action'),
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).text('100%');
                    progressPercentText.text('100%');
                    progressStatusText.text('Selesai!');

                    setTimeout(function() {
                        alert(response.message);
                        window.location.reload();
                    }, 400);
                },
                error: function(xhr) {
                    // Reset tombol jika gagal
                    submitBtn.prop('disabled', false);
                    cancelBtn.prop('disabled', false);
                    headerCloseBtn.prop('disabled', false);
                    progressContainer.addClass('d-none');

                    let errorMsg = 'Gagal memproses file.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.statusText) {
                        errorMsg = xhr.statusText;
                    }
                    
                    alert('Error (' + xhr.status + '): ' + errorMsg);
                }
            });
        });
    });
</script>
@endsection