<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\Authentication\Http\Controllers\Auth\ConfirmablePasswordController;
use Modules\Authentication\Http\Controllers\Auth\EmailVerificationNotificationController;
use Modules\Authentication\Http\Controllers\Auth\EmailVerificationPromptController;
use Modules\Authentication\Http\Controllers\Auth\NewPasswordController;
use Modules\Authentication\Http\Controllers\Auth\PasswordResetLinkController;
use Modules\Authentication\Http\Controllers\Auth\RegisteredUserController;
use Modules\Authentication\Http\Controllers\Auth\VerifyEmailController;
use Modules\Authentication\Http\Controllers\ProfileController;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// Registration routes - only for super-admin
Route::middleware(['auth', 'auth.object:USER_MANAGEMENT'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'auth.object:PROFILE_MANAGEMENT'])->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'auth.object:AUTHENTICATION_SETTINGS'])->prefix('admin/authentication')->name('authentication.settings.')->group(function () {
    Route::get('settings', [\Modules\Authentication\Http\Controllers\Admin\AuthSettingsController::class, 'edit'])->name('edit');
    Route::put('settings', [\Modules\Authentication\Http\Controllers\Admin\AuthSettingsController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'auth.object:USER_MANAGEMENT'])->prefix('admin')->name('admin.users.')->group(function () {
    Route::get('users/create', [\Modules\Authentication\Http\Controllers\Admin\UserController::class, 'create'])->name('create');
    Route::post('users', [\Modules\Authentication\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
});

// Authentication Dashboard
Route::middleware(['auth', 'auth.object:DASHBOARD_ACCESS'])->get('authentication/dashboard', [\Modules\Authentication\Http\Controllers\AuthController::class, 'index'])->name('authentication.dashboard');
