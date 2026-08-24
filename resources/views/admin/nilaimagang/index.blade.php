@extends('layouts.app')

@section('title', 'Penilaian Instansi Magang - SIMA PRO')

@section('styles')
    <!-- Menggunakan Aset Lokal pada direktori public/ -->
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rowGroup.bootstrap5.min.css') }}">
    
    <style>
        .input-nilai-group .form-control {
            text-align: center;
            font-weight: bold;
        }
        .badge-nilai {
            font-size: 0.85rem;
            padding: 0.4rem 0.65rem;
        }
        #tabelMatriksNilai th, #tabelMatriksNilai td {
            vertical-align: middle;
        }
        /* Styling Row Grouping */
        tr.dtrg-group.dtrg-level-0 th {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            font-weight: 700 !important;
            font-size: 0.95rem;
            border-left: 4px solid #0d6efd !important;
        }
        tr.dtrg-group.dtrg-level-1 th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600 !important;
            font-size: 0.85rem;
            padding-left: 2rem !important;
            border-left: 4px solid #64748b !important;
        }
        /* Custom UI Search Box & Filter */
        .search-container {
            position: relative;
            max-width: 300px;
        }
        .search-clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #94a3b8;
            cursor: pointer;
            display: none;
        }
        .search-clear:hover {
            color: #ef4444;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid p-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Penilaian Instansi Magang</h3>
            <p class="text-muted small m-0">Kelola poin kompetensi nilai penempatan magang mahasiswa.</p>
        </div>
        <div>
            <span class="badge bg-white border text-secondary shadow-sm px-3 py-2 rounded-pill font-monospace">
                <i class="fa-solid fa-calendar-check text-success me-1"></i> TA Aktif: <strong>{{ $taAktif ?? 'Belum Diatur' }}</strong>
            </span>
        </div>
    </div>

    <!-- Filter & Custom Search Bar Controls -->
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <!-- Dropdown Filter Perusahaan -->
                <div class="col-md-5 col-lg-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter Perusahaan / Instansi:</label>
                    <select id="filterPerusahaan" class="form-select form-select-sm rounded-3">
                        <option value="">-- Semua Perusahaan --</option>
                        @foreach($daftarNilai->unique('namaPerusahaan')->sortBy('namaPerusahaan') as $instansi)
                            <option value="{{ $instansi->namaPerusahaan }}">{{ $instansi->namaPerusahaan }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Custom Search Bar with Clear Button -->
                <div class="col-md-5 col-lg-4 ms-auto">
                    <label class="form-label small fw-bold text-secondary mb-1">Cari Mahasiswa:</label>
                    <div class="search-container">
                        <input type="text" id="customSearch" class="form-control form-control-sm rounded-3 pe-4" placeholder="Ketik nama atau NPM...">
                        <button type="button" id="btnClearSearch" class="search-clear"><i class="fa-solid fa-circle-xmark"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tabelNilaiMagang" class="table table-hover align-middle mb-0 w-100">
                    <thead class="bg-light text-secondary small fw-bold text-uppercase border-bottom">
                        <tr>
                            <!-- Kolom tersembunyi untuk grouping di DataTables -->
                            <th class="d-none">Perusahaan Utama</th>
                            <th class="d-none">Sub Cabang</th>
                            <th class="ps-4 py-3" style="width: 15%;">NPM</th>
                            <th class="py-3" style="width: 25%;">Nama Mahasiswa</th>
                            <th class="py-3" style="width: 20%;">Program</th>
                            <th class="text-center py-3" style="width: 15%;">Total Nilai</th>
                            <th class="text-center py-3" style="width: 15%;">Status</th>
                            <th class="text-center pe-4 py-3" style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($daftarNilai as $row)
                            <tr class="border-bottom">
                                <!-- Data grouping -->
                                <td class="d-none">{{ $row->namaPerusahaan }}</td>
                                <td class="d-none">{{ $row->anakCabang ?? 'Pusat / Departemen Utama' }}</td>
                                
                                <td class="ps-4 fw-semibold text-dark">{{ $row->npm }}</td>
                                <td class="fw-bold text-secondary">{{ $row->nama }}</td>
                                <td>{{ $row->prodi }}</td>
                                <td class="text-center fw-bold fs-6" data-order="{{ $row->totalNilai }}">
                                    @if($row->totalNilai > 0)
                                        <span class="text-success">{{ number_format($row->totalNilai, 2) }}</span>
                                    @else
                                        <span class="text-muted font-monospace">-</span>
                                    @endif
                                </td>
                                <td class="text-center" data-search="{{ $row->totalNilai > 0 ? 'Dinilai' : 'Kosong' }}">
                                    @if($row->totalNilai > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle badge-nilai rounded-pill">
                                            <i class="fa-solid fa-circle-check me-1"></i> Dinilai
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle badge-nilai rounded-pill">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Kosong
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm rounded-3 overflow-hidden" role="group">
                                        <button type="button" class="btn btn-sm btn-primary btn-input-nilai px-3" data-id="{{ $row->id }}" style="font-size: 0.75rem;">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        @if($row->totalNilai > 0)
                                            <form action="{{ route('admin.nilai-magang.destroy', $row->id) }}" method="POST" class="form-reset-nilai d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-reset px-2 border-start-0" style="font-size: 0.75rem; border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                                    <i class="fa-solid fa-rotate-left"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <!-- Fallback data kosong dikontrol oleh konfigurasi DataTables -->
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL FORM INPUT & UBAH NILAI ==================== -->
<div class="modal fade" id="modalNilai" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalNilaiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="modalNilaiLabel" style="font-size: 1rem;">
                    <i class="fa-solid fa-star text-warning me-2"></i>Form Matriks Komponen Nilai Magang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="box-shadow: none;"></button>
            </div>
            <form id="formPenilaian" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-3">
                    
                    <!-- Detail Informasi Mahasiswa Target -->
                    <div class="bg-light p-3 rounded-3 border mb-3 shadow-sm small">
                        <div class="row">
                            <div class="col-sm-6 mb-2 mb-sm-0">
                                <span class="text-muted d-block small">Mahasiswa</span>
                                <span id="infoMahasiswa" class="fw-bold text-dark"></span>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <span class="text-muted d-block small">Lokasi Instansi</span>
                                <span id="infoPerusahaan" class="fw-bold text-primary"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Pilihan Format Penilaian -->
                    <div class="card border border-primary-subtle bg-light mb-3">
                        <div class="card-body py-2 px-3">
                            <label class="form-label small fw-bold text-secondary d-block mb-1"><i class="fa-solid fa-sliders me-1"></i> Pilih Opsi Format Evaluasi:</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format_penilaian" id="formatPT" value="pt" checked>
                                    <label class="form-check-input-label small fw-bold text-dark" for="formatPT">
                                        Format PT (Akumulasi Poin Kriteria)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format_penilaian" id="formatPerusahaan" value="perusahaan">
                                    <label class="form-check-input-label small fw-bold text-dark" for="formatPerusahaan">
                                        Format Perusahaan (Input Skor / Rata-rata)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Matriks Tabel Penilaian (11 Kolom) -->
                    <div class="table-responsive border rounded-3 shadow-sm">
                        <table class="table table-bordered align-middle text-center mb-0 small" id="tabelMatriksNilai">
                            <thead class="bg-light fw-bold text-secondary">
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 30%; text-align: left;" class="ps-3">Aspek Penilaian</th>
                                    <th style="width: 5%;">10</th>
                                    <th style="width: 5%;">9</th>
                                    <th style="width: 5%;">8</th>
                                    <th style="width: 5%;">7</th>
                                    <th style="width: 5%;">6</th>
                                    <th style="width: 5%;">5</th>
                                    <th style="width: 5%;">4</th>
                                    <th style="width: 5%;">3</th>
                                    <th style="width: 15%;">Skor Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $aspek = [
                                        'etika' => '1. Etika',
                                        'disiplin' => '2. Disiplin',
                                        'percayaDiri' => '3. Percaya Diri',
                                        'kerjaSama' => '4. Kerja Sama',
                                        'motivasi' => '5. Motivasi',
                                        'inisiatifKerja' => '6. Inisiatif Kerja',
                                        'loyalitas' => '7. Loyalitas',
                                        'tanggungJawab' => '8. Tanggung Jawab',
                                        'pemahaman' => '9. Pemahaman Tugas',
                                        'PtigaK' => '10. K3 (Kesehatan & Keselamatan)'
                                    ];
                                    $no = 1;
                                @endphp

                                @foreach($aspek as $field => $label)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $no++ }}</td>
                                    <td style="text-align: left;" class="fw-semibold text-dark ps-3">{{ substr($label, 3) }}</td>
                                    @for($i = 10; $i >= 3; $i--)
                                    <td>
                                        <input type="radio" name="radio_{{ $field }}" value="{{ $i }}" class="form-check-input radio-skor" data-field="{{ $field }}">
                                    </td>
                                    @endfor
                                    <td>
                                        <input type="number" name="{{ $field }}" id="val_{{ $field }}" class="form-control form-control-sm text-center fw-bold input-skor-akhir" min="0" max="100" step="0.01" required>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end pe-3 fs-6" id="labelTotalAkhir">TOTAL NILAI AKHIR :</td>
                                    <td colspan="8" class="bg-light"></td>
                                    <td class="text-center fs-5 text-primary" id="liveTotalNilai">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-sm btn-secondary px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 rounded-3"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Penilaian</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Menggunakan Aset Lokal pada direktori public/ -->
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.rowGroup.min.js') }}"></script>
    
    <script>
        $(document).ready(function() {

            // Menangani Session Flash Message Sistem
            @if(session('success'))
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{!! session('success') !!}", confirmButtonColor: '#0d6efd' });
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: 'Proses Gagal!', text: "{!! session('error') !!}", confirmButtonColor: '#dc3545' });
            @endif

            // ==================== INISIALISASI DATATABLES (ROW GROUPING & PAGINASI 15) ====================
            var table = $('#tabelNilaiMagang').DataTable({
                order: [[0, 'asc'], [1, 'asc']], // Urutkan berdasarkan Perusahaan Utama, kemudian Sub Cabang
                columnDefs: [
                    { orderable: false, targets: [5, 6, 7] } // Menonaktifkan sorting pada kolom nilai, status, dan aksi
                ],
                dom: 'rtip', // Menggunakan kustom kontrol eksternal
                pageLength: 15, // Paginasi disetel ke 15 baris data per halaman
                rowGroup: {
                    dataSrc: [0, 1], // Group Level 0: Perusahaan Utama, Group Level 1: Sub Cabang
                    startRender: function (rows, group, level) {
                        if (level === 1 && group === 'Pusat / Departemen Utama') {
                            return null; // Tidak menampilkan header cabang jika merupakan pusat/utama
                        }
                        
                        var icon = level === 0 
                            ? '<i class="fa-solid fa-building text-primary me-2"></i>' 
                            : '<i class="fa-solid fa-folder-tree text-secondary me-2 ms-3"></i>';
                        
                        var label = level === 0 ? group : 'Cabang/Divisi: ' + group;
                        
                        return $('<tr/>')
                            .append('<th colspan="6">' + icon + label + ' (' + rows.count() + ' Mahasiswa)</th>');
                    }
                },
                language: {
                    emptyTable: "Belum ada data penempatan magang mahasiswa aktif pada periode ini.",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data mahasiswa",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data mahasiswa",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Data pencarian tidak ditemukan.",
                    paginate: {
                        next: "<i class='fa-solid fa-chevron-right'></i>",
                        previous: "<i class='fa-solid fa-chevron-left'></i>"
                    }
                }
            });

            // ==================== FITUR CUSTOM FILTER & SEARCH ====================
            
            // 1. Kotak Pencarian Kustom & Tombol Clear
            $('#customSearch').on('keyup input', function() {
                var val = $(this).val();
                table.search(val).draw();
                
                // Tampilkan/sembunyikan tombol clear
                if (val.length > 0) {
                    $('#btnClearSearch').show();
                } else {
                    $('#btnClearSearch').hide();
                }
            });

            $('#btnClearSearch').on('click', function() {
                $('#customSearch').val('').trigger('input');
                table.search('').draw();
            });

            // 2. Filter Berdasarkan Perusahaan
            $('#filterPerusahaan').on('change', function() {
                var val = $(this).val();
                table.column(0).search(val ? '^' + val + '$' : '', true, false).draw();
            });


            // ==================== LOGIKA MATRIKS PENILAIAN MODAL ====================
            var fields = ['etika', 'disiplin', 'percayaDiri', 'kerjaSama', 'motivasi', 'inisiatifKerja', 'loyalitas', 'tanggungJawab', 'pemahaman', 'PtigaK'];

            function kalkulasiNilaiMatriks() {
                var format = $('input[name="format_penilaian"]:checked').val();
                var total = 0;
                var count = 0;

                if (format === 'pt') {
                    // Aktifkan radio button, kunci input manual (readonly)
                    $('.radio-skor').prop('disabled', false);
                    $('.input-skor-akhir').prop('readonly', true).css('background-color', '#e9ecef');
                    $('#labelTotalAkhir').text('TOTAL NILAI AKHIR (AKUMULASI PT) :');

                    // Iterasi setiap aspek penilaian
                    fields.forEach(function(f) {
                        // Ambil nilai radio button yang sedang di-check untuk aspek ini
                        var checkedRadio = $('input[name="radio_' + f + '"]:checked');
                        var val = 0;

                        if (checkedRadio.length > 0) {
                            val = parseFloat(checkedRadio.val());
                        }

                        // Isi kolom input-skor-akhir agar datanya ikut tersubmit
                        $('#val_' + f).val(val);
                        total += val;
                    });

                    // Tampilkan total penjumlahan murni dari radio button ke footer tabel
                    $('#liveTotalNilai').text(total.toFixed(2));

                } else {
                    // Format Perusahaan: Matikan radio button, buka input manual skor 0-100
                    $('.radio-skor').prop('disabled', true);
                    $('.input-skor-akhir').prop('readonly', false).css('background-color', '#fff');
                    $('#labelTotalAkhir').text('RATA-RATA NILAI AKHIR :');

                    fields.forEach(function(f) {
                        var val = parseFloat($('#val_' + f).val());
                        if (!isNaN(val)) {
                            total += val;
                            count++;
                        }
                    });
                    var rataRata = count > 0 ? (total / count) : 0;
                    $('#liveTotalNilai').text(rataRata.toFixed(2));
                }
            }

            // Event handler ketika user memilih skor di radio button (Khusus Format PT)
            $(document).on('change', '.radio-skor', function() {
                if ($('input[name="format_penilaian"]:checked').val() === 'pt') {
                    kalkulasiNilaiMatriks();
                }
            });

            // Event handler ketika user mengubah nilai angka secara manual (Khusus Format Perusahaan)
            $(document).on('input', '.input-skor-akhir', function() {
                var format = $('input[name="format_penilaian"]:checked').val();
                if (format === 'perusahaan') {
                    var value = parseFloat($(this).val());
                    if (value > 100) $(this).val(100);
                    if (value < 0 || isNaN(value)) $(this).val('');
                    kalkulasiNilaiMatriks();
                }
            });

            $(document).on('focus', '.input-skor-akhir', function() {
                var format = $('input[name="format_penilaian"]:checked').val();
                if (format === 'perusahaan') {
                    var val = parseFloat($(this).val());
                    if (val === 0 || isNaN(val)) {
                        $(this).val(''); 
                    }
                }
            });

            $(document).on('blur', '.input-skor-akhir', function() {
                if ($(this).val() === '') {
                    $(this).val(0);
                    kalkulasiNilaiMatriks();
                }
            });

            // Event handler saat memindahkan pilihan opsi Format Evaluasi
            $('input[name="format_penilaian"]').on('change', function() {
                if ($(this).val() === 'perusahaan') {
                    // Bersihkan semua centang radio button jika pindah ke format perusahaan
                    $('.radio-skor').prop('checked', false);
                    fields.forEach(function(f) {
                        var currentVal = parseFloat($('#val_' + f).val());
                        // Jika nilainya di bawah atau sama dengan 10 (bekas format PT), bersihkan untuk input ulang
                        if (currentVal <= 10 || isNaN(currentVal)) {
                            $('#val_' + f).val('');
                        }
                    });
                } else {
                    // Jika kembali ke format PT, sesuaikan nilai angka ke bentuk radio button
                    fields.forEach(function(f) {
                        var currentVal = parseFloat($('#val_' + f).val());
                        if (!isNaN(currentVal) && currentVal >= 3 && currentVal <= 10 && Number.isInteger(currentVal)) {
                            $('input[name="radio_' + f + '"][value="' + parseInt(currentVal) + '"]').prop('checked', true);
                        } else {
                            $('#val_' + f).val(0);
                        }
                    });
                }
                kalkulasiNilaiMatriks();
            });

            // AJAX Ambil Data Penilaian untuk Modal
            $('.btn-input-nilai').on('click', function() {
                var id = $(this).data('id');
                var urlEdit = "{{ route('admin.nilai-magang.edit', ':id') }}".replace(':id', id);
                var urlUpdate = "{{ route('admin.nilai-magang.update', ':id') }}".replace(':id', id);

                $('#formPenilaian').attr('action', urlUpdate);
                $('.radio-skor').prop('checked', false); 

                $.ajax({
                    url: urlEdit,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#infoMahasiswa').text(data.npm + ' - ' + data.nama);
                        $('#infoPerusahaan').text(data.namaPerusahaan + (data.anakCabang ? ' ('+data.anakCabang+')' : ''));

                        fields.forEach(function(f) {
                            var dbValue = data[f] ? data[f] : 0;
                            $('#val_' + f).val(dbValue);

                            if(dbValue >= 3 && dbValue <= 10 && Number.isInteger(parseFloat(dbValue))) {
                                $('input[name="radio_' + f + '"][value="' + parseInt(dbValue) + '"]').prop('checked', true);
                            }
                        });

                        if (data.etika > 10 || data.disiplin > 10) {
                            $('#formatPerusahaan').prop('checked', true);
                            fields.forEach(function(f) {
                                if (parseFloat($('#val_' + f).val()) === 0) $('#val_' + f).val('');
                            });
                        } else {
                            $('#formatPT').prop('checked', true);
                        }

                        kalkulasiNilaiMatriks();

                        var myModal = new bootstrap.Modal(document.getElementById('modalNilai'));
                        myModal.show();
                    },
                    error: function(xhr) {
                        Swal.fire({ icon: 'error', title: 'Koneksi Bermasalah', text: 'Gagal mengambil data dari server.', confirmButtonColor: '#dc3545' });
                    }
                });
            });

            // Konfirmasi Sebelum Melakukan Reset Data Keadaan Kosong
            $(document).on('click', '.btn-reset', function(e) {
                e.preventDefault();
                var currentForm = $(this).closest('form');
                Swal.fire({
                    title: 'Kosongkan Nilai?',
                    text: "Komponen nilai magang mahasiswa terpilih akan di-reset total.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Kosongkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) { currentForm.submit(); }
                });
            });
        });
    </script>
@endsection