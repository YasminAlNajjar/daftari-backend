<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TransactionController;

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

});