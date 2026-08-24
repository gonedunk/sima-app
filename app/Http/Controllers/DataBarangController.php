<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataBarangController extends Controller
{
    /**
     * Menampilkan halaman kelola barang dengan data ter-grouping
     */
    public function index()
    {
        $barang = DB::table('tbanakbarang')
            ->join('tbmasterbarang', 'tbanakbarang.idMaster', '=', 'tbmasterbarang.id')
            ->join('tbsatuan', 'tbanakbarang.idsatuan', '=', 'tbsatuan.id')
            ->select(
                'tbmasterbarang.namaBarang',
                'tbanakbarang.id as id_anak',
                'tbanakbarang.merkBarang',
                'tbanakbarang.spesifikasi',
                'tbsatuan.jenisBarang'
            )
            ->orderBy('tbmasterbarang.namaBarang', 'asc')
            ->get();

        $masterBarangOpt = DB::table('tbmasterbarang')
            ->select('id', 'namaBarang')
            ->orderBy('namaBarang', 'asc')
            ->get();

        // FIX QUERY: Ambil namaBarang dari table master agar data-parent di Blade terisi dan JS tidak crash!
        $anakBarangOpt = DB::table('tbanakbarang')
            ->join('tbmasterbarang', 'tbanakbarang.idMaster', '=', 'tbmasterbarang.id')
            ->select('tbanakbarang.id', 'tbanakbarang.merkBarang', 'tbmasterbarang.namaBarang')
            ->distinct()
            ->get();

        $satuanOpt = DB::table('tbsatuan')
            ->select('id', 'jenisBarang')
            ->orderBy('jenisBarang', 'asc')
            ->get();

        return view('admin.databarang.index', compact('barang', 'masterBarangOpt', 'anakBarangOpt', 'satuanOpt'));
    }

    /**
     * Memproses Input Data Barang Baru via Form/Modal
     */
    public function store(Request $request)
    {
        $request->validate([
            'namaBarang'  => 'required|string|max:255',
            'merkBarang'  => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'jenisBarang' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            $master = DB::table('tbmasterbarang')->where('namaBarang', trim($request->namaBarang))->first();
            $idMaster = $master ? $master->id : DB::table('tbmasterbarang')->insertGetId(['namaBarang' => trim($request->namaBarang)]);

            $satuan = DB::table('tbsatuan')->where('jenisBarang', trim($request->jenisBarang))->first();
            $idSatuan = $satuan ? $satuan->id : DB::table('tbsatuan')->insertGetId(['jenisBarang' => trim($request->jenisBarang)]);

            DB::table('tbanakbarang')->insert([
                'idMaster'    => $idMaster,
                'merkBarang'  => trim($request->merkBarang),
                'spesifikasi' => $request->spesifikasi ?? '-', // FIX: Mencegah error Column 'spesifikasi' cannot be null
                'idsatuan'    => $idSatuan
            ]);
        });

        return redirect()->route('barang.index')->with('success', 'Data inventaris berhasil ditambahkan!');
    }

    /**
     * Memproses Perubahan Data Anak Barang dari Modal Edit
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'namaBarang'  => 'required|string|max:255',
            'merkBarang'  => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'jenisBarang' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $id) {
            $master = DB::table('tbmasterbarang')->where('namaBarang', trim($request->namaBarang))->first();
            $idMaster = $master ? $master->id : DB::table('tbmasterbarang')->insertGetId(['namaBarang' => trim($request->namaBarang)]);

            $satuan = DB::table('tbsatuan')->where('jenisBarang', trim($request->jenisBarang))->first();
            $idSatuan = $satuan ? $satuan->id : DB::table('tbsatuan')->insertGetId(['jenisBarang' => trim($request->jenisBarang)]);

            DB::table('tbanakbarang')->where('id', $id)->update([
                'idMaster'    => $idMaster,
                'merkBarang'  => trim($request->merkBarang),
                'spesifikasi' => $request->spesifikasi ?? '-', // FIX: Mencegah error Column 'spesifikasi' cannot be null
                'idsatuan'    => $idSatuan
            ]);
        });

        return redirect()->route('barang.index')->with('success', 'Data inventaris berhasil diubah!');
    }

    /**
     * AJAX Endpoint untuk Filter Anak Barang
     */
    public function getAnakBarang(Request $request)
    {
        $namaBarang = $request->get('namaBarang');

        $anakBarang = DB::table('tbanakbarang')
            ->join('tbmasterbarang', 'tbanakbarang.idMaster', '=', 'tbmasterbarang.id')
            ->where('tbmasterbarang.namaBarang', $namaBarang)
            ->select('tbanakbarang.merkBarang')
            ->distinct()
            ->orderBy('tbanakbarang.merkBarang', 'asc')
            ->get();

        return response()->json($anakBarang);
    }

    /**
     * Menghapus Data Anak Barang
     * Alur: Cek & hapus data di tbtransaksibarangmasuk terlebih dahulu jika ada, baru hapus anak barang.
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            // 1. Hapus semua riwayat transaksi masuk yang merujuk ke anak barang ini
            DB::table('tbtransaksibarangmasuk')->where('idAnak', $id)->delete();

            // 2. Hapus data utama di tbanakbarang
            DB::table('tbanakbarang')->where('id', $id)->delete();
        });

        return redirect()->route('barang.index')->with('success', 'Data anak barang dan riwayat transaksi masuk terkait berhasil dihapus!');
    }

    /**
     * Menghapus Data Master Barang
     * Alur: Proteksi ketat. Jika masih ada anak barang yang bergantung, proses dihentikan.
     */
    public function destroyMaster($id)
    {
        // 1. Cek apakah masih ada anak barang yang terikat dengan Master Barang ini
        $adaAnakBarang = DB::table('tbanakbarang')->where('idMaster', $id)->exists();

        if ($adaAnakBarang) {
            return redirect()->route('barang.index')->with('error', 'Gagal menghapus! Silakan hapus semua data Anak Barang terkait terlebih dahulu sebelum menghapus Master Barang.');
        }

        // 2. Jika bersih dari anak barang, lakukan penghapusan master
        DB::table('tbmasterbarang')->where('id', $id)->delete();

        return redirect()->route('barang.index')->with('success', 'Data master barang berhasil dihapus!');
    }
}