@extends('layouts.app')

{{-- Memanggil CSS Select2 dari folder public/css --}}
@section('styles')
<link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}">
@endsection

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">Sirkulasi Stok Opname Realtime</h1>
            <p class="text-muted mb-0">Daftar posisi stok akhir barang secara riil yang dikelompokkan per Master Barang.</p>
        </div>
        {{-- TOMBOL CETAK REKAP SELURUH BARANG --}}
        <div>
            <a href="{{ route('rekap-opname.cetak') }}" target="_blank" class="btn btn-danger shadow-sm">
                <i class="fas fa-print me-1"></i> Cetak Rekap Opname
            </a>
        </div>
    </div>

    {{-- FILTER SEARCHING --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="m-0 fw-bold"><i class="fas fa-filter me-1"></i> Filter Barang</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('sirkulasi.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small text-muted">Pilih Barang</label>
                    {{-- Element Select dengan class 'select2' --}}
                    <select name="idAnak" class="form-select select2">
                        <option value="">-- Semua Barang --</option>
                        @foreach($listBarang as $b)
                            <option value="{{ $b->id }}" {{ request('idAnak') == $b->id ? 'selected' : '' }}>
                                {{ $b->namaMaster }} - {{ $b->merkBarang }} ({{ $b->spesifikasi }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Kata Kunci (Merk/Spesifikasi)</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Ketik kata kunci..." value="{{ request('keyword') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cari
                    </button>
                    <a href="{{ route('sirkulasi.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL STOK REALTIME DENGAN GRUP HEADER MASTER BARANG --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th style="width: 130px;">Tgl Update</th>
                            <th>Merk & Spesifikasi</th>
                            <th class="text-center" style="width: 110px;">Stok Akhir</th>
                            <th class="text-center" style="width: 80px;">Satuan</th>
                            <th>Keterangan Terakhir</th>
                            <th class="text-center" style="width: 250px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = $sirkulasi->firstItem() ?? 1; @endphp

                        {{-- Pengelompokan Data Berdasarkan Master Barang --}}
                        @forelse($sirkulasi->getCollection()->groupBy('namaMasterBarang') as $masterBarang => $items)
                            
                            {{-- BARIS HEADER GRUP DATA (MASTER BARANG) --}}
                            <tr class="table-primary fw-bold">
                                <td colspan="7" class="py-2 px-3 text-uppercase">
                                    <i class="fas fa-boxes me-2 text-primary"></i> Master Barang: <strong>{{ $masterBarang }}</strong>
                                    <span class="badge bg-dark ms-2">{{ $items->count() }} Varian</span>
                                </td>
                            </tr>

                            {{-- BARIS ANAK BARANG DI BAWAH MASTER BARANG --}}
                            @foreach($items as $item)
                                <tr>
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td>
                                        <small class="fw-bold d-block">{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</small>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($item->tanggal)->format('H:i') }} WIB</small>
                                    </td>
                                    <td>
                                        <strong class="text-dark">{{ $item->merkBarang }}</strong>
                                        <p class="text-muted small mb-0">{{ $item->spesifikasi ?? '-' }}</p>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success fs-6">{{ number_format($item->stokAkhir) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $item->namaSatuan }}</span>
                                    </td>
                                    <td><small class="text-muted">{{ $item->keterangan ?? '-' }}</small></td>
                                    
                                    {{-- AKSI & LINK CETAK KARTU STOK PER BARANG --}}
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('sirkulasi.history', $item->idAnak) }}" class="btn btn-sm btn-outline-info" title="History Arus Stok">
                                                <i class="fas fa-history me-1"></i> History
                                            </a>
                                            <a href="{{ route('kartustok.cetak', ['idAnak' => $item->idAnak]) }}" target="_blank" class="btn btn-sm btn-info" title="Cetak Kartu Stok">
                                                <i class="fas fa-print me-1"></i> Cetak Kartu Stok
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Data sirkulasi stok opname belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- PAGINATION --}}
            <div class="p-3">
                {{ $sirkulasi->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Memanggil JS Select2 dari folder public/js --}}
@section('scripts')
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Semua Barang --',
            allowClear: true
        });
    });
</script>
@endsection