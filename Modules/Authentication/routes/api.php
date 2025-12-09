<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\AuthController;
use Modules\Authentication\Http\Controllers\Api\UserController;

// Public authentication routes
Route::post('login', [AuthController::class, 'login'])->name('api.login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('user', [AuthController::class, 'user'])->name('api.user');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    // User endpoints for microservice communication
    Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');
    Route::post('users/{id}/has-role', [UserController::class, 'hasRole'])->name('users.has-role');
    Route::get('users/{id}/roles', [UserController::class, 'roles'])->name('users.roles');
});
