<?php

use App\Http\Controllers\MonitorController;
use App\Http\Controllers\StatusPageController;
use App\Http\Middleware\AdminAccess;
use Illuminate\Support\Facades\Route;

// Status page pública: FORA do grupo protegido, de propósito.
// Limite de 60 requisições por minuto por IP.
Route::get('status', StatusPageController::class)
    ->middleware('throttle:60,1')
    ->name('status');

// Painel administrativo (protegido).
Route::middleware(AdminAccess::class)->group(function () {
    Route::redirect('/', '/monitors');

    Route::resource('monitors', MonitorController::class);
    Route::post('monitors/{monitor}/toggle', [MonitorController::class, 'toggle'])
        ->name('monitors.toggle');
});