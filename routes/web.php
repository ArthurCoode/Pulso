<?php

use App\Http\Controllers\MonitorController;
use App\Http\Middleware\AdminAccess;
use Illuminate\Support\Facades\Route;

// Painel administrativo (protegido). A status page pública da Fase 4
// ficará FORA deste grupo.
Route::middleware(AdminAccess::class)->group(function () {
    Route::redirect('/', '/monitors');

    Route::resource('monitors', MonitorController::class);
    Route::post('monitors/{monitor}/toggle', [MonitorController::class, 'toggle'])
        ->name('monitors.toggle');
});