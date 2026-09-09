<?php

use App\Http\Controllers\AppMaintenanceController;
use App\Http\Controllers\AppMenuController;
use App\Http\Controllers\AppUpdateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GtkController;
use App\Http\Controllers\GtkMonitoringController;
use App\Http\Controllers\KelembagaanController;
use App\Http\Controllers\Manajemen\DatabaseController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\NotifikasiPembacaController;
use App\Http\Controllers\NotifMediaController;
use App\Http\Controllers\PeriodePendataanController;
use App\Http\Controllers\Portal\SiswaAuthController;
use App\Http\Controllers\Portal\SiswaPortalController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SiswaMonitoringController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\Talim\WaliKelasController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebviewEnterController;
use Illuminate\Support\Facades\Route;

Route::get('/webview/enter', WebviewEnterController::class)->name('webview.enter');

Route::get('/privacy-policy', PrivacyPolicyController::class)->name('privacy-policy');
Route::redirect('/kebijakan-privasi', '/privacy-policy');

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'create'])->name('login');
    Route::post('/', [LoginController::class, 'store']);
});

Route::middleware('guest:siswa')->group(function () {
    Route::get('/siswa/masuk', [SiswaAuthController::class, 'create'])->name('siswa.masuk');
    Route::post('/siswa/masuk', [SiswaAuthController::class, 'store']);
});

Route::redirect('/login', '/');

Route::get('/portofolio/cek/{siswa}', [SiswaController::class, 'cekPortofolio'])
    ->middleware('signed')
    ->name('portofolio.cek')
    ->whereUuid('siswa');

