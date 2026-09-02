<?php

use App\Http\Controllers\Api\V1\SiswaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/siswa', [SiswaController::class, 'show']);
    });
});
