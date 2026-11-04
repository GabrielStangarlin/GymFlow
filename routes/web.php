<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/', [LoginController::class, 'home'])->middleware(['auth', 'active'])->name('home');

Route::middleware(['auth', 'active', 'role:admin,staff'])->prefix('crm')->name('crm.')->group(function (): void {
    Route::view('/', 'crm.dashboard')->name('dashboard');
});

Route::middleware(['auth', 'active', 'role:client'])->group(function (): void {
    Route::view('/aluno', 'client.qr')->name('client.qr');
});
