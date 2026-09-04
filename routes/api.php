<?php

use App\Http\Controllers\Api\V1\AppUpdateController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\GuruAuthController;
use App\Http\Controllers\Api\V1\GuruCalendarEventController;
use App\Http\Controllers\Api\V1\GuruElapkinController;
use App\Http\Controllers\Api\V1\GuruProfileController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\PengumumanController;
use App\Http\Controllers\Api\V1\ReferensiController;
use App\Http\Controllers\Api\V1\SiswaAuthController;
use App\Http\Controllers\Api\V1\SiswaController;
use App\Http\Controllers\Api\V1\TokenIntrospectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('app-update/{platform}', [AppUpdateController::class, 'show']);
    Route::post('siswa/login', [SiswaAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('guru/login', [GuruAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('token/introspect', TokenIntrospectController::class)->middleware('throttle:60,1');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('pengumuman', [PengumumanController::class, 'index']);
        Route::post('device-token', [DeviceTokenController::class, 'store']);
        Route::delete('device-token', [DeviceTokenController::class, 'destroy']);
        Route::get('notifikasi', [NotifikasiController::class, 'index']);
        Route::post('notifikasi/read-all', [NotifikasiController::class, 'markAllRead']);
        Route::post('notifikasi/clear', [NotifikasiController::class, 'clear']);
        Route::post('notifikasi/{notifikasi}/read', [NotifikasiController::class, 'markRead']);
        Route::post('notifikasi/{notifikasi}/dismiss', [NotifikasiController::class, 'dismiss']);
    });

    Route::middleware(['auth:sanctum', 'guru.api'])->prefix('guru')->group(function () {
        Route::post('logout', [GuruAuthController::class, 'logout']);
        Route::get('me', [GuruAuthController::class, 'me']);
        Route::put('password', [GuruAuthController::class, 'updatePassword']);
        Route::put('profile/biodata', [GuruProfileController::class, 'updateBiodata']);
        Route::put('profile/kontak', [GuruProfileController::class, 'updateKontak']);
        Route::post('profile/foto', [GuruProfileController::class, 'updatePhoto']);
        Route::get('calendar-events', [GuruCalendarEventController::class, 'index']);
        Route::get('elapkin-sso', [GuruElapkinController::class, 'ssoToken']);
        Route::post('elapkin-bridge', [GuruElapkinController::class, 'bridgeSession']);
        Route::get('hari-libur', [GuruElapkinController::class, 'hariLibur']);
    });

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
                ->where('jenis', 'kk|akta_lahir|kip|kks|pkh|ijazah_sd|foto');
            Route::post('siswa/upload-url', [SiswaController::class, 'requestUploadUrl']);
            Route::post('siswa/upload-confirm', [SiswaController::class, 'confirmUpload']);
            Route::post('siswa/pengajuan-perubahan', [SiswaController::class, 'storePengajuan']);
            Route::get('siswa/portofolio.pdf', [SiswaController::class, 'portofolio']);
        });
    });
});
