<?php

use App\Http\Controllers\SsoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

Route::prefix('catera')->group(function () {
    Route::get('/sso/verify', [SsoController::class, 'verify'])->name('sso.verify');
    Route::post('/logout', [SsoController::class, 'destroy'])->name('logout.app');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');
        Route::livewire('authorized', 'pages::authorized.index')->name('authorized.index');
        Route::livewire('quota-schedules', 'pages::quota_schedule.index')->name('quota_schedules.index');
        Route::livewire('access-logs', 'pages::access_logs.index')->name('access_logs.index');
    });

    require __DIR__.'/settings.php';
});

Route::prefix('catera/api')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::post('/webhook/clear-permission-cache', function (Request $request) {

        if ($request->header('X-Secret-Token') !== config('services.webhook.secret')) {
            Log::info('Token tidak valid.');
            abort(403, 'Akses Ditolak: Token tidak valid.');
        }

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        Log::info('valdi sudah token');

        return response()->json([
            'status' => 'success',
            'message' => 'Cache permission berhasil dibersihkan.',
        ]);
    });

    Route::get('/test', function () {
        return response()->json([
            'message' => 'Connection successful',
        ]);
    });
});
