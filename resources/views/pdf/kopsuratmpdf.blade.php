<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Dokumen Cetak Jurusan Akuntansi')</title>
    <style>
        /* Margin default mPDF untuk semua halaman */
        @page {
            margin-top: 10mm;
            margin-bottom: 15mm;
            margin-left: 15mm;
            margin-right: 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }

        /* Component Kop Surat */
        .kop-container {
            width: 100%;
            margin-bottom: 10px;
        }
        .kop-table { 
            width: 100%; 
            border-collapse: collapse; 
            border: none;
        }
        .kop-logo { 
            width: 24mm; /* Lebar kolom tempat logo */
            text-align: center; 
            vertical-align: middle; 
            border: none;
        }

        .kop-text { 
            text-align: center; 
            vertical-align: middle;
            border: none;
        }
        .kop-instansi { 
            font-size: 10pt; 
            font-weight: bold; 
            text-transform: uppercase; 
            margin: 0; 
            line-height: 1.2; 
        }
        .kop-jurusan { 
            font-size: 11pt; 
            font-weight: bold; 
        }
        .kop-alamat { 
            font-size: 7.5pt; 
            margin-top: 2px; 
            line-height: 1.2;
        }
        .kop-garis { 
            border-bottom: 2.5px solid #000; 
            padding-bottom: 1px; 
            margin-top: 4px;
        }
        .kop-garis-tipis { 
            border-bottom: 0.8px solid #000; 
            margin-bottom: 5px; 
        }

        @yield('additional_styles')
    </style>
</head>
<body>

    <!-- HTML Kop Surat -->
    <div class="kop-container">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <!-- Kunci ukuran LANGSUNG di atribut tag img & style inline -->
                    <img src="{{ public_path('img/logo-polsri.png') }}" 
                         width="75" 
                         height="75" 
                         style="width: 20mm; height: 20mm;" 
                         alt="Logo">
                </td>
                <td class="kop-text">
                    <div class="kop-instansi">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET,</div>
                    <div class="kop-instansi">DAN TEKNOLOGI</div>
                    <div class="kop-instansi">POLITEKNIK NEGERI SRIWIJAYA</div>
                    <div class="kop-jurusan">JURUSAN AKUNTANSI</div>
                    <div class="kop-alamat">
                        Jalan Srijaya Negara, Bukit Besar, Palembang 30139 | Telp/Fax: (0711) 353414<br>
                        Laman: https://www.polsri.ac.id | E-mail: akuntansi@polsri.ac.id
                    </div>
                </td>
            </tr>
        </table>
        <div class="kop-garis"></div>
        <div class="kop-garis-tipis"></div>
    </div>

    <!-- Konten halaman akan disisipkan di sini -->
    @yield('content')

</body>
</html>