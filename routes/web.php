<?php

use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/sso/verify', [SsoController::class, 'verify'])->name('sso.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('authorized', 'pages::authorized.index')->name('authorized.index');
    Route::livewire('unauthorized', 'pages::unauthorized.index')->name('unauthorized.index');
    Route::livewire('registereds', 'pages::registered.index')->name('registereds.index');
});

require __DIR__.'/settings.php';
