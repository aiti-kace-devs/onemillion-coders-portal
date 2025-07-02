<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\RegisteredUserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Admin\EmailVerificationPromptController;
use App\Http\Controllers\Admin\EmailVerificationNotificationController;
use App\Http\Controllers\Admin\VerifyEmailController;





Route::prefix('admin')->middleware('theme:admin')->name('admin.')->group(function () {

    Route::middleware(['guest:admin'])->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    });


    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::view('/register', 'auth.register')->name('register');
        Route::post('/register', [RegisteredUserController::class, 'store']);
    });
});
