<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CetakRekapPerbarangController extends Controller
{
    public function cetak(Request $request, $idAnak)
    {
        // 1. Ambil detail barang berdasarkan idAnak
        $barang = DB::table('tbanakbarang as ab')
            ->join('tbmasterbarang as mb', 'ab.idMaster', '=', 'mb.id')
            ->leftJoin('tbsatuan as s', 'ab.idsatuan', '=', 's.id')
            ->select(
                'ab.id',
                'mb.namaBarang',
                'ab.merkBarang',
                'ab.spesifikasi',
                's.jenisBarang as satuan'
            )
            ->where('ab.id', $idAnak)
            ->first();

        // Jika data barang tidak ditemukan
        if (!$barang) {
            abort(404, 'Data barang tidak ditemukan.');
        }

        // 2. Query Transaksi Barang Masuk
        $transaksiMasuk = DB::table('tbtransaksibarangmasuk')
            ->select(
                'tglMasuk as tanggal',
                DB::raw("'MASUK' as tipe"),
                'namaSupplier as pihak_terkait',
                'penerima as penerima_petugas',
                'jumlah as masuk',
                DB::raw("0 as keluar"),
                'keterangan as catatan'
            )
            ->where('idAnak', $idAnak);

        // 3. Query Transaksi Barang Keluar
        $transaksiKeluar = DB::table('tbtransaksibarangkeluar')
            ->select(
                'tglKeluar as tanggal',
                DB::raw("'KELUAR' as tipe"),
                DB::raw("'-' as pihak_terkait"),
                DB::raw("CONCAT(petugas, ' / ', penerima) as penerima_petugas"),
                DB::raw("0 as masuk"),
                'jumlah as keluar',
                'catatan'
            )
            ->where('idAnak', $idAnak);

        // 4. Gabungkan kedua query (UNION ALL) dan urutkan secara kronologis berdasarkan tanggal
        $riwayatTransaksi = $transaksiMasuk
            ->unionAll($transaksiKeluar)
            ->orderBy('tanggal', 'asc')
            ->get();

        // 5. Menyiapkan data pimpinan/pengelola jika dibutuhkan untuk tanda tangan
        $pimpinan = (object) [
            'jabatan' => 'Ketua Jurusan Akuntansi',
            'nama'    => 'Syarifuddin, S.E., M.Si.',
            'nip'     => '197001011995121001'
        ];

        $pengelola = (object) [
            'jabatan' => 'Pengelola Barang',
            'nama'    => auth()->user()->nama ?? auth()->user()->name ?? 'Administrator',
            'nip'     => '-'
        ];

        $data = [
            'barang'           => $barang,
            'riwayatTransaksi' => $riwayatTransaksi,
            'pimpinan'         => $pimpinan,
            'pengelola'        => $pengelola
        ];

        // 6. Generate PDF ke file views/pdf/kartustokperbarang.blade.php
        $pdf = Pdf::loadView('pdf.kartustokperbarang', $data)
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Kartu_Stok_' . str_replace(' ', '_', $barang->namaBarang) . '_' . date('Ymd') . '.pdf');
    }
}