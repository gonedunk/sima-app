@extends('layouts.app') {{-- Sesuaikan nama master layout Anda --}}

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">Riwayat Arus Stok (Masuk & Keluar)</h1>
            <p class="text-muted mb-0">Rincian pergerakan stok riil secara kronologis untuk barang ini.</p>
        </div>
        <a href="{{ route('sirkulasi.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Sirkulasi
        </a>
    </div>

    {{-- INFORMASI BARANG --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light rounded">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <span class="badge bg-primary mb-2">Master Barang: {{ $barang->namaMasterBarang ?? '-' }}</span>
                    <h4 class="fw-bold text-dark mb-1">{{ $barang->merkBarang }}</h4>
                    <p class="text-muted mb-0"><strong>Spesifikasi:</strong> {{ $barang->spesifikasi ?? '-' }}</p>
                </div>
                <div class="col-md-5 mt-3 mt-md-0 border-start border-md-top-0 border-2">
                    <div class="row text-center">
                        <div class="col-4">
                            <span class="text-muted d-block small">Total Masuk</span>
                            <span class="fw-bold text-success fs-5">+{{ number_format($totalMasuk) }}</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block small">Total Keluar</span>
                            <span class="fw-bold text-danger fs-5">-{{ number_format($totalKeluar) }}</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block small">Stok Saat Ini</span>
                            <span class="fw-bold text-primary fs-5">{{ number_format($stokSekarang) }} <small class="fs-6 text-muted">{{ $barang->namaSatuan }}</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER PERIODE TANGGAL --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-filter me-1"></i> Filter Periode Tanggal</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('sirkulasi.history', $barang->id) }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" class="form-control" value="{{ request('tgl_mulai') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Tanggal Selesai</label>
                    <input type="date" name="tgl_selesai" class="form-control" value="{{ request('tgl_selesai') }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    @if(request()->filled('tgl_mulai') || request()->filled('tgl_selesai'))
                        <a href="{{ route('sirkulasi.history', $barang->id) }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL ARUS STOK & SALDO BERJALAN --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-dark">Rincian Transaksi & Saldo Berjalan</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Tanggal</th>
                            <th class="text-center">Tipe</th>
                            <th class="text-end text-success">Masuk</th>
                            <th class="text-end text-danger">Keluar</th>
                            <th class="text-end text-info">Sisa Stok</th>
                            <th>Satuan</th>
                            <th>Supplier / Penerima</th>
                            <th>Petugas / Penanggung Jawab</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $saldo = 0; @endphp
                        
                        @forelse($arusStok as $index => $row)
                            @php
                                $saldo += ($row->masuk - $row->keluar);
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <small class="fw-bold">{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</small><br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($row->tanggal)->format('H:i') }} WIB</small>
                                </td>
                                <td class="text-center">
                                    @if($row->tipe == 'MASUK')
                                        <span class="badge bg-success">MASUK</span>
                                    @else
                                        <span class="badge bg-danger">KELUAR</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success">
                                    {{ $row->masuk > 0 ? '+'.number_format($row->masuk) : '-' }}
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    {{ $row->keluar > 0 ? '-'.number_format($row->keluar) : '-' }}
                                </td>
                                <td class="text-end fw-bold text-primary bg-light">
                                    {{ number_format($saldo) }}
                                </td>
                                <td><span class="badge bg-secondary">{{ $barang->namaSatuan }}</span></td>
                                <td>{{ $row->sumber_tujuan ?? '-' }}</td>
                                <td>{{ $row->penanggung_jawab ?? '-' }}</td>
                                <td><small class="text-muted">{{ $row->keterangan ?? '-' }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    Belum ada riwayat pergerakan stok masuk atau keluar untuk barang ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection