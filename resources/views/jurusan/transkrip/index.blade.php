<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Transkrip Nilai Mahasiswa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light py-5">

<div class="container" style="max-width: 600px;">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-primary text-white text-center fw-bold py-3">
            <i class="fa-solid fa-file-arrow-up me-2"></i>Form Upload Transkrip Nilai
        </div>
        
        <div class="card-body p-4">
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('transkrip.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Select2 NPM / Nama -->
                <div class="mb-3">
                    <label for="select2Npm" class="form-label fw-semibold">Pilih NPM / Nama Mahasiswa <span class="text-danger">*</span></label>
                    <select id="select2Npm" name="npm" class="form-select" required>
                        <option value="">-- Cari NPM atau Nama --</option>
                        @foreach($mahasiswa as $m)
                            @php
                                $labelProdi = ($m->prodi == '3050') ? 'D3 Akuntansi' : (($m->prodi == '4051') ? 'D4 Akuntansi Sektor Publik' : $m->prodi);
                            @endphp
                            <option value="{{ $m->npm }}" 
                                    data-nama="{{ $m->nama }}" 
                                    data-prodi="{{ $labelProdi }}"
                                    {{ old('npm') == $m->npm ? 'selected' : '' }}>
                                {{ $m->npm }} - {{ $m->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- BOX STATUS UPLOAD TRANSKRIP (AJAX) -->
                <div id="boxStatusUpload" class="mb-3 d-none">
                    <div id="alertStatus" class="alert py-2 px-3 small d-flex align-items-center mb-0">
                        <i id="iconStatus" class="fa-solid me-2 fs-5"></i>
                        <div>
                            <strong id="teksStatus"></strong>
                            <div id="subTeksStatus" class="text-muted" style="font-size: 11px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Auto-fill Nama (Readonly) -->
                <div class="mb-3">
                    <label for="txtNama" class="form-label fw-semibold">Nama Lengkap</label>
                    <input type="text" id="txtNama" class="form-control bg-light" placeholder="Otomatis terisi..." readonly>
                </div>

                <!-- Auto-fill Program Studi (Readonly) -->
                <div class="mb-3">
                    <label for="txtProdi" class="form-label fw-semibold">Program Studi</label>
                    <input type="text" id="txtProdi" class="form-control bg-light" placeholder="Otomatis terisi..." readonly>
                </div>

                <!-- Input File Transkrip -->
                <div class="mb-4">
                    <label for="file_transkrip" class="form-label fw-semibold">File Transkrip <span class="text-danger">*</span></label>
                    <input type="file" name="file_transkrip" id="file_transkrip" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    <small class="text-muted" id="helpTextFile">Format yang diizinkan: PDF, JPG, PNG (Maksimal 2 MB)</small>
                </div>

                <button type="submit" id="btnSubmit" class="btn btn-primary w-100 fw-bold py-2">
                    <i class="fa-solid fa-paper-plane me-1"></i> Kirim Transkrip
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#select2Npm').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Cari NPM atau Nama --',
            width: '100%'
        });

        $('#select2Npm').on('change select2:select', function() {
            let npm = $(this).val();
            let selectedOption = $(this).find(':selected');
            let nama = selectedOption.data('nama') || '';
            let prodi = selectedOption.data('prodi') || '';

            $('#txtNama').val(nama);
            $('#txtProdi').val(prodi);

            if (npm) {
                // Panggil AJAX untuk Cek Status Upload Transkrip
                $.ajax({
                    url: "{{ route('transkrip.cek-status') }}",
                    type: "GET",
                    data: { npm: npm },
                    success: function(response) {
                        $('#boxStatusUpload').removeClass('d-none');
                        
                        if (response.sudah_upload) {
                            $('#alertStatus').removeClass('alert-warning alert-secondary').addClass('alert-success');
                            $('#iconStatus').removeClass().addClass('fa-solid fa-circle-check text-success me-2 fs-5');
                            $('#teksStatus').text('Mahasiswa Sudah Mengunggah Transkrip');
                            $('#subTeksStatus').html('Berkas: <b>' + response.nama_file + '</b> (' + response.tanggal + ')<br><span class="text-danger">*Mengunggah lagi akan menimpa berkas lama.</span>');
                            $('#btnSubmit').html('<i class="fa-solid fa-rotate me-1"></i> Update Transkrip Baru');
                        } else {
                            $('#alertStatus').removeClass('alert-success alert-warning').addClass('alert-secondary');
                            $('#iconStatus').removeClass().addClass('fa-solid fa-circle-info text-secondary me-2 fs-5');
                            $('#teksStatus').text('Mahasiswa Belum Mengunggah Transkrip');
                            $('#subTeksStatus').text('Silakan pilih file dan klik tombol Kirim Transkrip di bawah.');
                            $('#btnSubmit').html('<i class="fa-solid fa-paper-plane me-1"></i> Kirim Transkrip');
                        }
                    }
                });
            } else {
                $('#boxStatusUpload').addClass('d-none');
            }
        });

        if ($('#select2Npm').val()) {
            $('#select2Npm').trigger('change');
        }
    });
</script>
</body>
</html>