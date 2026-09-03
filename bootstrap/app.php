<?php

use App\Http\Middleware\EnsureSiswaApi;
use App\Http\Middleware\EnsureSiswaPasswordChanged;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('siswa/portal*') || $request->is('siswa/keluar') || $request->is('siswa/password*')) {
                return route('siswa.masuk');
            }

            return route('login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('siswa/masuk')) {
                return route('siswa.portal');
            }

            return route('dashboard');
        });
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'siswa.password' => EnsureSiswaPasswordChanged::class,
            'siswa.api' => EnsureSiswaApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
