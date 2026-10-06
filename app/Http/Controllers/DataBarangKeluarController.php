<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataBarangKeluarController extends Controller
{
    public function index()
    {
        // 1. Master Barang
        $masterBarang = DB::table('tbmasterbarang')->orderBy('namaBarang', 'asc')->get();

        // 2. Subquery ID Sirkulasi Stok Terakhir per idAnak
        $latestSirkulasiIds = DB::table('tbsirkulasistokopname')
            ->select(DB::raw('MAX(id) as max_id'))
            ->groupBy('idAnak');

        // Subquery Stok Terakhir
        $subStokTerakhir = DB::table('tbsirkulasistokopname')
            ->whereIn('id', $latestSirkulasiIds)
            ->select('idAnak', 'stokAkhir');

        // Subquery Tanggal Barang Masuk Pertama Kali per idAnak
        $subTglMasukPertama = DB::table('tbtransaksibarangmasuk')
            ->select('idAnak', DB::raw('MIN(tglMasuk) as tglMasukPertama'))
            ->groupBy('idAnak');

        // 3. Query Anak Barang (dengan Stok Realtime & Tanggal Masuk Pertama)
        $anakBarang = DB::table('tbanakbarang')
            ->leftJoinSub($subStokTerakhir, 'stok_terakhir', function ($join) {
                $join->on('tbanakbarang.id', '=', 'stok_terakhir.idAnak');
            })
            ->leftJoinSub($subTglMasukPertama, 'masuk_pertama', function ($join) {
                $join->on('tbanakbarang.id', '=', 'masuk_pertama.idAnak');
            })
            ->select([
                'tbanakbarang.*',
                DB::raw('IFNULL(stok_terakhir.stokAkhir, 0) as stokRealtime'),
                'masuk_pertama.tglMasukPertama'
            ])
            ->orderBy('tbanakbarang.merkBarang', 'asc')
            ->get();

        // 4. Data Dosen / Petugas
        $dosenPetugas = DB::table('tbdosen')->orderBy('nama', 'asc')->where('level','02')->get();
        $dosenPenerima = DB::table('tbdosen')->orderBy('nama', 'asc')->get();

        // 5. Query Daftar Transaksi Barang Keluar
        $barangKeluar = DB::table('tbtransaksibarangkeluar')
            ->leftJoinSub($subStokTerakhir, 'stok_terakhir', function ($join) {
                $join->on('tbtransaksibarangkeluar.idAnak', '=', 'stok_terakhir.idAnak');
            })
            ->leftJoin('tbanakbarang', 'tbtransaksibarangkeluar.idAnak', '=', 'tbanakbarang.id')
            ->leftJoin('tbdosen as dsn_petugas', function($join) {
                $join->on('tbtransaksibarangkeluar.petugas', '=', DB::raw('dsn_petugas.nip COLLATE utf8mb4_general_ci'));
            })
            ->leftJoin('tbdosen as dsn_penerima', function($join) {
                $join->on('tbtransaksibarangkeluar.penerima', '=', DB::raw('dsn_penerima.nip COLLATE utf8mb4_general_ci'));
            })
            ->select([
                'tbtransaksibarangkeluar.*', 
                'stok_terakhir.stokAkhir',
                'tbanakbarang.merkBarang',
                'tbanakbarang.spesifikasi',
                'dsn_petugas.nama as nama_petugas',
                'dsn_penerima.nama as nama_penerima'
            ])
            ->orderBy('tbtransaksibarangkeluar.tglKeluar', 'desc')
            ->get();

        return view('admin.databarangkeluar.index', compact(
            'masterBarang', 
            'anakBarang', 
            'dosenPetugas', 
            'dosenPenerima', 
            'barangKeluar'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tglKeluar' => 'required|date',
            'idAnak'    => 'required',
            'jumlah'    => 'required|integer|min:1',
            'petugas'   => 'required',
            'penerima'  => 'required',
        ]);

        // 1. Validasi Tanggal Keluar vs Tanggal Masuk Paling Awal
        $tglMasukPertama = DB::table('tbtransaksibarangmasuk')
            ->where('idAnak', $request->idAnak)
            ->min('tglMasuk');

        if ($tglMasukPertama && $request->tglKeluar < $tglMasukPertama) {
            $tglFormatted = date('d-m-Y', strtotime($tglMasukPertama));
            return redirect()->back()->with('error', "Transaksi ditolak! Tanggal pengeluaran tidak boleh lebih kecil dari tanggal barang pertama kali masuk ($tglFormatted).");
        }

        // 2. Proteksi Stok Realtime
        $stokAktual = DB::table('tbsirkulasistokopname')
            ->where('idAnak', $request->idAnak)
            ->orderBy('id', 'desc')
            ->value('stokAkhir') ?? 0;

        if ($request->jumlah > $stokAktual) {
            return redirect()->back()->with('error', 'Transaksi ditolak! Jumlah pengeluaran melebihi stok yang ada saat ini.');
        }

        DB::beginTransaction();
        try {
            DB::table('tbtransaksibarangkeluar')->insert([
                'tglKeluar' => $request->tglKeluar,
                'idAnak'    => $request->idAnak,
                'jumlah'    => $request->jumlah,
                'petugas'   => $request->petugas,
                'penerima'  => $request->penerima,
                'catatan'   => $request->catatan
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaksi barang keluar berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tglKeluar' => 'required|date',
            'idAnak'    => 'required',
            'jumlah'    => 'required|integer|min:1',
            'petugas'   => 'required',
            'penerima'  => 'required',
        ]);

        // 1. Cari data lama transaksi barang keluar
        $transaksiLama = DB::table('tbtransaksibarangkeluar')->where('id', $id)->first();

        if (!$transaksiLama) {
            return redirect()->back()->with('error', 'Data transaksi barang keluar tidak ditemukan.');
        }

        // 2. Validasi Tanggal Keluar vs Tanggal Masuk Paling Awal
        $tglMasukPertama = DB::table('tbtransaksibarangmasuk')
            ->where('idAnak', $request->idAnak)
            ->min('tglMasuk');

        if ($tglMasukPertama && $request->tglKeluar < $tglMasukPertama) {
            $tglFormatted = date('d-m-Y', strtotime($tglMasukPertama));
            return redirect()->back()->with('error', "Transaksi ditolak! Tanggal pengeluaran tidak boleh lebih kecil dari tanggal barang pertama kali masuk ($tglFormatted).");
        }

        // 3. Proteksi Stok Realtime saat Edit
        $stokAktual = DB::table('tbsirkulasistokopname')
            ->where('idAnak', $request->idAnak)
            ->orderBy('id', 'desc')
            ->value('stokAkhir') ?? 0;

        // Hitung batas stok maksimal yang tersedia untuk perubahan ini:
        // Jika idAnak tidak berubah, stok yang tersedia adalah stok saat ini + jumlah barang transaksi lama
        if ($transaksiLama->idAnak == $request->idAnak) {
            $stokTersedia = $stokAktual + $transaksiLama->jumlah;
        } else {
            $stokTersedia = $stokAktual;
        }

        if ($request->jumlah > $stokTersedia) {
            return redirect()->back()->with('error', 'Transaksi ditolak! Jumlah pengeluaran melebihi stok yang tersedia.');
        }

        DB::beginTransaction();
        try {
            DB::table('tbtransaksibarangkeluar')->where('id', $id)->update([
                'tglKeluar' => $request->tglKeluar,
                'idAnak'    => $request->idAnak,
                'jumlah'    => $request->jumlah,
                'petugas'   => $request->petugas,
                'penerima'  => $request->penerima,
                'catatan'   => $request->catatan
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaksi barang keluar berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $transaksi = DB::table('tbtransaksibarangkeluar')->where('id', $id)->first();
            
            if (!$transaksi) {
                return redirect()->back()->with('error', 'Data transaksi barang keluar tidak ditemukan.');
            }

            DB::table('tbtransaksibarangkeluar')->where('id', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Transaksi barang keluar berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }
}