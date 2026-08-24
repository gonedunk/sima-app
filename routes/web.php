<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JamajarController;
use App\Http\Controllers\KurikulumController;
use App\Http\Controllers\AjarDosenController;
use App\Http\Controllers\PlottingAjarDosenController;
use App\Http\Controllers\PengelolaJurusanController;
use App\Http\Controllers\ManajemenKelasController; 
use App\Http\Controllers\ManajemenJamKerjaController; 
use App\Http\Controllers\ManajemenJamLemburController;
use App\Http\Controllers\RekapLemburHistoryController;
use App\Http\Controllers\CetakLemburController;
use App\Http\Controllers\RekapLemburMingguanController; 
use App\Http\Controllers\PangkatGolonganController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\AnakPerusahaanController;
use App\Http\Controllers\InputDataMagangController;
use App\Http\Controllers\NilaiMagangController;
use App\Http\Controllers\TahunAkademikController;
use App\Http\Controllers\PimpinanPolsriController;
use App\Http\Controllers\DataBarangController;
use App\Http\Controllers\DataBarangMasukController;
use App\Http\Controllers\DataBarangKeluarController;
use App\Http\Controllers\SirkulasiOpnameController;
use App\Http\Controllers\TandaTanganController;
use App\Http\Controllers\CetakUniversalController;
use App\Http\Controllers\CetakRekapPerbarangController;
use App\Http\Controllers\SerahTerimaIjazahController;
use App\Http\Controllers\IjazahJurusanController;
use App\Http\Controllers\TranskripPublikController;
use App\Http\Controllers\TranskripAdminController;

use Illuminate\Support\Facades\Auth;

