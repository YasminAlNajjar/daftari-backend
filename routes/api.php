<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\ReportVerificationController;

Route::prefix('auth/otp')->group(function () {
    Route::post('/send', [AuthController::class, 'sendOtp']);
    Route::post('/verify', [AuthController::class, 'verifyOtp']);
});

Route::post('/auth/complete-profile', [AuthController::class, 'completeProfile']); 

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
//Customer routes
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::patch('/customers/{customer}',[CustomerController::class, 'update'])->whereNumber('customer');
    Route::get('/customers/{customer}',[CustomerController::class, 'show'])->whereNumber('customer');
    Route::delete('/customers/{customer}',[CustomerController::class, 'destroy'])->whereNumber('customer');
//transaction routes
    Route::post('/customers/{customer}/transactions',[TransactionController::class, 'store'])->whereNumber('customer');
    Route::get('/customers/{customer}/transactions',[TransactionController::class, 'index'])->whereNumber('customer');
    Route::get('/customers/{customer}/transactions/{transaction}',[TransactionController::class, 'show'])->whereNumber('customer')->whereNumber('transaction');
    Route::patch('/customers/{customer}/transactions/{transaction}',[TransactionController::class, 'update'])->whereNumber('customer')->whereNumber('transaction');
    Route::delete('/customers/{customer}/transactions/{transaction}',[TransactionController::class, 'destroy'])->whereNumber('customer')->whereNumber('transaction');
//report routes
    Route::get('/reports/summary',[ReportController::class, 'financialSummary']);
    Route::get('/reports/daily',[ReportController::class, 'dailyInventory']);
    Route::get('/reports/customer-statement',[ReportController::class, 'customerStatement']);
    Route::get('/reports/transactions', [ReportController::class, 'generalTransactions']);
    Route::get('/reports/customer-balances',[ReportController::class, 'customerBalances']);
//export report 
    Route::post('/reports/exports', [ReportExportController::class, 'store']);
    Route::get('/reports/exports/{id}', [ReportExportController::class, 'show'])->whereNumber('id');
    Route::get('/reports/exports/{export}/download',[ReportExportController::class, 'download'])->whereNumber('export');
//QR
    Route::get('/reports/verify/{token}',[ReportVerificationController::class, 'verify']);

});