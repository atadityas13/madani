<?php

use App\Http\Controllers\Api\V1\ReferensiController;
use App\Http\Controllers\Api\V1\SiswaAuthController;
use App\Http\Controllers\Api\V1\SiswaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('siswa/login', [SiswaAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'siswa.api'])->group(function () {
        Route::post('siswa/logout', [SiswaAuthController::class, 'logout']);
        Route::get('siswa/me', [SiswaAuthController::class, 'me']);
        Route::put('siswa/password', [SiswaAuthController::class, 'updatePassword']);
        Route::get('referensi', [ReferensiController::class, 'emis']);
        Route::get('wilayah', [ReferensiController::class, 'wilayah']);

        Route::middleware('siswa.password')->group(function () {
            Route::put('siswa/{bagian}', [SiswaController::class, 'update'])
                ->where('bagian', 'data-siswa|orang-tua|alamat|rekam-didik');
            Route::post('siswa/prestasi', [SiswaController::class, 'storePrestasi']);
            Route::post('siswa/beasiswa', [SiswaController::class, 'storeBeasiswa']);
            Route::delete('siswa/{jenis}/{id}', [SiswaController::class, 'destroyRelasi'])
                ->where('jenis', 'prestasi|beasiswa');
            Route::post('siswa/dokumen/{jenis}', [SiswaController::class, 'uploadDokumen'])
                ->where('jenis', 'kk|kip|kks|pkh|ijazah_sd|foto');
            Route::post('siswa/upload-url', [SiswaController::class, 'requestUploadUrl']);
            Route::post('siswa/upload-confirm', [SiswaController::class, 'confirmUpload']);
            Route::post('siswa/pengajuan-perubahan', [SiswaController::class, 'storePengajuan']);
        });
    });
});