// =========================================================================
// ROUTE PUBLIK / GUEST
// =========================================================================
Route::get('/', function () {
    if (Auth::check()) { 
        return redirect('/index'); 
    }
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// Route Publik (Bisa diakses mahasiswa via Tunnel / Internet)
Route::get('/upload-transkrip', [TranskripPublikController::class, 'index'])->name('transkrip.index');
Route::get('/upload-transkrip/cek-status', [TranskripPublikController::class, 'cekStatus'])->name('transkrip.cek-status');
Route::post('/upload-transkrip', [TranskripPublikController::class, 'store'])->name('transkrip.store');
// =========================================================================
// ROUTE PROTECTED (HARUS LOGIN)
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // 1. DASHBOARD & SETTING
    Route::get('/index', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/setting', [SettingController::class, 'index'])->name('setting.index');
    Route::post('/setting/update', [SettingController::class, 'update'])->name('setting.update');

    // 2. MANAJEMEN USER
    Route::get('/user', [UserController::class, 'index'])->name('user.index');
    Route::post('/user/store', [UserController::class, 'store'])->name('user.store');
    Route::put('/user/update/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/delete/{id}', [UserController::class, 'destroy'])->name('user.destroy');

    // 3. MASTER MAHASISWA & KELAS
    Route::get('/admin/mahasiswa', [MahasiswaController::class, 'mahasiswaIndex'])->name('mahasiswa.index');
    Route::post('/admin/mahasiswa/store', [MahasiswaController::class, 'mahasiswaStore'])->name('mahasiswa.store');
    Route::put('/admin/mahasiswa/update/{id}', [MahasiswaController::class, 'mahasiswaUpdate'])->name('mahasiswa.update');
    Route::delete('/admin/mahasiswa/delete/{id}', [MahasiswaController::class, 'mahasiswaDestroy'])->name('mahasiswa.destroy');
    Route::get('/admin/mahasiswa/export', [MahasiswaController::class, 'mahasiswaExport'])->name('mahasiswa.export');
    Route::post('/admin/mahasiswa/import', [MahasiswaController::class, 'mahasiswaImport'])->name('mahasiswa.import');

    Route::get('/admin/kelas-mahasiswa', [MahasiswaController::class, 'kelasMhsIndex'])->name('kelas-mahasiswa.index');
    Route::post('/admin/kelas-mahasiswa/sync', [MahasiswaController::class, 'kelasMhsSync'])->name('kelas-mahasiswa.sync');
    Route::put('/admin/kelas-mahasiswa/update/{id}', [MahasiswaController::class, 'kelasMhsUpdate'])->name('kelas-mahasiswa.update');
    Route::delete('/admin/kelas-mahasiswa/delete/{id}', [MahasiswaController::class, 'kelasMhsDestroy'])->name('kelas-mahasiswa.destroy');
    Route::post('/admin/kelas-mahasiswa/promosi-massal', [MahasiswaController::class, 'kelasMhsPromosiMassal'])->name('kelas-mahasiswa.promosi-massal');
    Route::get('/admin/mahasiswa-so', [ManajemenKelasController::class, 'mahasiswaSO'])->name('mahasiswa.so');

  // 1. Route Cetak SERAH TERIMA PER KELAS (Menggunakan mPDF - SerahTerimaIjazahController)
Route::get('/kelas-mahasiswa/cetak-serah-terima-kelas/{id}', [SerahTerimaIjazahController::class, 'cetakBukti'])
    ->name('kelas-mahasiswa.cetak-serah-terima-kelas');

// 2. Route Cetak SERAH TERIMA INDIVIDU (Menggunakan DomPDF - MahasiswaController)
Route::get('/kelas-mahasiswa/cetak-serah-terima-mhs/{id}', [MahasiswaController::class, 'cetakSerahTerimaId'])
    ->name('kelas-mahasiswa.cetak-serah-terima-id');

  Route::post('/kelas-mahasiswa/lulus-massal', [MahasiswaController::class, 'lulusMassal'])
    ->name('kelas-mahasiswa.lulus-massal');
  
    // 4. ABSENSI 
    Route::get('/absensi', [AbsensiController::class, 'absensiIndex'])->name('absensi.index');
    Route::post('/absensi/sync', [AbsensiController::class, 'absensiSync'])->name('absensi.sync');
    Route::put('/absensi/update/{id}', [AbsensiController::class, 'absensiUpdate'])->name('absensi.update');
    Route::delete('/absensi/delete/{id}', [AbsensiController::class, 'absensiDelete'])->name('absensi.delete');
    Route::get('/absensi/export', [AbsensiController::class, 'absensiExport'])->name('absensi.export');
    Route::post('/absensi/import', [AbsensiController::class, 'absensiImport'])->name('absensi.import');
    Route::put('/absensi/buat-surat/{id}', [AbsensiController::class, 'absensiBuatSurat'])->name('absensi.buatsurat');

    // 5. MASTER DATA PEGAWAI (DOSEN & TENDIK)
    Route::get('/dosen', [DosenController::class, 'dosenIndex'])->name('dosen.index');
    Route::post('/dosen/store', [DosenController::class, 'dosenStore'])->name('dosen.store');
    Route::put('/dosen/update/{id}', [DosenController::class, 'dosenUpdate'])->name('dosen.update');
    Route::delete('/dosen/delete/{id}', [DosenController::class, 'dosenDestroy'])->name('dosen.delete');

    // 6. PROFILE USER
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // 7. MASTER JAM AJAR & PLOTTING
    Route::get('/superadmin/jamajar', [JamajarController::class, 'Jamajarindex'])->name('jamajar.index');
    Route::post('/superadmin/jamajar', [JamajarController::class, 'store'])->name('jamajar.store');
    Route::put('/superadmin/jamajar/{id}', [JamajarController::class, 'update'])->name('jamajar.update');
    Route::delete('/superadmin/jamajar/{id}', [JamajarController::class, 'destroy'])->name('jamajar.destroy');

    Route::get('/ajar-dosen/create', [AjarDosenController::class, 'create'])->name('admin.ajardosen.index');
    Route::post('/ajar-dosen/store', [AjarDosenController::class, 'store'])->name('admin.ajardosen.store');

    Route::get('/admin/plottingkelasdosen', [PlottingAjarDosenController::class, 'index'])->name('admin.plottingkelasdosen.index');
    Route::post('/admin/plottingkelasdosen', [PlottingAjarDosenController::class, 'store'])->name('admin.plottingkelasdosen.store');
    Route::put('/admin/plottingkelasdosen/{id}', [PlottingAjarDosenController::class, 'update'])->name('admin.plottingkelasdosen.update');
    Route::delete('/admin/plottingkelasdosen/{id}', [PlottingAjarDosenController::class, 'destroy'])->name('admin.plottingkelasdosen.destroy');
    
    Route::get('/admin/ajardosen/get-terisi-mk', [AjarDosenController::class, 'getTerisiMatakuliah'])->name('admin.ajardosen.getterisimk');
    Route::get('/admin/ajardosen/get-checkbox-jam', [AjarDosenController::class, 'getCheckboxJam'])->name('admin.ajardosen.getcheckboxjam');

    // 8. MASTER DATA BARANG INDUK
    Route::get('/barang/get-anak', [DataBarangController::class, 'getAnakBarang'])->name('barang.get-anak');
    Route::get('/barang', [DataBarangController::class, 'index'])->name('barang.index');
    Route::post('/barang', [DataBarangController::class, 'store'])->name('barang.store');
    Route::put('/barang/{id}', [DataBarangController::class, 'update'])->name('barang.update');
    Route::delete('/barang/{id}', [DataBarangController::class, 'destroy'])->name('barang.destroy');

  Route::get('/kartu-stok/{idAnak}', [CetakRekapPerbarangController::class, 'cetak'])->name('kartustok.cetak');

Route::prefix('jurusan')->name('jurusan.')->middleware(['auth'])->group(function () {
    Route::get('/ijazah', [IjazahJurusanController::class, 'index'])->name('ijazah.index');
    Route::post('/ijazah', [IjazahJurusanController::class, 'store'])->name('ijazah.store');
    Route::put('/ijazah/{id}', [IjazahJurusanController::class, 'update'])->name('ijazah.update');
    Route::delete('/ijazah/{id}', [IjazahJurusanController::class, 'destroy'])->name('ijazah.destroy');
});

  Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/transkrip/pengecekan', [TranskripAdminController::class, 'index'])->name('transkrip.admin.index');
    Route::post('/transkrip/verifikasi/{npm}', [TranskripAdminController::class, 'verifikasi'])->name('transkrip.admin.verifikasi');
});
  
    // =====================================================================
    // GRUP ROUTE KHUSUS AREA ADMIN (/admin)
    // =====================================================================
    Route::prefix('admin')->group(function () {
        
        // Pimpinan Polsri
        Route::resource('pimpinan', PimpinanPolsriController::class)->names([
            'index'   => 'pimpinan.index',
            'store'   => 'pimpinan.store',
            'update'  => 'pimpinan.update',
            'destroy' => 'pimpinan.destroy',
        ])->only(['index', 'store', 'update', 'destroy']);

        // Jam Kerja & Libur
        Route::get('/jam-kerja', [ManajemenJamKerjaController::class, 'index'])->name('jam-kerja.index');
        Route::post('/jam-kerja/store', [ManajemenJamKerjaController::class, 'storeJamWajib'])->name('jam-kerja.storeJamWajib');
        Route::put('/jam-kerja/update/{id}', [ManajemenJamKerjaController::class, 'updateJamWajib'])->name('jam-kerja.updateJamWajib');
        Route::delete('/jam-kerja/delete/{id}', [ManajemenJamKerjaController::class, 'destroyJamWajib'])->name('jam-kerja.destroyJamWajib');
        
        Route::post('/jam-kerja/libur/store', [ManajemenJamKerjaController::class, 'storeLibur'])->name('jam-kerja.storeLibur');
        Route::put('/jam-kerja/libur/update/{id}', [ManajemenJamKerjaController::class, 'updateLibur'])->name('jam-kerja.updateLibur');
        Route::delete('/jam-kerja/libur/delete/{id}', [ManajemenJamKerjaController::class, 'destroyLibur'])->name('jam-kerja.destroyLibur');
        
        Route::post('/jam-kerja/bulan-puasa', [ManajemenJamKerjaController::class, 'storeBulanPuasa'])->name('jam-kerja.storeBulanPuasa');
        Route::put('/jam-kerja/bulan-puasa/{id}', [ManajemenJamKerjaController::class, 'updateBulanPuasa'])->name('jam-kerja.updateBulanPuasa');
        Route::delete('/jam-kerja/bulan-puasa/{id}', [ManajemenJamKerjaController::class, 'destroyBulanPuasa'])->name('jam-kerja.destroyBulanPuasa');

        // Nilai Magang
        Route::get('/nilai-magang', [NilaiMagangController::class, 'index'])->name('admin.nilai-magang.index');
        Route::get('/nilai-magang/{id}/edit', [NilaiMagangController::class, 'edit'])->name('admin.nilai-magang.edit');
        Route::put('/nilai-magang/{id}', [NilaiMagangController::class, 'update'])->name('admin.nilai-magang.update');
        Route::delete('/nilai-magang/{id}', [NilaiMagangController::class, 'destroy'])->name('admin.nilai-magang.destroy');

        // Tanda Tangan & Cetak PDF
        Route::get('/tandatangan', [TandaTanganController::class, 'index'])->name('tandatangan.index');
        Route::post('/tandatangan', [TandaTanganController::class, 'store'])->name('tandatangan.store');
        Route::put('/tandatangan/{id}', [TandaTanganController::class, 'update'])->name('tandatangan.update');
        Route::delete('/tandatangan/{id}', [TandaTanganController::class, 'destroy'])->name('tandatangan.destroy');
        Route::post('/tandatangan/cetak-pdf', [TandaTanganController::class, 'cetakPdfLembur'])->name('tandatangan.cetak-pdf');

        // Data Barang Masuk
        Route::get('/barang-masuk', [DataBarangMasukController::class, 'index'])->name('barang-masuk.index');
        Route::post('/barang-masuk', [DataBarangMasukController::class, 'store'])->name('barang-masuk.store');
        Route::delete('/barang-masuk/{id}', [DataBarangMasukController::class, 'destroy'])->name('barang-masuk.destroy');
        Route::get('/barang-masuk/export-template', [DataBarangMasukController::class, 'exportTemplate'])->name('barang-masuk.export-template');
        Route::post('/barang-masuk/import-excel', [DataBarangMasukController::class, 'importExcel'])->name('barang-masuk.import-excel');
        Route::get('/barang-masuk/export', [DataBarangMasukController::class, 'exportExcel'])->name('barang-masuk.export');
        Route::post('/barang-masuk/import', [DataBarangMasukController::class, 'importExcel'])->name('barang-masuk.import');
        Route::delete('/barang-masuk/pending/{index}', [DataBarangMasukController::class, 'removeUnregisteredItem'])
            ->name('barang-masuk.remove-pending');
      
        // Data Barang Keluar
        Route::resource('barang-keluar', DataBarangKeluarController::class)->except(['create', 'edit', 'show']);
        Route::get('/barang-keluar/get-stok/{idAnak}', [DataBarangKeluarController::class, 'getStokAkhir'])->name('barang-keluar.get-stok');

        // =====================================================================
        // ROUTE CETAK UNIVERSAL PUSAT
        // =====================================================================
        // ROUTE CETAK UNIVERSAL
    Route::get('/cetak-universal', [CetakUniversalController::class, 'index'])->name('rekap-universal.cetak');
    Route::match(['get', 'post'], '/cetak-universal/proses', [CetakUniversalController::class, 'prosesCetak'])
        ->name('rekap-universal.proses');
        // Alias Route Lama (Agar tombol lama/link lain yang masih panggil 'rekap-opname.cetak' tidak error)
        Route::get('/sirkulasi-opname/cetak', [CetakUniversalController::class, 'index'])->name('rekap-opname.cetak');

        // --- ROUTE SIRKULASI & OPNAME MODUL ---
        Route::get('/sirkulasi-opname', [SirkulasiOpnameController::class, 'index'])->name('sirkulasi.index');
        Route::get('/sirkulasi-opname/history/{idAnak}', [SirkulasiOpnameController::class, 'history'])->name('sirkulasi.history');
        Route::get('/sirkulasi-opname/{id}', [SirkulasiOpnameController::class, 'show'])->name('sirkulasi.show');

        // Tahun Akademik
        Route::resource('tahun-akademik', TahunAkademikController::class)->names([
            'index'   => 'tahun-akademik.index',
            'store'   => 'tahun-akademik.store',
            'update'  => 'tahun-akademik.update',
            'destroy' => 'tahun-akademik.destroy',
        ])->only(['index', 'store', 'update', 'destroy']);
    });

    // 9. DATA PENEMPATAN MAGANG
    Route::prefix('admin/data-magang')->name('admin.data-magang.')->group(function () {
        Route::get('/ajax-mahasiswa', [InputDataMagangController::class, 'ajaxGetMahasiswa'])->name('ajaxGetMahasiswa');
        Route::get('/ajax-perusahaan', [InputDataMagangController::class, 'ajaxGetPerusahaan'])->name('ajaxGetPerusahaan');
        Route::get('/ajax-anak-cabang', [InputDataMagangController::class, 'ajaxGetAnakCabang'])->name('ajaxGetAnakCabang');

        Route::get('/', [InputDataMagangController::class, 'index'])->name('index');
        Route::get('/create', [InputDataMagangController::class, 'create'])->name('create');
        Route::post('/store', [InputDataMagangController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InputDataMagangController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [InputDataMagangController::class, 'update'])->name('update');
        Route::delete('/{id}/destroy', [InputDataMagangController::class, 'destroy'])->name('destroy');
    });

    // =====================================================================
    // KHUSUS LEVEL: SUPERADMIN & ADMIN (MANAJEMEN JAM LEMBUR & REKAP)
    // =====================================================================
    Route::group(['middleware' => function ($request, $next) {
        if (Auth::check() && in_array(Auth::user()->level, ['superadmin', 'admin'])) {
            return $next($request);
        }
        abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
    }], function () {
        
        // Modul Manajemen Lembur
        Route::controller(ManajemenJamLemburController::class)->group(function () {
            Route::get('/manajemen-lembur', 'index')->name('lembur.index');
            Route::post('/manajemen-lembur', 'store')->name('lembur.store');
            Route::get('/manajemen-lembur/export-template', 'exportTemplate')->name('lembur.export_template');
            Route::post('/manajemen-lembur/import-excel', 'importExcel')->name('lembur.import_excel');
            Route::post('/lembur/arsip-kjp2', [ManajemenJamLemburController::class, 'arsipKjp2ToHistory'])->name('lembur.arsip-kjp2');

            Route::get('/manajemen-lembur/{id}', function() {
                return redirect()->route('lembur.index')->with('error', 'Aksi detail data tidak didukung.');
            });

            Route::get('/manajemen-lembur/{id}/edit', 'edit')->name('lembur.edit');
            Route::put('/manajemen-lembur/{id}', 'update')->name('lembur.update');
            Route::delete('/manajemen-lembur/{id}', 'destroy')->name('lembur.destroy');
        });

        // Modul Rekap Lembur History
        Route::get('/admin/rekaplembur', [RekapLemburMingguanController::class, 'index'])->name('rekaplembur.index');
        Route::get('/admin/rekaplembur/cetak-mingguan', [RekapLemburMingguanController::class, 'cetakMingguan'])->name('rekaplembur.cetak_mingguan');

        Route::controller(RekapLemburHistoryController::class)->group(function () {
            Route::post('/admin/rekaplembur', 'store')->name('rekaplembur.store');
            Route::post('/admin/rekaplembur/generate', 'generateHistory')->name('lembur.history.generate');
            Route::get('/admin/rekaplembur/cetak-bulanan', [CetakLemburController::class, 'cetakBulanan'])->name('rekaplembur.cetak');

            Route::get('/admin/rekaplembur/{id}', function() {
                return redirect()->route('rekaplembur.index')->with('error', 'Aksi detail data tidak didukung.');
            });

            Route::get('/admin/rekaplembur/{id}/edit', 'edit')->name('rekaplembur.edit');
            Route::put('/admin/rekaplembur/{id}', 'update')->name('rekaplembur.update');
            Route::delete('/admin/rekaplembur/{id}', 'destroy')->name('rekaplembur.destroy');

            Route::put('/admin/rekaplembur/update-arsip/{id}', 'updateArsip')->name('rekaplembur.updateArsip');
            Route::delete('/admin/rekaplembur/hapus-arsip/{id}', 'destroyArsip')->name('rekaplembur.destroyArsip');
        });
    });

    // =====================================================================
    // KHUSUS LEVEL: SUPERADMIN ONLY
    // =====================================================================
    Route::middleware(['role:superadmin'])->group(function () {
        
        Route::resource('kelas', ManajemenKelasController::class)->except(['create', 'edit', 'show']);
        
        Route::prefix('superadmin')->name('superadmin.')->group(function () {
            // Kurikulum
            Route::post('kurikulum/bulk-update-status', [KurikulumController::class, 'bulkUpdateStatus'])->name('kurikulum.bulkUpdateStatus');
            Route::get('kurikulum/export/excel', [KurikulumController::class, 'exportExcel'])->name('kurikulum.export');
            Route::post('kurikulum/import/excel', [KurikulumController::class, 'importExcel'])->name('kurikulum.import');
            Route::resource('kurikulum', KurikulumController::class);

            // Pengelola Jurusan
            Route::get('/pengelolajurusan', [PengelolaJurusanController::class, 'index'])->name('pengelolajurusan.index');
            Route::post('/pengelolajurusan', [PengelolaJurusanController::class, 'store'])->name('pengelolajurusan.store');
            Route::put('/pengelolajurusan/{id}', [PengelolaJurusanController::class, 'update'])->name('pengelolajurusan.update');
            Route::delete('/pengelolajurusan/{id}', [PengelolaJurusanController::class, 'destroy'])->name('pengelolajurusan.destroy');
            Route::get('/api/dosen', [PengelolaJurusanController::class, 'apiDosen'])->name('api.dosen');

            // Perusahaan & Unit
            Route::resource('perusahaan', PerusahaanController::class)->except(['create', 'show', 'edit']);
            Route::get('/unit-instansi', [AnakPerusahaanController::class, 'index'])->name('unit.index');
            Route::post('/unit-instansi', [AnakPerusahaanController::class, 'store'])->name('unit.store');
            Route::put('/unit-instansi/{id_unit}', [AnakPerusahaanController::class, 'update'])->name('unit.update');
            Route::delete('/unit-instansi/{id_unit}', [AnakPerusahaanController::class, 'destroy'])->name('unit.destroy');
        });
    });

    // Pangkat Golongan
    Route::get('/superadmin/pangkat-golongan', [PangkatGolonganController::class, 'index'])->name('pangkat.index');
    Route::post('/superadmin/pangkat-golongan', [PangkatGolonganController::class, 'store'])->name('pangkat.store');
    Route::put('/superadmin/pangkat-golongan/{id}', [PangkatGolonganController::class, 'update'])->name('pangkat.update');
    Route::delete('/superadmin/pangkat-golongan/{id}', [PangkatGolonganController::class, 'destroy'])->name('pangkat.destroy');

});