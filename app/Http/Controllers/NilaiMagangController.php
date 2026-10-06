<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NilaiMagangController extends Controller
{
    /**
     * Menampilkan daftar nilai mahasiswa magang berdasarkan TA Aktif.
     */
  public function index(Request $request)
{
    // 1. Ambil Tahun Akademik Aktif
    $setting = DB::table('tbsetting')->first();
    $taAktif = $setting ? $setting->ta_aktif : null;

    // 2. Ambil daftar instansi unik untuk isi dropdown filter
    $listInstansi = DB::table('tbnilaiperusahaan')
        ->where('tahunAkademik', $taAktif)
        ->whereNotNull('namaPerusahaan') // Sesuaikan nama kolom instansi di database Anda
        ->distinct()
        ->pluck('namaPerusahaan');

    // 3. Query utama dengan filter
    $query = DB::table('tbnilaiperusahaan')
        ->where('tahunAkademik', $taAktif);

    // Filter berdasarkan Perusahaan / Instansi jika dipilih
    if ($request->filled('perusahaan')) {
        $query->where('namaPerusahaan', $request->perusahaan);
    }

    // Filter pencarian nama / NPM
    if ($request->filled('q')) {
        $search = $request->q;
        $query->where(function($q) use ($search) {
            $q->where('npm', 'like', "%{$search}%")
              ->orWhere('nama', 'like', "%{$search}%"); // Sesuaikan nama kolom
        });
    }

    $daftarNilai = $query->orderBy('npm', 'asc')
        ->paginate(15)
        ->withQueryString(); // Mempertahankan parameter URL saat perpindahan halaman pagination

    return view('admin.nilaimagang.index', compact('daftarNilai', 'taAktif', 'listInstansi'));
}

    /**
     * Mengambil detail data untuk keperluan Modal Edit Penilaian (AJAX JSON).
     */
    public function edit($id)
    {
        $nilai = DB::table('tbnilaiperusahaan')->where('id', $id)->first();

        if (!$nilai) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data penilaian tidak ditemukan.'
            ], 404);
        }

        return response()->json($nilai);
    }

    /**
     * Memperbarui / Menginput Nilai Perusahaan Mahasiswa.
     */
    public function update(Request $request, $id)
    {
        // 1. Validasi input nilai (skala 0 - 100)
        $request->validate([
            'format_penilaian' => 'required|in:pt,perusahaan', // Validasi tipe format evaluasi yang dikirim modal
            'etika'            => 'required|numeric|between:0,100',
            'disiplin'         => 'required|numeric|between:0,100',
            'percayaDiri'      => 'required|numeric|between:0,100',
            'kerjaSama'        => 'required|numeric|between:0,100',
            'motivasi'         => 'required|numeric|between:0,100',
            'inisiatifKerja'   => 'required|numeric|between:0,100',
            'loyalitas'        => 'required|numeric|between:0,100',
            'tanggungJawab'    => 'required|numeric|between:0,100',
            'pemahaman'        => 'required|numeric|between:0,100',
            'PtigaK'           => 'required|numeric|between:0,100',
        ]);

        try {
            // Hitung akumulasi murni
            $jumlahSkor = 
                $request->etika + 
                $request->disiplin + 
                $request->percayaDiri + 
                $request->kerjaSama + 
                $request->motivasi + 
                $request->inisiatifKerja + 
                $request->loyalitas + 
                $request->tanggungJawab + 
                $request->pemahaman + 
                $request->PtigaK;

            // 2. Logika Penentuan Total Nilai Berdasarkan Format
            if ($request->format_penilaian === 'pt') {
                // FORMAT PT: Akumulasi murni penjumlahan tanpa pembagian (Maksimal 100)
                $totalNilai = $jumlahSkor;
            } else {
                // FORMAT PERUSAHAAN: Menggunakan Rata-rata dari 10 komponen
                $totalNilai = $jumlahSkor / 10;
            }

            // 3. Update data ke tabel tbnilaiperusahaan
            DB::table('tbnilaiperusahaan')
                ->where('id', $id)
                ->update([
                    'etika'          => $request->etika,
                    'disiplin'       => $request->disiplin,
                    'percayaDiri'    => $request->percayaDiri,
                    'kerjaSama'      => $request->kerjaSama,
                    'motivasi'       => $request->motivasi,
                    'inisiatifKerja' => $request->inisiatifKerja,
                    'loyalitas'      => $request->loyalitas,
                    'tanggungJawab'  => $request->tanggungJawab,
                    'pemahaman'      => $request->pemahaman,
                    'PtigaK'         => $request->PtigaK,
                    'totalNilai'     => $totalNilai, // Tersimpan sesuai format pilihan
                ]);

            return redirect()->route('admin.nilai-magang.index')
                ->with('success', 'Nilai instansi perusahaan berhasil diperbarui.');

        } catch (\Exception $e) {
            Log::error('Gagal memperbarui nilai magang ID ' . $id . ': ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan sistem saat menyimpan nilai.');
        }
    }

    /**
     * Mereset nilai mahasiswa kembali ke angka 0.
     */
    public function destroy($id)
    {
        try {
            DB::table('tbnilaiperusahaan')
                ->where('id', $id)
                ->update([
                    'etika'          => 0,
                    'disiplin'       => 0,
                    'percayaDiri'    => 0,
                    'kerjaSama'      => 0,
                    'motivasi'       => 0,
                    'inisiatifKerja' => 0,
                    'loyalitas'      => 0,
                    'tanggungJawab'  => 0,
                    'pemahaman'      => 0,
                    'PtigaK'         => 0,
                    'totalNilai'     => 0
                ]);

            return redirect()->route('admin.nilai-magang.index')
                ->with('success', 'Nilai instansi perusahaan mahasiswa berhasil di-reset.');

        } catch (\Exception $e) {
            Log::error('Gagal mereset nilai magang ID ' . $id . ': ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Gagal mereset nilai penempatan.');
        }
    }
}