<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManajemenJamKerjaController extends Controller
{
    /**
     * Menampilkan halaman utama manajemen jam kerja, libur, lembur, dan bulan puasa.
     */
    public function index()
    {
        // PADA PANEL 1: Mengambil data aturan jam kerja wajib (TETAP SAMA / TIDAK DIUBAH)
        $dataJamWajib = DB::table('tbjamkerjawajib')->get();

        // PADA PANEL 2: Mengambil data tanggal libur saja dari tabel tbtglliburlembur
        $dataLibur = DB::table('tbtglliburlembur')->get(); 

        // PADA PANEL 3: Mengambil data rentang tanggal bulan puasa dari tabel tbbulanpuasa
        $dataPuasa = DB::table('tbbulanpuasa')->get();

        return view('admin.manajemenjamkerja.index', compact('dataJamWajib', 'dataLibur', 'dataPuasa'));
    }

    /**
     * Menyimpan data jam kerja wajib baru ke database. (PANEL 1 - TETAP SAMA)
     */
    public function storeJamWajib(Request $request)
    {
        $request->validate([
            'kode_bulan'       => 'required|string',
            'jam_datang'       => 'required',
            'jam_pulang'       => 'required',
            'jam_lembur_maks'  => 'required',
        ]);

        DB::table('tbjamkerjawajib')->insert([
            'kodeBulan'      => strtoupper($request->kode_bulan),
            'jamDatangWajib' => $request->jam_datang,
            'jamPulangWajib' => $request->jam_pulang,
            'jamLemburMaks'  => $request->jam_lembur_maks,
        ]);

        return redirect()->route('jam-kerja.index')->with('success', 'Data jam kerja wajib baru berhasil ditambahkan!');
    }

    /**
     * Memperbarui data jam kerja wajib yang sudah ada. (PANEL 1 - TETAP SAMA)
     */
    public function updateJamWajib(Request $request, $id)
    {
        $request->validate([
            'kode_bulan'       => 'required|string',
            'jam_datang'       => 'required',
            'jam_pulang'       => 'required',
            'jam_lembur_maks'  => 'required',
        ]);

        DB::table('tbjamkerjawajib')->where('id', $id)->update([
            'kodeBulan'      => strtoupper($request->kode_bulan),
            'jamDatangWajib' => $request->jam_datang,
            'jamPulangWajib' => $request->jam_pulang,
            'jamLemburMaks'  => $request->jam_lembur_maks,
        ]);

        return redirect()->route('jam-kerja.index')->with('success', 'Data jam kerja wajib berhasil diperbarui!');
    }

    /**
     * Menghapus data jam kerja wajib dari database. (PANEL 1 - TETAP SAMA)
     */
    public function destroyJamWajib($id)
    {
        DB::table('tbjamkerjawajib')->where('id', $id)->delete();

        return redirect()->route('jam-kerja.index')->with('success', 'Data jam kerja wajib berhasil dihapus!');
    }

    // =========================================================================
    // PERUBAHAN KHUSUS PANEL 2: MURNI UNTUK DATA TANGGAL LIBUR SAJA
    // =========================================================================
    
    /**
     * Menyimpan data tanggal libur baru ke database.
     */
    public function storeLibur(Request $request)
    {
        $request->validate([
            'tanggal'    => 'required|date_format:Y-m-d', 
            'keterangan' => 'required|string|max:255',
        ]);

        DB::table('tbtglliburlembur')->insert([
            'tanggal'    => $request->tanggal, 
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('jam-kerja.index', ['tab' => 'libur-lembur'])->with('success', 'Tanggal libur berhasil disimpan!');
    }

    /**
     * Memperbarui data tanggal libur di database.
     */
    public function updateLibur(Request $request, $id)
    {
        $request->validate([
            'tanggal'    => 'required|date_format:Y-m-d',
            'keterangan' => 'required|string|max:255',
        ]);

        DB::table('tbtglliburlembur')->where('id', $id)->update([
            'tanggal'    => $request->tanggal,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('jam-kerja.index', ['tab' => 'libur-lembur'])->with('success', 'Tanggal libur berhasil diperbarui!');
    }

    /**
     * Menghapus data tanggal libur dari database.
     */
    public function destroyLibur($id)
    {
        DB::table('tbtglliburlembur')->where('id', $id)->delete();

        return redirect()->route('jam-kerja.index', ['tab' => 'libur-lembur'])->with('success', 'Tanggal libur berhasil dihapus!');
    }

    // =========================================================================
    // PANEL 3: CRUD DATA OPERASIONAL BULAN PUASA (tbbulanpuasa)
    // =========================================================================

    /**
     * Menyimpan data jadwal bulan puasa baru ke database.
     */
    public function storeBulanPuasa(Request $request)
    {
        $request->validate([
            'dari_tanggal'   => 'required|date_format:Y-m-d',
            'sampai_tanggal' => 'required|date_format:Y-m-d|after_or_equal:dari_tanggal',
            'keterangan'     => 'required|string|max:255',
        ]);

        DB::table('tbbulanpuasa')->insert([
            'dariTanggal'   => $request->dari_tanggal,
            'sampaiTanggal' => $request->sampai_tanggal,
            'keterangan'    => $request->keterangan,
        ]);

        return redirect()->route('jam-kerja.index', ['tab' => 'bulan-puasa'])->with('success', 'Data rentang bulan puasa berhasil ditambahkan!');
    }

    /**
     * Memperbarui data jadwal bulan puasa di database.
     */
    public function updateBulanPuasa(Request $request, $id)
    {
        $request->validate([
            'dari_tanggal'   => 'required|date_format:Y-m-d',
            'sampai_tanggal' => 'required|date_format:Y-m-d|after_or_equal:dari_tanggal',
            'keterangan'     => 'required|string|max:255',
        ]);

        DB::table('tbbulanpuasa')->where('id', $id)->update([
            'dariTanggal'   => $request->dari_tanggal,
            'sampaiTanggal' => $request->sampai_tanggal,
            'keterangan'    => $request->keterangan,
        ]);

        return redirect()->route('jam-kerja.index', ['tab' => 'bulan-puasa'])->with('success', 'Data rentang bulan puasa berhasil diperbarui!');
    }

    /**
     * Menghapus data jadwal bulan puasa dari database.
     */
    public function destroyBulanPuasa($id)
    {
        DB::table('tbbulanpuasa')->where('id', $id)->delete();

        return redirect()->route('jam-kerja.index', ['tab' => 'bulan-puasa'])->with('success', 'Data rentang bulan puasa berhasil dihapus!');
    }
}