@extends('layouts.app')

@section('title', 'Kelola Penanda Tangan & Cetak Laporan')

@push('styles')
    <!-- CSS Dependencies dari Folder Public -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">

    <style>
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
        }
        .badge-aktif { background-color: #28a745; color: #fff; }
        .badge-nonaktif { background-color: #dc3545; color: #fff; }
    </style>
@endpush

@section('content')
<div class="container-fluid pt-3">

    <!-- ========================================================================= -->
    <!-- 1. PANEL KONFIRMASI CETAK REKAP BULANAN (Muncul jika ada parameter tanggal) -->
    <!-- ========================================================================= -->
    @if(request()->has('tanggal_awal') && request()->has('tanggal_akhir'))
    <div class="card border-danger shadow mb-4">
        <div class="card-header bg-danger text-white font-weight-bold d-flex align-items-center">
            <i class="fas fa-file-pdf mr-2"></i> Konfirmasi Penanda Tangan Laporan Rekap Bulanan
        </div>
        <div class="card-body bg-light">
            <form action="{{ route('tandatangan.cetak-pdf') }}" method="POST" target="_blank">
                @csrf
                <!-- Parameter Tanggal Utama -->
                <input type="hidden" name="tanggal_awal" value="{{ $tanggal_awal }}">
                <input type="hidden" name="tanggal_akhir" value="{{ $tanggal_akhir }}">

                <!-- Parameter Jenis Jam (KJP1 / KJP2 / Normal) -->
                <input type="hidden" name="jenis_jam" value="{{ request('jenis_jam', 'normal') }}">

                <!-- ========================================================================= -->
                <!-- PENANGKAP ARRAY NIP TERCENTANG DARI HALAMAN REKAP LEMBUR                  -->
                <!-- ========================================================================= -->
                @php
                    $nipSelected = request('nip_pilihan') 
                                    ?? request('nip_kjp2') 
                                    ?? request('selected_nip') 
                                    ?? request('nip') 
                                    ?? [];

                    if (is_string($nipSelected)) {
                        $nipSelected = array_filter(explode(',', $nipSelected));
                    }
                @endphp

                <!-- Oper setiap NIP yang tercentang ke Form Hidden Input -->
                @if(!empty($nipSelected) && is_array($nipSelected))
                    @foreach($nipSelected as $nip)
                        <input type="hidden" name="nip_pilihan[]" value="{{ $nip }}">
                    @endforeach
                @endif

                <div class="row align-items-end">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="font-weight-bold text-dark">Periode Rekap Laporan</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" class="form-control bg-white font-weight-bold text-secondary" 
                                   value="{{ date('d/m/Y', strtotime($tanggal_awal)) }} s/d {{ date('d/m/Y', strtotime($tanggal_akhir)) }}" readonly>
                        </div>
                        @if(!empty($nipSelected))
                            <small class="text-success font-weight-bold mt-1 d-block">
                                <i class="fas fa-check-circle mr-1"></i> {{ count($nipSelected) }} Pegawai Terpilih Dicentang
                            </small>
                        @endif
                    </div>

                    <div class="col-md-5 mb-3 mb-md-0">
                        <label class="font-weight-bold text-dark">Pilih Pejabat Penanda Tangan <span class="text-danger">*</span></label>
                        <select name="pengelola_id" class="form-control select2-cetak" required>
                            <option value="">-- Pilih Pejabat Penanda Tangan --</option>
                            @foreach($pengelolaList as $p)
                                <option value="{{ $p->id }}" {{ strpos(strtolower($p->jabatan), 'ketua jurusan') !== false ? 'selected' : '' }}>
                                    {{ $p->jabatan }} - {{ $p->nama_pengelola }} (NIP. {{ $p->nip }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-danger btn-block font-weight-bold shadow-sm">
                            <i class="fas fa-print mr-1"></i> Cetak Rekap Bulanan (PDF)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- 2. TABEL MASTER DATA PENANDA TANGAN (TBPENGELOLAJURUSAN)                  -->
    <!-- ========================================================================= -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="m-0 font-weight-bold text-primary">Master Data Penanda Tangan</h4>
            <small class="text-muted">Kelola daftar pengelola jurusan / pejabat penanda tangan dokumen resmi</small>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" onclick="btnTambahData()">
            <i class="fas fa-plus mr-1"></i> Tambah Penanda Tangan
        </button>
    </div>

    <!-- Card Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tableTandatangan" width="100%" cellspacing="0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>NIP</th>
                            <th>Nama Penanda Tangan</th>
                            <th>Jabatan</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th width="10%">Status</th>
                            <th width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pengelolaList as $index => $row)
                        @php
                            $today = date('Y-m-d');
                            $isAktif = ($row->tanggalMulai <= $today) && (is_null($row->tanggalSelesai) || $row->tanggalSelesai >= $today);
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center"><code>{{ $row->nip }}</code></td>
                            <td class="font-weight-bold">{{ $row->nama_pengelola ?? '-' }}</td>
                            <td><span class="badge badge-info">{{ $row->jabatan }}</span></td>
                            <td class="text-center">{{ date('d/m/Y', strtotime($row->tanggalMulai)) }}</td>
                            <td class="text-center">{{ $row->tanggalSelesai ? date('d/m/Y', strtotime($row->tanggalSelesai)) : '-' }}</td>
                            <td class="text-center">
                                @if($isAktif)
                                    <span class="badge badge-aktif px-2 py-1">Aktif</span>
                                @else
                                    <span class="badge badge-nonaktif px-2 py-1">Selesai</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning" title="Edit Data" onclick="btnEditData({{ json_encode($row) }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <form action="{{ route('tandatangan.destroy', $row->id) }}" method="POST" class="d-inline form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. MODAL FORM INPUT / EDIT DATA                                            -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalTandatangan" tabindex="-1" role="dialog" aria-labelledby="modalTandatanganLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="modalTandatanganLabel">Form Penanda Tangan</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form id="formTandatangan" method="POST" action="{{ route('tandatangan.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="id" id="tandatangan_id">

                <div class="modal-body">
                    <!-- Select Dosen / Pegawai (NIP) -->
                    <div class="form-group">
                        <label for="nip" class="font-weight-bold">Pegawai / Dosen (NIP) <span class="text-danger">*</span></label>
                        <select name="nip" id="nip" class="form-control select2-modal" style="width: 100%;" required>
                            <option value="">-- Pilih Pegawai / Dosen --</option>
                            @foreach($dosenList as $dosen)
                                <option value="{{ $dosen->nip }}">{{ $dosen->nip }} - {{ $dosen->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Jabatan -->
                    <div class="form-group">
                        <label for="jabatan" class="font-weight-bold">Jabatan <span class="text-danger">*</span></label>
                        <select name="jabatan" id="jabatan" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="Ketua Jurusan">Ketua Jurusan</option>
                            <option value="Sekretaris Jurusan">Sekretaris Jurusan</option>
                            <option value="Ketua Program Studi">Ketua Program Studi</option>
                            <option value="Kepala Laboratorium">Kepala Laboratorium</option>
                        </select>
                    </div>

                    <!-- Tanggal Mulai & Tanggal Selesai -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggalMulai" class="font-weight-bold">Tanggal Mulai Jabatan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggalMulai" id="tanggalMulai" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggalSelesai" class="font-weight-bold">Tanggal Selesai Jabatan <small class="text-muted">(Kosongkan jika aktif)</small></label>
                                <input type="date" name="tanggalSelesai" id="tanggalSelesai" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary font-weight-bold" id="btnSimpan">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- JS Dependencies dari Public Folder -->
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            
            // 1. INISIALISASI DATATABLES
            $('#tableTandatangan').DataTable({
                "language": {
                    "url": "{{ asset('assets/plugins/datatables/i18n/Indonesian.json') }}"
                }
            });

            // 2. INISIALISASI SELECT2
            $('.select2-modal').select2({
                theme: 'bootstrap4',
                placeholder: "-- Pilih Pegawai / Dosen --",
                allowClear: true,
                dropdownParent: $('#modalTandatangan')
            });

            $('.select2-cetak').select2({
                theme: 'bootstrap4',
                placeholder: "-- Pilih Pejabat Penanda Tangan --"
            });

            // 3. FLASH SESSION NOTIFIKASI SWEETALERT2
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    timer: 2500,
                    showConfirmButton: false
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#dc3545'
                });
            @endif

            // 4. CONFIRMATION DELETE DENGAN SWEETALERT2
            $(document).on('submit', '.form-delete', function(e) {
                e.preventDefault();
                let form = this;

                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data penanda tangan ini akan dihapus dari sistem!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // HANDLER TOMBOL TAMBAH DATA BARU
        function btnTambahData() {
            $('#formTandatangan').attr('action', "{{ route('tandatangan.store') }}");
            $('#formMethod').val('POST');
            $('#tandatangan_id').val('');
            $('#nip').val('').trigger('change');
            $('#jabatan').val('');
            $('#tanggalMulai').val('');
            $('#tanggalSelesai').val('');
            $('#modalTandatanganLabel').text('Tambah Penanda Tangan Baru');
            $('#modalTandatangan').modal('show');
        }

        // HANDLER TOMBOL EDIT DATA
        function btnEditData(data) {
            let updateUrl = "{{ url('admin/tandatangan') }}/" + data.id;
            
            $('#formTandatangan').attr('action', updateUrl);
            $('#formMethod').val('PUT');
            $('#tandatangan_id').val(data.id);
            $('#nip').val(data.nip).trigger('change');
            $('#jabatan').val(data.jabatan);
            $('#tanggalMulai').val(data.tanggalMulai);
            $('#tanggalSelesai').val(data.tanggalSelesai);
            $('#modalTandatanganLabel').text('Edit Data Penanda Tangan');
            $('#modalTandatangan').modal('show');
        }
    </script>
@endpush