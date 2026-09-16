<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\Web\ReportVerificationController;

Route::get('/verify/report/{token}',[ReportVerificationController::class, 'show'])->name('reports.verify.page');

Route::post('/verify/report/{token}',[ReportVerificationController::class, 'verifyFile'])->name('reports.verify.file');
