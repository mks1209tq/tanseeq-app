<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthorizationDebug\Http\Controllers\AuthorizationDebugController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authorizationdebugs', AuthorizationDebugController::class)->names('authorizationdebug');
});
