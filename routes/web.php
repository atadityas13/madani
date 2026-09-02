<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GtkController;
use App\Http\Controllers\KelembagaanController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TahunAjaranController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'create'])->name('login');
    Route::post('/', [LoginController::class, 'store']);
});

Route::redirect('/login', '/');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/kelembagaan/identitas', [KelembagaanController::class, 'identitas'])->name('kelembagaan.identitas');
    Route::post('tahun-ajaran/{tahunAjaran}/aktifkan', [TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');
    Route::resource('tahun-ajaran', TahunAjaranController::class)
        ->parameters(['tahun-ajaran' => 'tahunAjaran'])
        ->except(['show']);
    Route::resource('gtk', GtkController::class)->except(['show']);
    Route::post('rombel/{rombel}/anggota', [RombelController::class, 'storeAnggota'])->name('rombel.anggota.store');
    Route::delete('rombel/{rombel}/anggota/{siswa}', [RombelController::class, 'destroyAnggota'])->name('rombel.anggota.destroy');
    Route::resource('rombel', RombelController::class);
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
    Route::resource('siswa', SiswaController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::delete('siswa/{siswa}/relasi', [SiswaController::class, 'destroyRelasi'])->name('siswa.relasi.destroy');
});
