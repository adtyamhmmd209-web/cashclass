<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassFeeController;
use App\Http\Controllers\ErrorReportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WaliKelasController;
use App\Http\Controllers\WaliAccountController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(match (auth()->user()->role) {
            'siswa' => 'student.dashboard',
            'wali_kelas' => 'walikelas.dashboard',
            default => 'dashboard',
        });
    }

    return view('auth.login');
});

// Dashboard Bendahara
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Routes Transaksi (Hanya Bendahara)
Route::middleware(['auth', 'role:bendahara'])->prefix('transactions')->name('transactions.')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('index');
    Route::get('/create', [TransactionController::class, 'create'])->name('create');
    Route::post('/', [TransactionController::class, 'store'])->name('store');
    Route::get('/{id}', [TransactionController::class, 'show'])->name('show');
    Route::get('/{id}/proof', [TransactionController::class, 'proof'])->name('proof');
    Route::get('/{id}/edit', [TransactionController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TransactionController::class, 'update'])->name('update');
    Route::delete('/{id}', [TransactionController::class, 'destroy'])->name('destroy');
});

// Routes Pembayaran Siswa (Bendahara)
Route::middleware(['auth', 'role:bendahara'])->prefix('payments')->name('payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/cash', [PaymentController::class, 'storeCash'])->name('cash.store');
    Route::patch('/{payment}', [PaymentController::class, 'review'])->name('review');
    Route::patch('/{payment}/refund', [PaymentController::class, 'refund'])->name('cash.refund');
    Route::get('/{payment}/proof', [PaymentController::class, 'proof'])->name('proof');
});

// Tagihan kelas yang diterbitkan per periode oleh bendahara
Route::middleware(['auth', 'role:bendahara'])->prefix('class-fees')->name('class-fees.')->group(function () {
    Route::get('/', [ClassFeeController::class, 'index'])->name('index');
    Route::get('/create', [ClassFeeController::class, 'create'])->name('create');
    Route::post('/', [ClassFeeController::class, 'store'])->name('store');
    Route::get('/{classFee}/edit', [ClassFeeController::class, 'edit'])->name('edit');
    Route::put('/{classFee}', [ClassFeeController::class, 'update'])->name('update');
    Route::delete('/{classFee}', [ClassFeeController::class, 'destroy'])->name('destroy');
});

// Routes Laporan (Bendahara)
Route::middleware(['auth', 'role:bendahara'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/export-pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
});

// Routes Data Siswa (Bendahara dan Wali Kelas)
Route::middleware(['auth', 'role:bendahara,wali_kelas'])->prefix('students')->name('students.')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('index');
    Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit');
    Route::put('/{student}', [StudentController::class, 'update'])->name('update');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
    Route::get('/{student}', [StudentController::class, 'show'])->name('show');
});

// Routes Pengaturan (Bendahara)
Route::middleware(['auth', 'role:bendahara'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::post('/payment-accounts', [SettingController::class, 'storeAccount'])->name('accounts.store');
    Route::patch('/payment-accounts/{account}', [SettingController::class, 'toggleAccount'])->name('accounts.update');
    Route::post('/categories', [SettingController::class, 'storeCategory'])->name('categories.store');
    Route::delete('/categories/{category}', [SettingController::class, 'destroyCategory'])->name('categories.destroy');
});

// Profile routes
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==================== ROUTES SISWA ====================
Route::middleware(['auth', 'role:siswa'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/fees', [StudentDashboardController::class, 'fees'])->name('fees');
    Route::get('/transactions', [StudentDashboardController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/create', [StudentDashboardController::class, 'createTransaction'])->name('transactions.create');
    Route::post('/transactions', [StudentDashboardController::class, 'storeTransaction'])->name('transactions.store');
    Route::get('/payments/{payment}/proof', [StudentDashboardController::class, 'proof'])->name('payments.proof');
    Route::get('/schedule', [StudentDashboardController::class, 'schedule'])->name('schedule');
    Route::get('/profile', [StudentDashboardController::class, 'profile'])->name('profile');
    Route::get('/settings', [StudentDashboardController::class, 'settings'])->name('settings');
});

// ==================== ROUTES WALI KELAS ====================
Route::middleware(['auth', 'role:wali_kelas'])->prefix('walikelas')->name('walikelas.')->group(function () {
    Route::get('/dashboard', [WaliKelasController::class, 'dashboard'])->name('dashboard');
    Route::get('/class', [WaliAccountController::class, 'accounts'])->name('class');
    Route::get('/accounts/create', [WaliAccountController::class, 'createAccount'])->name('accounts.create');
    Route::post('/accounts', [WaliAccountController::class, 'storeAccount'])->name('accounts.store');
    Route::put('/bendahara', [WaliAccountController::class, 'assignTreasurer'])->name('treasurer.assign');
    Route::post('/records/announcement', [WaliKelasController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::delete('/records/announcement/{id}', [WaliKelasController::class, 'destroyAnnouncement'])->whereNumber('id')->name('announcements.destroy');
    Route::get('/reports', [WaliKelasController::class, 'reports'])->name('reports');
    Route::get('/reports/print', [WaliKelasController::class, 'exportPdf'])->name('reports.print');
    Route::get('/announcements', [WaliKelasController::class, 'announcements'])->name('announcements');
});

// ==================== API TOKEN ROUTES (S2-03) ====================
Route::middleware(['auth'])->group(function () {
    Route::post('/api-token', [AuthenticatedSessionController::class, 'generateApiToken'])
        ->name('api.token.generate');

    Route::delete('/api-token', [AuthenticatedSessionController::class, 'revokeApiToken'])
        ->name('api.token.revoke');

    Route::delete('/api-token/all', [AuthenticatedSessionController::class, 'revokeAllApiTokens'])
        ->name('api.token.revoke-all');
});

// Route authentication
require __DIR__.'/auth.php';
Route::get('/error-reports/create', [ErrorReportController::class, 'create'])->name('error-reports.create');
Route::post('/error-reports', [ErrorReportController::class, 'store'])->middleware('throttle:5,1')->name('error-reports.store');
Route::middleware(['auth', 'role:bendahara'])->group(function () {
    Route::get('/error-reports', [ErrorReportController::class, 'index'])->name('error-reports.index');
    Route::patch('/error-reports/{id}', [ErrorReportController::class, 'update'])->whereUuid('id')->name('error-reports.update');
});

// Routes Notifikasi
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::patch('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
    Route::get('/{notification}', [NotificationController::class, 'read'])->name('read');
});
