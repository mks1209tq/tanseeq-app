<?php

use Illuminate\Support\Facades\Route;
use Modules\UI\Http\Controllers\UIController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('uis', UIController::class)->names('ui');
});
