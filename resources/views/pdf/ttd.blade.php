{{-- BAGIAN TABEL DATA MAHASISWA DAN REKAP SELESAI --}}

{{-- 🔴 INCLUDE TTD UNIVERSAL 🔴 --}}
@if(($mode_ttd ?? 'single') !== 'none')
<div class="ttd-container" style="width: 100%; margin-top: 30px; page-break-inside: avoid;">
    <table style="width: 100%; border: none; border-collapse: collapse;">
        
        <!-- BARIS 1: TANGGAL, JABATAN & A.N. -->
        <tr>
            <!-- KIRI -->
            <td style="width: 50%; text-align: center; border: none; vertical-align: top;">
                @if(($mode_ttd ?? 'single') === 'dual' && isset($ttdKiri))
                    @if(!empty($isAnKiri))
                        <p style="margin: 0; font-size: 8.5pt;">a.n. Direktur</p>
                    @endif
                    <p style="margin: 0; font-weight: bold; font-size: 8.5pt;">{{ $ttdKiri->jabatan ?? '' }}</p>
                @endif
            </td>

            <!-- KANAN -->
            <td style="width: 50%; text-align: center; border: none; vertical-align: top;">
                <p style="margin: 0; font-size: 8.5pt;">Palembang, {{ $tgl_cetak ?? \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</p>
                @if(!empty($isAnKanan))
                    <p style="margin: 0; font-size: 8.5pt;">a.n. Ketua Jurusan</p>
                @endif
                <p style="margin: 0; font-weight: bold; font-size: 8.5pt;">{{ $ttdKanan->jabatan ?? 'Pengelola Jurusan' }}</p>
            </td>
        </tr>

        <!-- BARIS 2: RUANG KOSONG TANDA TANGAN / QR CODE -->
        <tr>
            <!-- KIRI -->
            <td style="height: 60px; border: none; text-align: center; vertical-align: middle;">
                @if(($mode_ttd ?? 'single') === 'dual' && isset($ttdKiri))
                    @if(($jenis_ttd ?? 'manual') === 'qr' && !empty($qrKiri ?? null))
                        @php $pathQrKiri = storage_path('app/public/' . $qrKiri); @endphp
                        @if(file_exists($pathQrKiri))
                            <img src="{{ $pathQrKiri }}" style="height: 55px; width: auto;">
                        @endif
                    @endif
                @endif
            </td>

            <!-- KANAN -->
            <td style="height: 60px; border: none; text-align: center; vertical-align: middle;">
                @if(($jenis_ttd ?? 'manual') === 'qr' && !empty($qrKanan ?? null))
                    @php $pathQrKanan = storage_path('app/public/' . $qrKanan); @endphp
                    @if(file_exists($pathQrKanan))
                        <img src="{{ $pathQrKanan }}" style="height: 55px; width: auto;">
                    @endif
                @endif
            </td>
        </tr>

        <!-- BARIS 3: NAMA & NIP -->
        <tr>
            <!-- KIRI -->
            <td style="width: 50%; text-align: center; border: none; vertical-align: bottom;">
                @if(($mode_ttd ?? 'single') === 'dual' && isset($ttdKiri))
                    <p style="margin: 0; font-weight: bold; text-decoration: underline; font-size: 8.5pt;">{{ $ttdKiri->nama }}</p>
                    <p style="margin: 2px 0 0 0; font-size: 8.5pt;">NIP. {{ $ttdKiri->nip ?? '-' }}</p>
                @endif
            </td>

            <!-- KANAN -->
            <td style="width: 50%; text-align: center; border: none; vertical-align: bottom;">
                <p style="margin: 0; font-weight: bold; text-decoration: underline; font-size: 8.5pt;">{{ $ttdKanan->nama ?? '( ..................................... )' }}</p>
                <p style="margin: 2px 0 0 0; font-size: 8.5pt;">NIP. {{ $ttdKanan->nip ?? '-' }}</p>
            </td>
        </tr>

    </table>
</div>
@endif