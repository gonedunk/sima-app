<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Dokumen Cetak Jurusan Akuntansi')</title>
    <style>
        @page {
            margin: 4cm 2cm 2cm 2cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }
        header {
            position: fixed;
            top: -3.5cm;
            left: 0cm;
            right: 0cm;
            height: 3cm;
            border-bottom: 4px double #000;
        }
        .kop-table { width: 100%; border-collapse: collapse; }
        .kop-logo { width: 15%; text-align: center; vertical-align: middle; }
        .kop-logo img { height: 75px; width: auto; }
        .kop-text { width: 85%; text-align: center; }
        .kop-instansi { font-size: 12pt; text-transform: uppercase; margin: 0; line-height: 1.1; }
        .kop-jurusan { font-weight: bold; }
        .kop-alamat { font-size: 9pt; margin-top: 3px; }
        
        /* Inject style dari halaman anak */
        @yield('additional_styles')
    </style>
</head>
<body>
    <header>
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/logo-polsri.png'))) }}">
                </td>
                <td class="kop-text">
                    <div class="kop-instansi">KEMENTERIAN PENDIDIKAN TINGGI, SAINS,</div>
                    <div class="kop-instansi">DAN TEKNOLOGI</div>
                    <div class="kop-instansi">POLITEKNIK NEGERI SRIWIJAYA</div>
                    <div class="kop-instansi kop-jurusan">JURUSAN AKUNTANSI</div>
                    <div class="kop-alamat">
                        Jalan Srijaya Negara Bukit Besar - Palembang 30139 Telepon (0711) 353414<br>
                        Laman : http://polsri.ac.id, Pos El : info@polsri.ac.id
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>