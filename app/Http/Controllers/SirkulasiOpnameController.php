<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SirkulasiOpnameController extends Controller
{
    /**
     * Menampilkan stok akhir paling terbaru secara riil per barang (idAnak)
     */
    public function index(Request $request)
    {
        // 1. Data Dropdown Select Filter Barang
        $listBarang = DB::table('tbanakbarang as ab')
            ->leftJoin('tbmasterbarang as mb', 'ab.idMaster', '=', 'mb.id')
            ->select('ab.id', 'ab.merkBarang', 'ab.spesifikasi', 'mb.namaBarang as namaMaster')
            ->orderBy('mb.namaBarang', 'asc')
            ->orderBy('ab.merkBarang', 'asc')
            ->get();

        // 2. Subquery ID sirkulasi terbaru per idAnak
        $latestIds = DB::table('tbsirkulasistokopname')
            ->select(DB::raw('MAX(id) as max_id'))
            ->groupBy('idAnak');

        // 3. Query Utama dengan JOIN ke tbanakbarang, tbmasterbarang, dan tbsatuan
        $query = DB::table('tbsirkulasistokopname as s')
            ->joinSub($latestIds, 'latest', function ($join) {
                $join->on('s.id', '=', 'latest.max_id');
            })
            ->join('tbanakbarang as ab', 's.idAnak', '=', 'ab.id')
            ->leftJoin('tbmasterbarang as mb', 'ab.idMaster', '=', 'mb.id')
            ->leftJoin('tbsatuan as st', 'ab.idsatuan', '=', 'st.id');

        // -------------------------------------------------------------
        // FILTER SEARCHING
        // -------------------------------------------------------------
        if ($request->filled('idAnak')) {
            $query->where('s.idAnak', $request->idAnak);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('ab.merkBarang', 'like', "%{$keyword}%")
                  ->orWhere('ab.spesifikasi', 'like', "%{$keyword}%")
                  ->orWhere('mb.namaBarang', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('keterangan')) {
            $query->where('s.keterangan', 'like', '%' . $request->keterangan . '%');
        }

        // -------------------------------------------------------------
        // SELECT & PAGINATION
        // -------------------------------------------------------------
        $sirkulasi = $query->select(
                                's.id', 
                                's.tanggal', 
                                's.idAnak', 
                                's.stokAkhir', 
                                's.keterangan',
                                'ab.merkBarang',
                                'ab.spesifikasi',
                                'ab.idsatuan',
                                'mb.namaBarang as namaMasterBarang',
                                DB::raw('IFNULL(st.jenisBarang, "Pcs") as namaSatuan')
                           )
                           ->orderBy('s.idAnak', 'asc')
                           ->paginate(15)
                           ->withQueryString();

        return view('admin.sirkulasiopname.index', compact('sirkulasi', 'listBarang'));
    }

    /**
     * Menampilkan detail satu record sirkulasi berdasarkan ID
     */
    public function show($id)
    {
        $sirkulasi = DB::table('tbsirkulasistokopname as s')
                       ->join('tbanakbarang as ab', 's.idAnak', '=', 'ab.id')
                       ->leftJoin('tbmasterbarang as mb', 'ab.idMaster', '=', 'mb.id')
                       ->leftJoin('tbsatuan as st', 'ab.idsatuan', '=', 'st.id')
                       ->where('s.id', $id)
                       ->select(
                           's.id', 
                           's.tanggal', 
                           's.idAnak', 
                           's.stokAkhir', 
                           's.keterangan',
                           'ab.merkBarang',
                           'ab.spesifikasi',
                           'ab.idsatuan',
                           'mb.namaBarang as namaMasterBarang',
                           DB::raw('IFNULL(st.jenisBarang, "Pcs") as namaSatuan')
                       )
                       ->first();

        if (!$sirkulasi) {
            abort(404, 'Data sirkulasi tidak ditemukan.');
        }

        return view('admin.sirkulasiopname.show', compact('sirkulasi'));
    }

    /**
     * Menampilkan Detail Arus Stok Masuk & Keluar berdasarkan idAnak (Barang)
     */
    public function history(Request $request, $idAnak)
    {
        // 1. Ambil Informasi Barang
        $barang = DB::table('tbanakbarang as ab')
            ->leftJoin('tbmasterbarang as mb', 'ab.idMaster', '=', 'mb.id')
            ->leftJoin('tbsatuan as st', 'ab.idsatuan', '=', 'st.id')
            ->where('ab.id', $idAnak)
            ->select(
                'ab.id',
                'ab.merkBarang',
                'ab.spesifikasi',
                'mb.namaBarang as namaMasterBarang',
                DB::raw('IFNULL(st.jenisBarang, "Pcs") as namaSatuan')
            )
            ->first();

        if (!$barang) {
            abort(404, 'Data Barang tidak ditemukan.');
        }

        // 2. Query Transaksi Barang Masuk
        // Gunakan DB::raw pada klausa ON untuk menyelaraskan Collation saat perbandingan '='
        $masuk = DB::table('tbtransaksibarangmasuk as tm')
            ->leftJoin('tbdosen as p_masuk', function($join) {
                $join->on(DB::raw('tm.penerima COLLATE utf8mb4_general_ci'), '=', DB::raw('p_masuk.nip COLLATE utf8mb4_general_ci'));
            })
            ->select(
                'tm.id',
                'tm.tglMasuk as tanggal',
                DB::raw("CONVERT('MASUK' USING utf8mb4) as tipe"),
                'tm.jumlah as masuk',
                DB::raw('0 as keluar'),
                DB::raw("CONVERT(tm.namaSupplier USING utf8mb4) as sumber_tujuan"),
                DB::raw("CONVERT(IFNULL(p_masuk.nama, tm.penerima) USING utf8mb4) as penanggung_jawab"),
                DB::raw("CONVERT(tm.keterangan USING utf8mb4) as keterangan")
            )
            ->where('tm.idAnak', $idAnak);

        // 3. Query Transaksi Barang Keluar
        $keluar = DB::table('tbtransaksibarangkeluar as tk')
            ->leftJoin('tbdosen as p_penerima', function($join) {
                $join->on(DB::raw('tk.penerima COLLATE utf8mb4_general_ci'), '=', DB::raw('p_penerima.nip COLLATE utf8mb4_general_ci'));
            })
            ->leftJoin('tbdosen as p_petugas', function($join) {
                $join->on(DB::raw('tk.petugas COLLATE utf8mb4_general_ci'), '=', DB::raw('p_petugas.nip COLLATE utf8mb4_general_ci'));
            })
            ->select(
                'tk.id',
                'tk.tglKeluar as tanggal',
                DB::raw("CONVERT('KELUAR' USING utf8mb4) as tipe"),
                DB::raw('0 as masuk'),
                'tk.jumlah as keluar',
                DB::raw("CONVERT(IFNULL(p_penerima.nama, tk.penerima) USING utf8mb4) as sumber_tujuan"),
                DB::raw("CONVERT(IFNULL(p_petugas.nama, tk.petugas) USING utf8mb4) as penanggung_jawab"),
                DB::raw("CONVERT(tk.catatan USING utf8mb4) as keterangan")
            )
            ->where('tk.idAnak', $idAnak);

        // Filter Rentang Tanggal jika Diperlukan
        if ($request->filled('tgl_mulai') && $request->filled('tgl_selesai')) {
            $masuk->whereBetween('tm.tglMasuk', [$request->tgl_mulai, $request->tgl_selesai]);
            $keluar->whereBetween('tk.tglKeluar', [$request->tgl_mulai, $request->tgl_selesai]);
        }

        // 4. Gabungkan Arus Masuk & Keluar dengan UNION ALL
        $arusStok = $masuk->unionAll($keluar)
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 5. Hitung Ringkasan Total
        $totalMasuk = $arusStok->sum('masuk');
        $totalKeluar = $arusStok->sum('keluar');
        $stokSekarang = $totalMasuk - $totalKeluar;

        return view('admin.sirkulasiopname.history', compact('barang', 'arusStok', 'totalMasuk', 'totalKeluar', 'stokSekarang'));
    }
  
}