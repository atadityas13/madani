<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GtkController;
use App\Http\Controllers\KelembagaanController;
use App\Http\Controllers\Portal\SiswaAuthController;
use App\Http\Controllers\Portal\SiswaPortalController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'create'])->name('login');
    Route::post('/', [LoginController::class, 'store']);
});

Route::middleware('guest:siswa')->group(function () {
    Route::get('/siswa/masuk', [SiswaAuthController::class, 'create'])->name('siswa.masuk');
    Route::post('/siswa/masuk', [SiswaAuthController::class, 'store']);
});

Route::redirect('/login', '/');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/siswa/keluar', [SiswaAuthController::class, 'destroy'])->middleware('auth:siswa')->name('siswa.keluar');

Route::middleware('auth:siswa')->group(function () {
    Route::get('/siswa/password', [SiswaAuthController::class, 'editPassword'])->name('siswa.password.edit');
    Route::put('/siswa/password', [SiswaAuthController::class, 'updatePassword'])->name('siswa.password.update');

    Route::middleware('siswa.password')->group(function () {
        Route::get('/siswa/portal', [SiswaPortalController::class, 'show'])->name('siswa.portal');
        Route::put('/siswa/portal', [SiswaPortalController::class, 'update'])->name('siswa.portal.update');
        Route::post('/siswa/portal/pengajuan', [SiswaPortalController::class, 'storePengajuan'])->name('siswa.portal.pengajuan.store');
        Route::delete('/siswa/portal/relasi', [SiswaPortalController::class, 'destroyRelasi'])->name('siswa.portal.relasi.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:superadmin|admin|operator|kamad')->group(function () {
        Route::get('/kelembagaan/identitas', [KelembagaanController::class, 'identitas'])->name('kelembagaan.identitas');
        Route::post('tahun-ajaran/{tahunAjaran}/aktifkan', [TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');
        Route::resource('tahun-ajaran', TahunAjaranController::class)
            ->parameters(['tahun-ajaran' => 'tahunAjaran'])
            ->except(['show']);
        Route::resource('gtk', GtkController::class)->except(['show']);
        Route::get('siswa/create', [SiswaController::class, 'create'])->name('siswa.create');
        Route::post('siswa', [SiswaController::class, 'store'])->name('siswa.store');
        Route::post('rombel/{rombel}/anggota', [RombelController::class, 'storeAnggota'])->name('rombel.anggota.store');
        Route::delete('rombel/{rombel}/anggota/{siswa}', [RombelController::class, 'destroyAnggota'])->name('rombel.anggota.destroy');
        Route::resource('rombel', RombelController::class)->except(['index', 'show']);
        Route::view('/ppdb', 'pages.soon', [
            'heading' => 'PPDB',
            'subheading' => 'Penerimaan peserta didik baru',
            'keterangan' => 'Menu PPDB disiapkan di sini. Alur pendaftaran akan menyusul.',
        ])->name('ppdb.index');
        Route::view('/mutasi', 'pages.soon', [
            'heading' => 'Mutasi',
            'subheading' => 'Mutasi masuk dan keluar',
            'keterangan' => 'Menu mutasi disiapkan di sini. Proses pindah madrasah akan menyusul.',
        ])->name('mutasi.index');
        Route::view('/alumni', 'pages.soon', [
            'heading' => 'Alumni',
            'subheading' => 'Data lulusan',
            'keterangan' => 'Menu alumni disiapkan di sini. Rekap lulusan akan menyusul.',
        ])->name('alumni.index');
    });

    Route::middleware('role:superadmin')->group(function () {
        Route::put('/kelembagaan/identitas', [KelembagaanController::class, 'updateIdentitas'])->name('kelembagaan.identitas.update');
        Route::resource('pengguna', UserController::class)
            ->parameters(['pengguna' => 'user'])
            ->except(['show']);
    });

    Route::get('siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('siswa/{siswa}', [SiswaController::class, 'show'])->name('siswa.show')->whereUuid('siswa');
    Route::get('siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit')->whereUuid('siswa');
    Route::put('siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update')->whereUuid('siswa');
    Route::post('siswa/{siswa}/pengajuan/{pengajuan}', [SiswaController::class, 'prosesPengajuan'])->name('siswa.pengajuan.proses')->whereUuid('siswa');
    Route::post('siswa/{siswa}/reset-password', [SiswaController::class, 'resetPassword'])->name('siswa.reset-password')->whereUuid('siswa');
    Route::delete('siswa/{siswa}/relasi', [SiswaController::class, 'destroyRelasi'])->name('siswa.relasi.destroy')->whereUuid('siswa');
    Route::get('rombel', [RombelController::class, 'index'])->name('rombel.index');
    Route::get('rombel/{rombel}', [RombelController::class, 'show'])->name('rombel.show');
});
