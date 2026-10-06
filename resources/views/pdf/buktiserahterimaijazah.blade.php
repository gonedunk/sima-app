@extends('pdf.kopsuratdompdf')

@section('title', 'Bukti Serah Terima Ijazah & Transkrip')

@section('content')

    <!-- JUDUL DOKUMEN & NOMOR (RATA TENGAH) -->
    <center>
        <div style="margin-top: 10px; margin-bottom: 15px;">
            <span style="font-size: 13pt; font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                BUKTI SERAH TERIMA IJAZAH & TRANSKRIP AKADEMIK
            </span><br>
            <span style="font-size: 10pt;">
                Nomor: {{ $mahasiswa->no_surat ?? '......./PL6.3.2/BA/'.date('Y') }}
            </span>
        </div>
    </center>

    <p style="margin-bottom: 8px; font-size: 11pt; text-align: justify;">
        Pada hari ini, <strong>{{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</strong>, telah diserahkan dokumen kelulusan akademik kepada mahasiswa yang bersangkutan di bawah ini:
    </p>

    <!-- TABEL DATA MAHASISWA (BERGARIS TEGAS) -->
    <table border="1" cellspacing="0" cellpadding="6" style="width: 100%; border-collapse: collapse; border: 1px solid #000; margin-top: 10px; margin-bottom: 15px; font-size: 10pt;">
        <tr>
            <td style="width: 28%; background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;">Nama Mahasiswa</td>
            <td style="border: 1px solid #000;"><strong>{{ strtoupper($mahasiswa->nama) }}</strong></td>
        </tr>
        <tr>
            <td style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;">NPM / NIM</td>
            <td style="border: 1px solid #000;">{{ $mahasiswa->npm }}</td>
        </tr>
        <tr>
            <td style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;">Program</td>
            <td style="border: 1px solid #000;">{{ $mahasiswa->namaProdi ?? $mahasiswa->nama_prodi ?? $mahasiswa->prodi ?? '-' }}</td>
        </tr>
        <tr>
            <td style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;">Jurusan</td>
            <td style="border: 1px solid #000;">{{ $mahasiswa->namaJurusan ?? $mahasiswa->jurusan ?? 'Akuntansi' }}</td>
        </tr>
        <tr>
            <td style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;">Tahun Kelulusan / TA</td>
            <td style="border: 1px solid #000;">{{ $mahasiswa->tahunAkademik ?? $mahasiswa->tahunMasuk ?? date('Y') }}</td>
        </tr>
    </table>

    <p style="margin-bottom: 5px; font-size: 11pt;">Rincian dokumen yang diserahkan dan diterima secara sah:</p>

    <!-- TABEL RINCIAN DOKUMEN (BERGARIS TEGAS) -->
    <table border="1" cellspacing="0" cellpadding="6" style="width: 100%; border-collapse: collapse; border: 1px solid #000; margin-top: 8px; margin-bottom: 15px; font-size: 10pt;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="width: 8%; border: 1px solid #000; text-align: center; font-weight: bold;">No</th>
                <th style="width: 52%; border: 1px solid #000; text-align: center; font-weight: bold;">Jenis Dokumen</th>
                <th style="width: 20%; border: 1px solid #000; text-align: center; font-weight: bold;">Jumlah</th>
                <th style="width: 20%; border: 1px solid #000; text-align: center; font-weight: bold;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">1</td>
                <td style="border: 1px solid #000;">Ijazah Asli</td>
                <td style="border: 1px solid #000; text-align: center;">1 Lembar</td>
                <td style="border: 1px solid #000; text-align: center;">Asli</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">2</td>
                <td style="border: 1px solid #000;">Transkrip Nilai Akademik Asli</td>
                <td style="border: 1px solid #000; text-align: center;">1 Lembar</td>
                <td style="border: 1px solid #000; text-align: center;">Asli</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">3</td>
                <td style="border: 1px solid #000;">Fotokopi Ijazah</td>
                <td style="border: 1px solid #000; text-align: center;">2 Rangkap</td>
                <td style="border: 1px solid #000; text-align: center;">Fotokopi</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">4</td>
                <td style="border: 1px solid #000;">Fotokopi Transkrip Nilai</td>
                <td style="border: 1px solid #000; text-align: center;">2 Rangkap</td>
                <td style="border: 1px solid #000; text-align: center;">Fotokopi</td>
            </tr>
        </tbody>
    </table>

    <!-- CATATAN PENTING -->
    <div style="border: 1px dashed #555; padding: 8px 12px; font-size: 9pt; margin-bottom: 25px; background-color: #fafafa;">
        <strong>PENTING:</strong>
        <ol style="margin: 3px 0 0 15px; padding: 0;">
            <li>Periksa kembali kebenaran identitas pada Ijazah dan Transkrip Nilai sebelum menandatangani bukti ini.</li>
            <li>Ijazah & Transkrip Asli yang hilang / rusak setelah penyerahan ini menjadi tanggung jawab mahasiswa sepenuhnya.</li>
        </ol>
    </div>

    <!-- TANDA TANGAN -->
    <table width="100%" border="0" style="margin-top: 20px; font-size: 11pt;">
        <tr>
            <td width="50%"></td>
            <td width="50%" align="center">
                Palembang, {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}<br>
                Yang Menerima (Mahasiswa),
                <br><br><br><br><br>
                <strong><u>{{ $mahasiswa->nama }}</u></strong><br>
                NPM. {{ $mahasiswa->npm }}
            </td>
        </tr>
    </table>

@endsection