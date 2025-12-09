<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\UserController;

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    // User endpoints for microservice communication
    Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');
    Route::post('users/{id}/has-role', [UserController::class, 'hasRole'])->name('users.has-role');
    Route::get('users/{id}/roles', [UserController::class, 'roles'])->name('users.roles');
});
