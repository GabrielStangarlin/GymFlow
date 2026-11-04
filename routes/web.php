<?php

use App\Http\Controllers\ActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientAreaController;
use App\Http\Controllers\Crm\EnrollmentController;
use App\Http\Controllers\Crm\ScanController;
use App\Http\Controllers\Crm\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/ativar/{token}', [ActivationController::class, 'show'])->name('activation.show');
    Route::post('/ativar', [ActivationController::class, 'store'])->name('activation.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/', [LoginController::class, 'home'])->middleware(['auth', 'active'])->name('home');

Route::middleware(['auth', 'active', 'role:admin,staff'])->prefix('crm')->name('crm.')->group(function (): void {
    Route::view('/', 'crm.dashboard')->name('dashboard');

    Route::get('/inscricoes/nova', [EnrollmentController::class, 'create'])->name('enrollments.create');
    Route::post('/inscricoes', [EnrollmentController::class, 'store'])->name('enrollments.store');

    Route::get('/ler-qr', [ScanController::class, 'show'])->name('scan.show');
    Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');

    Route::middleware('role:admin')->prefix('funcionarios')->name('staff.')->group(function (): void {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/novo', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::patch('/{user}/desativar', [StaffController::class, 'deactivate'])->name('deactivate');
        Route::patch('/{user}/ativar', [StaffController::class, 'activate'])->name('activate');
    });
});

Route::middleware(['auth', 'active', 'role:client'])->group(function (): void {
    Route::get('/aluno', ClientAreaController::class)->name('client.qr');
});
