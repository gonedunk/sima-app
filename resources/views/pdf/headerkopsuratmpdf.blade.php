{{-- CSS Kop Surat --}}
<style>
    .kop-surat-table {
        width: 100%;
        border-collapse: collapse;
        border-bottom: 3px double #000000;
        margin-bottom: 10px;
        padding-bottom: 5px;
    }
    .kop-surat-table td {
        border: none;
        padding: 0;
        vertical-align: middle;
    }
    .kop-logo {
        width: 10%;
        text-align: center;
    }
    .kop-logo img {
        width: 75px;
        height: auto;
    }
    .kop-teks {
        width: 90%;
        text-align: center;
        line-height: 1.2;
    }
    .kop-instansi {
        font-size: 11pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 0;
    }
    .kop-kampus {
        font-size: 12pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 2px 0;
    }
    .kop-jurusan {
        font-size: 11pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 2px 0;
    }
    .kop-alamat {
        font-size: 8pt;
        margin: 0;
    }
</style>

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