Route::get('/kartu-e-pelajar/cek/{siswa}', [SiswaController::class, 'cekKartuEPelajar'])
    ->middleware('signed')
    ->name('kartu-e-pelajar.cek')
    ->whereUuid('siswa');

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

    Route::middleware('role:wali_kelas|superadmin|admin')->group(function () {
        Route::get('/talim/wali', WaliKelasController::class)->name('talim.wali');
    });

    Route::middleware('role:superadmin|admin|operator|kamad')->group(function () {
        Route::get('/kelembagaan/identitas', [KelembagaanController::class, 'identitas'])->name('kelembagaan.identitas');
        Route::post('tahun-ajaran/{tahunAjaran}/aktifkan', [TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');
        Route::resource('tahun-ajaran', TahunAjaranController::class)
            ->parameters(['tahun-ajaran' => 'tahunAjaran'])
            ->except(['show']);
        Route::get('gtk/monitoring', [GtkMonitoringController::class, 'index'])->name('gtk.monitoring');
        Route::resource('gtk', GtkController::class)->except(['show']);
        Route::get('pengaturan/update-app', [AppUpdateController::class, 'index'])->name('app-updates.index');
        Route::post('pengaturan/update-app', [AppUpdateController::class, 'store'])->name('app-updates.store');
        Route::get('pengaturan/maintenance', [AppMaintenanceController::class, 'index'])->name('app-maintenance.index');
        Route::post('pengaturan/maintenance', [AppMaintenanceController::class, 'store'])->name('app-maintenance.store');
        Route::get('pengaturan/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
        Route::get('pengaturan/notifikasi/media', [NotifMediaController::class, 'index'])->name('notifikasi.media.index');
        Route::post('pengaturan/notifikasi/media', [NotifMediaController::class, 'store'])->name('notifikasi.media.store');
        Route::delete('pengaturan/notifikasi/media/{media}', [NotifMediaController::class, 'destroy'])->name('notifikasi.media.destroy');
        Route::post('pengaturan/notifikasi', [NotifikasiController::class, 'store'])->name('notifikasi.store');
        Route::put('pengaturan/notifikasi/{notifikasi}', [NotifikasiController::class, 'update'])->name('notifikasi.update');
        Route::delete('pengaturan/notifikasi/{notifikasi}', [NotifikasiController::class, 'destroy'])->name('notifikasi.destroy');
        Route::post('pengaturan/notifikasi/{notifikasi}/resend', [NotifikasiController::class, 'resend'])->name('notifikasi.resend');
        Route::get('pengaturan/notifikasi/{notifikasi}/pembaca', [NotifikasiPembacaController::class, 'show'])->name('notifikasi.pembaca');
        Route::redirect('pengaturan/pengumuman', '/pengaturan/notifikasi')->name('pengumuman.index');
        Route::get('pengaturan/kalender', [CalendarEventController::class, 'index'])->name('calendar-events.index');
        Route::post('pengaturan/kalender', [CalendarEventController::class, 'store'])->name('calendar-events.store');
        Route::put('pengaturan/kalender/{calendarEvent}', [CalendarEventController::class, 'update'])->name('calendar-events.update');
        Route::delete('pengaturan/kalender/{calendarEvent}', [CalendarEventController::class, 'destroy'])->name('calendar-events.destroy');
        Route::get('pengaturan/menus', [AppMenuController::class, 'index'])->name('app-menus.index');
        Route::post('pengaturan/menus', [AppMenuController::class, 'store'])->name('app-menus.store');
        Route::put('pengaturan/menus/{appMenu}', [AppMenuController::class, 'update'])->name('app-menus.update');
        Route::delete('pengaturan/menus/{appMenu}', [AppMenuController::class, 'destroy'])->name('app-menus.destroy');
        Route::post('pengaturan/menus/{appMenu}/move-up', [AppMenuController::class, 'moveUp'])->name('app-menus.move-up');
        Route::post('pengaturan/menus/{appMenu}/move-down', [AppMenuController::class, 'moveDown'])->name('app-menus.move-down');
        Route::get('siswa/create', [SiswaController::class, 'create'])->name('siswa.create');
        Route::post('siswa', [SiswaController::class, 'store'])->name('siswa.store');
        Route::post('siswa/generate-nis', [SiswaController::class, 'generateNis'])->name('siswa.generate-nis');
        Route::put('siswa/periode-pendataan', [PeriodePendataanController::class, 'update'])->name('siswa.periode-pendataan.update');
        Route::post('rombel/{rombel}/anggota', [RombelController::class, 'storeAnggota'])->name('rombel.anggota.store');
        Route::post('rombel/{rombel}/anggota/kosongkan', [RombelController::class, 'kosongkanAnggota'])->name('rombel.anggota.kosongkan');
        Route::post('rombel/{rombel}/anggota/{siswa}/pindah', [RombelController::class, 'pindahAnggota'])->name('rombel.anggota.pindah');
        Route::delete('rombel/{rombel}/anggota/{siswa}', [RombelController::class, 'destroyAnggota'])->name('rombel.anggota.destroy');
        Route::post('rombel/sync-simpatisans', [RombelController::class, 'syncFromSimpatisans'])->name('rombel.sync-simpatisans');
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
        Route::post('gtk/{gtk}/akun', [GtkController::class, 'buatAkun'])->name('gtk.akun.store');
        Route::post('gtk/{gtk}/akun/reset-password', [GtkController::class, 'resetPassword'])->name('gtk.akun.reset');
        Route::get('manajemen/database', [DatabaseController::class, 'index'])->name('manajemen.database');
        Route::get('manajemen/database/siswa/template', [DatabaseController::class, 'templateSiswa'])
            ->name('manajemen.database.siswa.template');
        Route::get('manajemen/database/siswa/ekspor-duplikat', [DatabaseController::class, 'eksporDuplikatSiswa'])
            ->name('manajemen.database.siswa.ekspor-duplikat');
        Route::post('manajemen/database/siswa/impor', [DatabaseController::class, 'imporSiswa'])
            ->name('manajemen.database.siswa.impor');
        Route::post('manajemen/database/jurnal/impor', [DatabaseController::class, 'imporJurnal'])
            ->name('manajemen.database.jurnal.impor');
        Route::post('manajemen/database/{modul}/kosongkan', [DatabaseController::class, 'kosongkan'])
            ->name('manajemen.database.kosongkan')
            ->where('modul', 'siswa|gtk|rombel|tahun-ajaran|periode-pendataan|jurnal|notifikasi|identitas|app-settings');
    });

    Route::get('siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('siswa/monitoring', [SiswaMonitoringController::class, 'index'])->name('siswa.monitoring');
    Route::get('siswa/monitoring/export', [SiswaMonitoringController::class, 'export'])->name('siswa.monitoring.export');
    Route::get('siswa/{siswa}', [SiswaController::class, 'show'])->name('siswa.show')->whereUuid('siswa');
    Route::get('siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit')->whereUuid('siswa');
    Route::put('siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update')->whereUuid('siswa');
    Route::post('siswa/{siswa}/pengajuan/{pengajuan}', [SiswaController::class, 'prosesPengajuan'])->name('siswa.pengajuan.proses')->whereUuid('siswa');
    Route::post('siswa/{siswa}/reset-password', [SiswaController::class, 'resetPassword'])->name('siswa.reset-password')->whereUuid('siswa');
    Route::get('siswa/{siswa}/portofolio', [SiswaController::class, 'portofolio'])->name('siswa.portofolio')->whereUuid('siswa');
    Route::get('siswa/{siswa}/portofolio/stream', [SiswaController::class, 'portofolioStream'])->name('siswa.portofolio.stream')->whereUuid('siswa');
    Route::get('siswa/{siswa}/portofolio.pdf', [SiswaController::class, 'portofolioDownload'])->name('siswa.portofolio.download')->whereUuid('siswa');
    Route::get('siswa/{siswa}/kartu', [SiswaController::class, 'kartu'])->name('siswa.kartu')->whereUuid('siswa');
    Route::get('siswa/{siswa}/kartu/stream', [SiswaController::class, 'kartuStream'])->name('siswa.kartu.stream')->whereUuid('siswa');
    Route::get('siswa/{siswa}/kartu.pdf', [SiswaController::class, 'kartuDownload'])->name('siswa.kartu.download')->whereUuid('siswa');
    Route::get('siswa/{siswa}/pernyataan/{jenis}/unduh', [SiswaController::class, 'pernyataanDownload'])
        ->name('siswa.pernyataan.download')
        ->whereUuid('siswa')
        ->where('jenis', 'biodata|peserta-didik');
    Route::get('siswa/{siswa}/pernyataan/{jenis}/stream', [SiswaController::class, 'pernyataanStream'])
        ->name('siswa.pernyataan.stream')
        ->whereUuid('siswa')
        ->where('jenis', 'biodata|peserta-didik');
    Route::delete('siswa/{siswa}/pernyataan', [SiswaController::class, 'batalkanPernyataan'])->name('siswa.pernyataan.batalkan')->whereUuid('siswa');
    Route::delete('siswa/{siswa}/relasi', [SiswaController::class, 'destroyRelasi'])->name('siswa.relasi.destroy')->whereUuid('siswa');
    Route::delete('siswa/{siswa}/dokumen/{jenis}', [SiswaController::class, 'destroyDokumen'])
        ->name('siswa.dokumen.destroy')
        ->whereUuid('siswa')
        ->where('jenis', 'kk|akta_lahir|kip|kks|pkh|ijazah_sd');
    Route::get('siswa/{siswa}/dokumen/{jenis}', [SiswaController::class, 'downloadDokumen'])
        ->name('siswa.dokumen.download')
        ->whereUuid('siswa')
        ->where('jenis', 'kk|akta_lahir|kip|kks|pkh|ijazah_sd');
    Route::get('siswa/{siswa}/foto', [SiswaController::class, 'downloadFoto'])
        ->name('siswa.foto.download')
        ->whereUuid('siswa');
    Route::get('rombel', [RombelController::class, 'index'])->name('rombel.index');
    Route::get('rombel/{rombel}', [RombelController::class, 'show'])->name('rombel.show');
});
