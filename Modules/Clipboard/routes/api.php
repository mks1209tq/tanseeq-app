<?php

use Illuminate\Support\Facades\Route;
use Modules\Clipboard\Http\Controllers\ClipboardController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('clipboards', ClipboardController::class)->names('clipboard');
});
