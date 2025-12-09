<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Http\Controllers\Api\AuthorizationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    // Authorization check endpoint for microservice communication
    Route::post('authorizations/check', [AuthorizationController::class, 'check'])->name('authorizations.check');
});
