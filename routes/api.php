<?php


use App\Http\Controllers\Api\DokumenController;

Route::get('/mahasiswa-transkrip', [DokumenController::class, 'getMahasiswa']);
Route::post('/store-transkrip', [DokumenController::class, 'storeTranskrip']);