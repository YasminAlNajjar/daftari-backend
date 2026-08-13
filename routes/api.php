<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;

Route::prefix('auth/otp')->group(function () {
    Route::post('/send', [AuthController::class, 'sendOtp']);
    Route::post('/verify', [AuthController::class, 'verifyOtp']);
});

Route::post('/auth/complete-profile', [AuthController::class, 'completeProfile']); 

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::patch('/customers/{customer}',[CustomerController::class, 'update'])->whereNumber('customer');
    Route::get('/customers/{customer}',[CustomerController::class, 'show'])->whereNumber('customer');
});