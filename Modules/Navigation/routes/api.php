<?php

use Illuminate\Support\Facades\Route;
use Modules\Navigation\Http\Controllers\NavigationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('navigations', NavigationController::class)->names('navigation');
});
