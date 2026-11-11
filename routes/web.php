<?php

use App\Http\Controllers\ActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientAreaController;
use App\Http\Controllers\Crm\AccessLogController;
use App\Http\Controllers\Crm\BillingPeriodController;
use App\Http\Controllers\Crm\ClientController;
use App\Http\Controllers\Crm\ClientSubscriptionController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\EnrollmentController;
use App\Http\Controllers\Crm\PlanController;
use App\Http\Controllers\Crm\ScanController;
use App\Http\Controllers\Crm\StaffController;
use App\Http\Controllers\Crm\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/ativar/{token}', [ActivationController::class, 'show'])->name('activation.show');
    Route::post('/ativar', [ActivationController::class, 'store'])->name('activation.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/', [LoginController::class, 'home'])->middleware(['auth', 'active'])->name('home');

Route::middleware(['auth', 'active', 'role:admin,staff'])->prefix('crm')->name('crm.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/inscricoes/nova', [EnrollmentController::class, 'create'])->name('enrollments.create');
    Route::post('/inscricoes', [EnrollmentController::class, 'store'])->name('enrollments.store');

    Route::prefix('alunos')->name('clients.')->group(function (): void {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/{client}', [ClientController::class, 'show'])->name('show');
        Route::get('/{client}/editar', [ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClientController::class, 'update'])->name('update');
        Route::patch('/{client}/desativar', [ClientController::class, 'deactivate'])->name('deactivate');
        Route::patch('/{client}/ativar', [ClientController::class, 'activate'])->name('activate');
        Route::post('/{client}/reenviar-ativacao', [ClientController::class, 'resendActivation'])->name('resend-activation');
        Route::get('/{client}/assinaturas/nova', [ClientSubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('/{client}/assinaturas', [ClientSubscriptionController::class, 'store'])->name('subscriptions.store');
    });

    Route::prefix('assinaturas')->name('subscriptions.')->group(function (): void {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::post('/{subscription}/pagar', [SubscriptionController::class, 'pay'])->name('pay');
        Route::post('/{subscription}/regularizar-divida', [SubscriptionController::class, 'settleDebt'])->name('settle-debt');
        Route::post('/{subscription}/cancelar', [SubscriptionController::class, 'cancel'])->name('cancel');
    });

    Route::resource('planos', PlanController::class)->except(['show', 'destroy'])->names('plans')->parameters(['planos' => 'plan']);
    Route::get('/periodicidades', [BillingPeriodController::class, 'edit'])->name('billing-periods.edit');
    Route::put('/periodicidades', [BillingPeriodController::class, 'update'])->name('billing-periods.update');

    Route::get('/entradas-saidas', [AccessLogController::class, 'index'])->name('access-logs.index');

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
