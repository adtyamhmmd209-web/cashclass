<?php

use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API v1 Routes untuk CashClass
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Transaksi endpoints
    Route::get('/transactions', [TransactionApiController::class, 'index']);
    Route::post('/transactions', [TransactionApiController::class, 'store'])->middleware('role:bendahara');
    Route::get('/transactions/{id}', [TransactionApiController::class, 'show']);
    Route::put('/transactions/{id}', [TransactionApiController::class, 'update'])->middleware('role:bendahara');
    Route::delete('/transactions/{id}', [TransactionApiController::class, 'destroy'])->middleware('role:bendahara');

    // Dashboard stats
    Route::get('/dashboard/stats', [TransactionApiController::class, 'dashboardStats']);
});
