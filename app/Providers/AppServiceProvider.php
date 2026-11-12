<?php

namespace App\Providers;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Policies\TunjanganDokumenPolicy;
use App\Support\Navigasi;
use App\Support\TalimSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone'));

        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, bool $isValid): bool {
            if (! $isValid) {
                return false;
            }

            return ! TalimSession::revokeIfStale($token);
        });

        Siswa::creating(function (Siswa $siswa): void {
            $siswa->ensurePasswordAwal();
        });

        // Ability berbasis Gtk (bukan instance TunjanganDokumen).
        Gate::define('viewGtk', [TunjanganDokumenPolicy::class, 'viewGtk']);
        Gate::define('upload', [TunjanganDokumenPolicy::class, 'upload']);

        View::composer('layouts.app', function ($view) {
            $view->with([
                'tahunAktif' => TahunAjaran::aktif(),
                'menuEmis' => Navigasi::untuk(auth()->user()),
            ]);
        });

        View::composer('layouts.siswa', function ($view) {
            $view->with([
                'tahunAktif' => TahunAjaran::aktif(),
            ]);
        });
    }
}
