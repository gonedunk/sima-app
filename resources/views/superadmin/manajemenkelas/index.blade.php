@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">

<style>
    .custom-search-box { max-width: 350px; }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Manajemen Data Kelas</h3>
            <small class="text-muted">Kelola data pada tabel <code>tbkelas</code></small>
        </div>
        <div>
            <button type="button" class="btn btn-primary px-4 shadow-sm" onclick="bukaModalTambah()">
                <i class="fas fa-plus me-2"></i> Tambah Kelas Baru
            </button>
        </div>
    </div>

    <!-- Panel Kontrol Tabel (Form Pencarian Native Server-Side) -->
    <div class="card mb-3 border-0 bg-transparent">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                Menampilkan {{ $all_kelas->count() }} data dari total {{ $all_kelas->total() }} data
            </div>
            
            <!-- Form Pencarian -->
            <form action="{{ route('kelas.index') }}" method="GET" id="form-pencarian">
                <div class="input-group custom-search-box shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                    <!-- Input otomatis mempertahankan teks yang dicari -->
                    <input type="text" name="cari" id="dt-pencarian" class="form-control border-start-0 border-end-0" placeholder="Cari nama kelas, prodi..." value="{{ $cari ?? '' }}">
                    
                    @if(!empty($cari))
                        <!-- Tombol Clear Search hanya muncul jika ada text pencarian -->
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button" id="btn-clear-search" title="Bersihkan Pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Tampilan Data -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light py-3">
            <h6 class="mb-0 fw-bold text-secondary">Daftar Kelas Terdaftar</h6>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-striped table-hover align-middle mb-0 w-100">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;" class="text-center">No</th>
                        <th>Nama Kelas</th>
                        <th>Program</th>
                        <th>Program Studi</th>
                        <th>Jurusan</th>
                        <th style="width: 150px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($all_kelas as $index => $k)
                        <tr>
                            <!-- Penomoran dinamis berdasarkan halaman pagination Laravel -->
                            <td class="text-center text-muted fw-bold">{{ $all_kelas->firstItem() + $index }}</td>
                            <td><span class="badge bg-light text-dark border fw-bold fs-6">{{ $k->namaKelas }}</span></td>
                            <td><small class="text-muted">({{ $k->kodeProgram }})</small> {{ $k->namaProgram ?? '-' }}</td>
                            <td><small class="text-muted">({{ $k->kodeProdi }})</small> {{ $k->namaProdi ?? '-' }}</td>
                            <td><small class="text-muted">({{ $k->kodeJurusan }})</small> {{ $k->namaJurusan ?? '-' }}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning me-1" onclick="bukaModalEdit({{ json_encode($k) }})">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="konfirmasiHapus({{ $k->id }})">
                                    Hapus
                                </button>
                                <form id="form-hapus-{{ $k->id }}" action="{{ route('kelas.destroy', $k->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Tidak ditemukan data kelas yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Cetak Navigasi Halaman Paginate (15 Data) -->
        <div class="card-footer bg-white d-flex justify-content-center py-3">
            {{ $all_kelas->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- MODAL FORM (TAMBAH / EDIT) -->
<div class="modal fade" id="modalKelas" tabindex="-1" aria-labelledby="modalKelasTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="modal-header-bg">
                <h5 class="modal-title text-white" id="modalKelasTitle">Form Kelas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="formKelas">
                @csrf
                <div id="method-container"></div>
                
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Kelas</label>
                            <input type="text" name="namaKelas" id="namaKelas" class="form-control" placeholder="Misal: 1A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program</label>
                            <select name="kodeProgram" id="kodeProgram" class="form-control select2-modal" required style="width: 100%">
                                <option value=""></option>
                                @foreach($all_program as $program)
                                    <option value="{{ $program->kodeProgram }}">{{ $program->namaProgram }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program Studi</label>
                            <select name="kodeProdi" id="kodeProdi" class="form-control select2-modal" style="width: 100%">
                                <option value=""></option>
                                @foreach($all_prodi as $prodi)
                                    <option value="{{ $prodi->kodeProdi }}">{{ $prodi->namaProdi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jurusan</label>
                            <select name="kodeJurusan" id="kodeJurusan" class="form-control select2-modal" style="width: 100%">
                                <option value=""></option>
                                @foreach($all_jurusan as $jurusan)
                                    <option value="{{ $jurusan->kodeJurusan }}">{{ $jurusan->namaJurusan }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn px-4" id="btn-submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
$(document).ready(function() {
    
    // Fitur pencarian otomatis: Mengetik memicu submit pencarian (kasih jeda/debounce sedikit agar ketikan nyaman)
    let mengetikTimer;
    $('#dt-pencarian').on('keyup input', function() {
        clearTimeout(mengetikTimer);
        mengetikTimer = setTimeout(function() {
            $('#form-pencarian').submit();
        }, 700); // submit otomatis setelah 0.7 detik berhenti mengetik
    });

    // Aksi hapus pencarian (Clear Search)
    $('#btn-clear-search').on('click', function() {
        window.location.href = "{{ route('kelas.index') }}";
    });

    // Inisialisasi Select2
    if ($.fn.select2) {
        $('.select2-modal').select2({
            placeholder: "- Pilih Opsi -",
            allowClear: true,
            theme: 'bootstrap-5',
            dropdownParent: $('#modalKelas')
        });
    }

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            showConfirmButton: false,
            timer: 2500
        });
    @endif
});

function bukaModalTambah() {
    document.getElementById('formKelas').reset();
    $('.select2-modal').val('').trigger('change');
    document.getElementById('modalKelasTitle').innerText = 'Tambah Data Kelas Baru';
    document.getElementById('formKelas').action = "{{ route('kelas.store') }}";
    document.getElementById('method-container').innerHTML = '';
    $('#modal-header-bg').removeClass('bg-warning').addClass('bg-primary');
    $('#btn-submit').removeClass('btn-warning').addClass('btn-success').text('Simpan Data');
    $('#modalKelas').modal('show');
}

function bukaModalEdit(data) {
    document.getElementById('formKelas').reset();
    document.getElementById('modalKelasTitle').innerText = 'Ubah Rincian Data Kelas: ' + data.namaKelas;
    document.getElementById('formKelas').action = `/kelas/${data.id}`;
    document.getElementById('method-container').innerHTML = '@method("PUT")';
    document.getElementById('namaKelas').value = data.namaKelas;
    $('#kodeProgram').val(data.kodeProgram).trigger('change');
    $('#kodeProdi').val(data.kodeProdi ?? '').trigger('change');
    $('#kodeJurusan').val(data.kodeJurusan ?? '').trigger('change');
    $('#modal-header-bg').removeClass('bg-primary').addClass('bg-warning');
    $('#btn-submit').removeClass('btn-success').addClass('btn-warning').text('Simpan Perubahan');
    $('#modalKelas').modal('show');
}

function konfirmasiHapus(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data kelas yang dihapus dari tbkelas tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, hapus data!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-hapus-' + id).submit();
        }
    });
}
</script>
@endsection