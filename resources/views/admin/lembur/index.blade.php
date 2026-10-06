@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark text-uppercase small tracking-wide">Rekapitulasi Jam Lembur</h4>
            <small class="text-muted">Manajemen aktivitas lembur & kalkulasi jam kerja otomatis per pegawai</small>
        </div>
        <div class="d-flex gap-2">
            <!-- Tombol Unduh Template -->
            <a href="{{ route('lembur.export_template') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-2 shadow-sm fw-bold">
                <i class="bi bi-file-earmark-excel"></i> Download Template
            </a>
            <!-- Tombol Tambah Manual -->
            <button onclick="openModal('add')" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2 shadow-sm fw-bold">
                <span>+ Tambah Rekap Lembur</span>
            </button>
        </div>
    </div>

    <!-- Notifikasi Sukses / Error -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show small shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <!-- Fitur Pencarian Data -->
        <div class="col-md-7">
            <div class="card border border-secondary-subtle shadow-sm h-100">
                <div class="card-body p-3 bg-light d-flex align-items-center">
                    <form action="{{ route('lembur.index') }}" method="GET" class="row g-2 w-100 align-items-center">
                        <div class="col-sm-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control form-control-sm border-start-0 bg-white" 
                                       placeholder="Cari Nama Pegawai / NIP..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-sm-4 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-dark px-3 fw-bold w-100">Cari</button>
                            @if(request('search'))
                                <a href="{{ route('lembur.index') }}" class="btn btn-sm btn-outline-secondary px-3 d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fitur Import Excel -->
        <div class="col-md-5">
            <div class="card border border-secondary-subtle shadow-sm h-100">
                <div class="card-body p-3 bg-light">
                    <form action="{{ route('lembur.import_excel') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-center">
                        @csrf
                        <div class="col-sm-8">
                            <input type="file" name="excel_file" class="form-control form-control-sm bg-white" accept=".xlsx, .xls" required>
                        </div>
                        <div class="col-sm-4">
                            <button type="submit" class="btn btn-sm btn-success fw-bold w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-upload"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Grouped by NIP -->
    <div class="table-responsive rounded border shadow-sm">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-dark text-uppercase small">
                <tr>
                    <th class="text-center py-3" style="width: 15%;">Tanggal / Hari</th>
                    <th class="text-center py-3" style="width: 12%;">Aturan Kerja</th>
                    <th class="text-center py-3">Jam Datang</th>
                    <th class="text-center py-3">Jam Wajib</th>
                    <th class="text-center py-3">Jam Kerja</th>
                    <th class="text-center py-3">Jam Pulang</th>
                    <th class="text-center py-3 bg-warning text-dark">Jam Lembur</th>
                    <th class="py-3" style="width: 25%;">Keterangan Tugas</th>
                    <th class="text-center py-3" style="width: 12%;">Aksi</th>
                </tr>
            </thead>
            <tbody class="small text-dark">
                @php 
                    $currentGroupNip = null; 
                @endphp

                @forelse($dataLembur as $item)
                    @if($currentGroupNip !== $item->nip)
                        @php $currentGroupNip = $item->nip; @endphp
                        <tr class="table-secondary">
                            <td colspan="9" class="py-2.5 ps-3 fw-bold text-dark bg-secondary-subtle border-bottom border-dark-subtle">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="bi bi-person-badge text-primary me-2 fs-6"></i>
                                        <span class="fs-6 fw-bold tracking-wide text-uppercase text-dark">{{ $item->nama_dosen ?? 'Nama tidak ditemukan' }}</span>
                                        <span class="badge bg-dark font-monospace ms-2 px-2 py-1">NIP: {{ $item->nip }}</span>
                                    </div>
                                    <small class="text-muted fst-italic pe-2">Daftar Riwayat Lembur</small>
                                </div>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="text-center text-nowrap fw-medium bg-light-subtle">
                            {{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->isoFormat('dddd, DD-MM-Y') }}
                        </td>
                        <td class="text-center fw-bold text-secondary">
                            {{ $item->aturan ?? '-' }}
                        </td>
                        <td class="text-center font-monospace text-muted">{{ substr($item->jamDatang, 0, 5) }}</td>
                        <td class="text-center font-monospace text-muted">{{ substr($item->jamWajibDatang, 0, 5) }}</td>
                        <td class="text-center font-monospace text-primary fw-bold">{{ substr($item->jamKerja, 0, 5) }}</td>
                        <td class="text-center font-monospace text-muted">{{ substr($item->jamPulang, 0, 5) }}</td>
                        <td class="text-center font-monospace text-danger fw-bold bg-warning-subtle">{{ substr($item->jamLembur, 0, 5) }}</td>
                        <td class="text-muted text-wrap" style="max-width: 250px;" title="{{ $item->keterangan ?? '-' }}">
                            {{ $item->keterangan ?? '-' }}
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button onclick="editData({{ $item->id }})" class="btn btn-sm btn-warning text-dark fw-bold py-1 px-2">
                                    Ubah
                                </button>
                                <form id="delete-form-{{ $item->id }}" action="{{ route('lembur.destroy', $item->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete({{ $item->id }})" class="btn btn-sm btn-danger py-1 px-2">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted bg-white">
                            <i class="bi bi-inbox fs-3 d-block text-secondary mb-2"></i>
                            Belum ada catatan aktivitas lembur di database.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $dataLembur->appends(request()->query())->links() }}
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="lemburModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="lemburModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-dark text-white p-3">
                <h5 class="modal-title fw-bold text-uppercase small" id="lemburModalLabel">Form Data Lembur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="modalForm" method="POST">
                @csrf
                <div id="methodContainer"></div>

                <div class="modal-body p-4 bg-light">
                    <!-- Fitur Pilihan Jenis Hari (Normal / Ramadan) -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-secondary d-block">Jenis Periode Kerja</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jenis_periode" id="periode_normal" value="normal" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="periode_normal">Normal</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jenis_periode" id="periode_ramadan" value="ramadan">
                                <label class="form-check-label small fw-semibold text-dark" for="periode_ramadan">Ramadan</label>
                            </div>
                        </div>
                    </div>

                    <!-- Baris Tanggal -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Tanggal Lembur</label>
                            <input type="date" name="tanggal" id="form_tanggal" required class="form-control form-control-sm bg-white">
                        </div>
                    </div>

                    <!-- Dropdown Rule Jam Kerja -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Aturan Jam Kerja Wajib</label>
                            <select name="jam_kerja_wajib_id" id="form_jam_kerja_wajib" required class="form-select form-select-sm select2-jam-wajib">
                                <option value="" disabled selected>-- Pilih Jam Kerja Wajib --</option>
                                @foreach($daftarJamKerjaWajib as $jkw)
                                    <option value="{{ $jkw->id }}" data-kode="{{ trim($jkw->kodeBulan) }}">
                                        Wajib: {{ substr($jkw->jamDatangWajib, 0, 5) }} s/d {{ substr($jkw->jamPulangWajib, 0, 5) }} 
                                        [Batas Jam Lembur: {{ substr($jkw->jamLemburMaks, 0, 5) }}] ({{ trim($jkw->kodeBulan) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Input Identitas Pegawai -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Nama Pegawai / Dosen</label>
                            <select name="nip" id="form_nip" required class="form-select form-select-sm select2-dosen">
                                <option value="" disabled selected>-- Cari Nama Dosen --</option>
                                @foreach($daftarDosen as $dosen)
                                    <option value="{{ $dosen->nip }}">{{ $dosen->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Jam Datang (Fingerprint)</label>
                            <input type="time" name="jamDatang" id="form_jamDatang" required class="form-control form-control-sm font-monospace bg-white">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Jam Wajib Datang</label>
                            <input type="time" name="jamWajibDatang" id="form_jamWajibDatang" required readonly class="form-control form-control-sm font-monospace bg-secondary-subtle">
                        </div>
                    </div>

                    <!-- Kalkulasi Box -->
                    <div class="row g-2 p-3 bg-white rounded border border-secondary-subtle shadow-sm mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-uppercase text-primary">Total Kerja (Target)</label>
                            <input type="time" name="jamKerja" id="form_jamKerja" required readonly class="form-control form-control-sm font-monospace text-primary border-primary-subtle bg-light fw-bold">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Jam Pulang Riil</label>
                            <input type="time" name="jamPulang" id="form_jamPulang" required class="form-control form-control-sm font-monospace bg-white">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-uppercase text-danger">Durasi Lembur</label>
                            <input type="text" name="jamLembur" id="form_jamLembur" required readonly class="form-control form-control-sm font-monospace text-danger border-danger-subtle fw-bold bg-light">
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Keterangan Lembur</label>
                        <textarea name="keterangan" id="form_keterangan" rows="2" placeholder="Tulis tugas lembur yang diselesaikan..." class="form-control border-secondary-subtle small bg-white"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer bg-white p-3 border-top">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitForm" class="btn btn-sm btn-primary px-4 fw-bold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const b5Modal = new bootstrap.Modal(document.getElementById('lemburModal'));
    const modalTitle = document.getElementById('lemburModalLabel');
    const modalForm = document.getElementById('modalForm');
    const methodContainer = document.getElementById('methodContainer');
    
    // Dataset shift original dari backend Controller
    const masterShift = @json($daftarJamKerjaWajib);
    
    let currentJamPulangWajib = "16:00";
    let currentJamLemburMaks = "19:00"; 

    $(document).ready(function() {
        $('.select2-dosen').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#lemburModal')
        });

        $('.select2-jam-wajib').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#lemburModal')
        });

        // Supaya nilai select2 terkirim pas disubmit meski statusnya disabled/readonly di beberapa case
        $('#modalForm').on('submit', function() {
            $('.select2-jam-wajib').prop('disabled', false);
        });

        $('input[name="jenis_periode"]').on('change', function() {
            evaluasiAturanOtomatis();
        });

        $('#form_tanggal').on('change', function() {
            evaluasiAturanOtomatis();
        });

        $('#form_jam_kerja_wajib').on('change', function() {
            const selectedId = $(this).val();
            if (!selectedId) return;

            const shiftTerpilih = masterShift.find(item => item.id == selectedId);

            if (shiftTerpilih) {
                const formatTime = (timeStr) => timeStr ? timeStr.substring(0, 5) : '';
                
                document.getElementById('form_jamWajibDatang').value = formatTime(shiftTerpilih.jamDatangWajib);
                currentJamPulangWajib = formatTime(shiftTerpilih.jamPulangWajib);
                currentJamLemburMaks = formatTime(shiftTerpilih.jamLemburMaks); 
                
                hitungOtomatisJam();
            }
        });

        $('#form_jamDatang, #form_jamPulang').on('input change', function() {
            hitungOtomatisJam();
        });
    });

    function setFormFieldsDisabledStatus(status) {
        $('input[name="jenis_periode"]').prop('disabled', status);
        $('.select2-dosen').prop('disabled', status).trigger('change.select2');
        $('#form_jamDatang').prop('disabled', status);
        $('#form_jamPulang').prop('disabled', status);
        $('#form_keterangan').prop('disabled', status);
        $('#btnSubmitForm').prop('disabled', status);
        $('.select2-jam-wajib').prop('disabled', status).trigger('change.select2');
    }

    /**
     * Solusi Mutlak Masalah Select2: Membangun ulang isi Option DOM secara real-time
     */
    function evaluasiAturanOtomatis(forcedId = null) {
        const tanggalValue = document.getElementById('form_tanggal').value;
        const jenisPeriode = $('input[name="jenis_periode"]:checked').val();

        if (!tanggalValue) {
            renderSelect2Options([]);
            setFormFieldsDisabledStatus(false);
            return;
        }

        const dateObj = new Date(tanggalValue);
        const dayIndex = dateObj.getDay(); 

        // 1. BLOKIR MINGGU
        if (dayIndex === 0) {
            Swal.fire({
                title: 'Hari Libur!',
                text: 'Tanggal yang Anda pilih adalah hari Minggu (Hari Libur). Pengisian data lembur tidak diizinkan.',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Mengerti'
            });

            renderSelect2Options([]);
            document.getElementById('form_jamWajibDatang').value = '';
            document.getElementById('form_jamKerja').value = '';
            document.getElementById('form_jamLembur').value = '';
            
            setFormFieldsDisabledStatus(true);
            return;
        }

        setFormFieldsDisabledStatus(false);

        // 2. FILTER DATA MASTER BERDASARKAN HARI & PERIODE
        let allowedKodes = [];
        let targetKodeAuto = null;
        let isSelect2Disabled = false;

        if (jenisPeriode === 'normal') {
            isSelect2Disabled = false; // Normal boleh diedit manual / tidak dipungut disabled

            if (dayIndex >= 1 && dayIndex <= 4) {       // Senin - Kamis
                allowedKodes = ['N', 'NK']; 
                targetKodeAuto = 'N'; 
            } else if (dayIndex === 5) {                // Jumat
                allowedKodes = ['NJ', 'NK']; 
                targetKodeAuto = 'NJ';
            } else if (dayIndex === 6) {                // Sabtu
                allowedKodes = ['KS']; 
                targetKodeAuto = 'KS';
            }
        } else if (jenisPeriode === 'ramadan') {
            isSelect2Disabled = true; // Ramadan autolock/disabled sesuai aturan default awal

            if (dayIndex >= 1 && dayIndex <= 4) {
                allowedKodes = ['R'];
                targetKodeAuto = 'R';   
            } else if (dayIndex === 5) {
                allowedKodes = ['RJ'];
                targetKodeAuto = 'RJ';  
            } else if (dayIndex === 6) {
                allowedKodes = ['RS'];
                targetKodeAuto = 'RS';  
            }
        }

        // Saring masterShift dari array JSON sesuai array filter kode di atas
        const filteredShifts = masterShift.filter(shift => {
            const kodeClean = shift.kodeBulan ? shift.kodeBulan.trim() : '';
            return allowedKodes.includes(kodeClean);
        });

        // Terapkan render ulang isi option DOM murni agar bug select2 tuntas hilang
        renderSelect2Options(filteredShifts, isSelect2Disabled);

        // 3. LOGIKA PENENTUAN VALUE TERPILIH
        if (forcedId) {
            // Jika dalam mode editData, pakai ID simpanan database
            $('.select2-jam-wajib').val(forcedId).trigger('change');
        } else if (targetKodeAuto) {
            // Jika mode add data baru, cari ID dari kode auto default
            const match = filteredShifts.find(s => s.kodeBulan.trim() === targetKodeAuto);
            if (match) {
                $('.select2-jam-wajib').val(match.id).trigger('change');
            } else {
                $('.select2-jam-wajib').val('').trigger('change');
            }
        } else {
            $('.select2-jam-wajib').val('').trigger('change');
        }
    }

    /**
     * Helper DOM untuk menghancurkan, mengisi ulang, dan memetakan komponen Jquery Select2
     */
    function renderSelect2Options(shifts, isDisabled = false) {
        const $select = $('#form_jam_kerja_wajib');
        
        // Hancurkan instance select2 sementara
        $select.select2('destroy');
        $select.empty(); // Kosongkan seluruh isi option DOM bawaan

        // Masukkan option default kosong
        $select.append('<option value="" disabled selected>-- Pilih Jam Kerja Wajib --</option>');

        // Isi ulang option DOM murni berdasarkan parameter hasil filter array
        shifts.forEach(jkw => {
            const jamD = jkw.jamDatangWajib ? jkw.jamDatangWajib.substring(0, 5) : '';
            const jamP = jkw.jamPulangWajib ? jkw.jamPulangWajib.substring(0, 5) : '';
            const jamL = jkw.jamLemburMaks ? jkw.jamLemburMaks.substring(0, 5) : '';
            const kode = jkw.kodeBulan ? jkw.kodeBulan.trim() : '';

            const labelText = `Wajib: ${jamD} s/d ${jamP} [Batas Jam Lembur: ${jamL}] (${kode})`;
            const $option = $('<option></option>').val(jkw.id).attr('data-kode', kode).text(labelText);
            
            $select.append($option);
        });

        // Hidupkan kembali Select2 dengan state status disabled terbaru
        $select.select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#lemburModal')
        });

        $select.prop('disabled', isDisabled).trigger('change.select2');
    }

    function parseTimeToMinutes(timeStr) {
        if (!timeStr) return 0;
        const [hours, minutes] = timeStr.split(':').map(Number);
        return (hours * 60) + minutes;
    }

    function formatMinutesToTime(totalMinutes) {
        if (totalMinutes < 0) totalMinutes = 0;
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    function hitungOtomatisJam() {
        const jamDatangValue = document.getElementById('form_jamDatang').value; 
        const jamWajibDatangValue = document.getElementById('form_jamWajibDatang').value; 
        const jamPulangValue = document.getElementById('form_jamPulang').value; 

        if (!jamDatangValue || !jamWajibDatangValue) return;

        const menitDatang = parseTimeToMinutes(jamDatangValue);
        const menitWajibDatang = parseTimeToMinutes(jamWajibDatangValue);
        const menitPulangWajib = parseTimeToMinutes(currentJamPulangWajib);
        const menitLemburMaksimal = parseTimeToMinutes(currentJamLemburMaks); 

        let tambahanMenitKeterlambatan = 0;
        if (menitDatang > menitWajibDatang) {
            tambahanMenitKeterlambatan = menitDatang - menitWajibDatang;
        }

        const totalMenitKerjaEfektif = menitPulangWajib + tambahanMenitKeterlambatan;
        document.getElementById('form_jamKerja').value = formatMinutesToTime(totalMenitKerjaEfektif);

        if (jamPulangValue) {
            const menitPulangRiil = parseTimeToMinutes(jamPulangValue);
            let menitLemburHitung = 0;

            if (menitPulangRiil <= totalMenitKerjaEfektif) {
                menitLemburHitung = 0;
            } else if (menitPulangRiil > menitLemburMaksimal) {
                menitLemburHitung = menitLemburMaksimal - totalMenitKerjaEfektif;
            } else {
                menitLemburHitung = menitPulangRiil - totalMenitKerjaEfektif;
            }
            
            if (menitLemburHitung < 0) menitLemburHitung = 0;

            document.getElementById('form_jamLembur').value = formatMinutesToTime(menitLemburHitung);
        }
    }

    function openModal(mode) {
        if(mode === 'add') {
            modalTitle.innerText = "Form Tambah Lembur";
            modalForm.action = "{{ route('lembur.store') }}";
            methodContainer.innerHTML = ""; 
            modalForm.reset();
            
            currentJamPulangWajib = "16:00"; 
            currentJamLemburMaks = "19:00";
            
            document.getElementById('periode_normal').checked = true;
            $('.select2-dosen').val(null).trigger('change');
            
            renderSelect2Options([]);
            setFormFieldsDisabledStatus(false);
        }
        b5Modal.show();
    }

    function editData(id) {
        Swal.fire({
            title: 'Memuat Data...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
        });

        fetch(`/manajemen-lembur/${id}/edit`)
            .then(response => {
                if (!response.ok) throw new Error('Gagal mengambil data dari server.');
                return response.json();
            })
            .then(data => {
                Swal.close();

                openModal('edit');
                modalTitle.innerText = "Form Ubah Lembur";
                modalForm.action = `/manajemen-lembur/${id}`;
                methodContainer.innerHTML = `<input type="hidden" name="_method" value="PUT">`;

                const formatTime = (timeStr) => timeStr ? timeStr.substring(0, 5) : '';

                // Cek relasi periode (Normal / Ramadan) dari kode database asal
                const shiftTerikat = masterShift.find(item => item.id == data.jam_kerja_wajib_id);
                if (shiftTerikat) {
                    const kodeBersih = shiftTerikat.kodeBulan ? shiftTerikat.kodeBulan.trim() : '';
                    if (['R', 'RJ', 'RS', 'K2R', 'KRJ'].includes(kodeBersih)) {
                        document.getElementById('periode_ramadan').checked = true;
                    } else {
                        document.getElementById('periode_normal').checked = true;
                    }
                    
                    currentJamPulangWajib = formatTime(shiftTerikat.jamPulangWajib);
                    currentJamLemburMaks = formatTime(shiftTerikat.jamLemburMaks);
                }

                document.getElementById('form_tanggal').value = data.tanggal;

                // Evaluasi otomatis dengan mem-passing ID terpilih dari DB
                evaluasiAturanOtomatis(data.jam_kerja_wajib_id);

                $('.select2-dosen').val(data.nip).trigger('change');

                document.getElementById('form_jamDatang').value = formatTime(data.jamDatang);
                document.getElementById('form_jamWajibDatang').value = formatTime(data.jamWajibDatang);
                document.getElementById('form_jamKerja').value = formatTime(data.jamKerja);
                document.getElementById('form_jamPulang').value = formatTime(data.jamPulang);
                document.getElementById('form_jamLembur').value = formatTime(data.jamLembur);
                document.getElementById('form_keterangan').value = data.keterangan ?? '';
            })
            .catch(err => {
                Swal.fire('Gagal!', err.message, 'error');
            });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data rekap lembur yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-form-${id}`).submit();
            }
        });
    }
</script>
@endsection