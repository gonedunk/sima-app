@extends('layouts.app') {{-- Sesuaikan dengan nama layout utama Anda --}}

@section('content')
<div class="container-fluid pull-up">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-print mr-2"></i> Form Cetak Universal Laporan
                    </h5>
                </div>
                <div class="card-body">

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('rekap-universal.proses') }}" method="POST" target="_blank">
    @csrf

                        <!-- 1. PILIH JENIS LAPORAN -->
                        <div class="form-group mb-3">
                            <label for="jenis_laporan" class="font-weight-bold">Jenis Laporan <span class="text-danger">*</span></label>
                            <select name="jenis_laporan" id="jenis_laporan" class="form-control select2" required>
                                <option value="">-- Pilih Jenis Laporan --</option>
                                <option value="stok_opname">Laporan Sirkulasi Stok Opname</option>
<option value="mahasiswa_yudisium">Keadaan Mahasiswa Yudisium</option>
<option value="rekap_mahasiswa">Rekap Mahasiswa Perkelas</option>
                            </select>
                        </div>

                        <!-- 2. OPSI TANGGAL / FILTER -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="opsi_cetak" class="font-weight-bold">Opsi Filter Data</label>
                                    <select name="opsi_cetak" id="opsi_cetak" class="form-control select2">
                                        <option value="semua" selected>Semua Data</option>
                                        <option value="filter">Berdasarkan Rentang Tanggal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 wrapper-tanggal d-none">
                                <div class="form-group mb-3">
                                    <label for="tgl_mulai" class="font-weight-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4 wrapper-tanggal d-none">
                                <div class="form-group mb-3">
                                    <label for="tgl_selesai" class="font-weight-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                                    <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- 3. KONFIGURASI TANDA TANGAN -->
                        <h6 class="font-weight-bold text-secondary mb-3">
                            <i class="fas fa-signature mr-1"></i> Pengaturan Tanda Tangan Laporan
                        </h6>

                        <div class="row">
                            <!-- MODE TTD -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="mode_ttd" class="font-weight-bold">Mode Tanda Tangan</label>
                                    <select name="mode_ttd" id="mode_ttd" class="form-control select2">
                                        <option value="none">Tanpa Tanda Tangan</option>
                                        <option value="single" selected>Single Tanda Tangan (Kanan)</option>
                                        <option value="dual">Dual Tanda Tangan (Kiri & Kanan)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- JENIS TTD (MANUAL / DIGITAL QR) -->
                            <div class="col-md-4" id="wrapper_jenis_ttd">
                                <div class="form-group mb-3">
                                    <label for="jenis_ttd" class="font-weight-bold">Jenis Tanda Tangan</label>
                                    <select name="jenis_ttd" id="jenis_ttd" class="form-control select2">
                                        <option value="manual" selected>Manual (Cetak & TTD Basah)</option>
                                        <option value="qr">Digital (QR Code)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- DROPDOWN TTD KIRI (PIMPINAN POLSRI) -->
                            <div class="col-md-6 d-none" id="wrapper_ttd_kiri">
                                <div class="form-group mb-3">
                                    <label for="pengelola_kiri_id" class="font-weight-bold">Pimpinan Polsri (TTD Kiri)</label>
                                    <select name="pengelola_kiri_id" id="pengelola_kiri_id" class="form-control select2">
                                        <option value="">-- Pilih Pimpinan (Kiri) --</option>
                                        @foreach($pimpinanList as $pimpinan)
                                            <option value="{{ $pimpinan->id }}">
                                                {{ $pimpinan->nama }} - {{ $pimpinan->jabatan }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- DROPDOWN TTD KANAN (PENGELOLA JURUSAN AKUNTAI) -->
                            <div class="col-md-6" id="wrapper_ttd_kanan">
                                <div class="form-group mb-3">
                                    <label for="pengelola_kanan_id" class="font-weight-bold">Pengelola Jurusan (TTD Kanan)</label>
                                    <select name="pengelola_kanan_id" id="pengelola_kanan_id" class="form-control select2">
                                        <option value="">-- Pilih Pengelola Jurusan (Kanan) --</option>
                                        @foreach($pengelolaList as $pengelola)
                                            <option value="{{ $pengelola->id }}">
                                                {{ $pengelola->nama }} - {{ $pengelola->jabatan }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL SUBMIT -->
                        <div class="form-group mt-4 text-right">
                            <button type="reset" class="btn btn-secondary mr-2">
                                <i class="fas fa-undo mr-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-pdf mr-1"></i> Cetak Laporan PDF
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Inisialisasi Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Toggle Filter Tanggal
        $('#opsi_cetak').on('change', function() {
            var opsi = $(this).val();
            if (opsi === 'filter') {
                $('.wrapper-tanggal').removeClass('d-none').show();
                $('#tgl_mulai, #tgl_selesai').prop('required', true);
            } else {
                $('.wrapper-tanggal').addClass('d-none').hide();
                $('#tgl_mulai, #tgl_selesai').prop('required', false).val('');
            }
        });

        // Toggle Dynamic Layout Mode TTD
        $('#mode_ttd').on('change', function() {
            var mode = $(this).val();

            if (mode === 'none') {
                $('#wrapper_ttd_kanan, #wrapper_ttd_kiri, #wrapper_jenis_ttd').addClass('d-none').hide();
                $('#pengelola_kanan_id, #pengelola_kiri_id').prop('required', false).val('').trigger('change');
            } else if (mode === 'single') {
                $('#wrapper_ttd_kanan, #wrapper_jenis_ttd').removeClass('d-none').show();
                $('#wrapper_ttd_kiri').addClass('d-none').hide();
                $('#pengelola_kanan_id').prop('required', true);
                $('#pengelola_kiri_id').prop('required', false).val('').trigger('change');
            } else if (mode === 'dual') {
                $('#wrapper_ttd_kanan, #wrapper_ttd_kiri, #wrapper_jenis_ttd').removeClass('d-none').show();
                $('#pengelola_kanan_id, #pengelola_kiri_id').prop('required', true);
            }
        });

        // Trigger awal pada saat halaman dimuat
        $('#opsi_cetak').trigger('change');
        $('#mode_ttd').trigger('change');
    });
</script>
@endsection