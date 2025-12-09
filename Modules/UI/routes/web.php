<?php

use Illuminate\Support\Facades\Route;
use Modules\UI\Http\Controllers\UIController;

Route::middleware(['auth', 'verified', 'auth.object:UI_MANAGEMENT'])->group(function () {
    Route::resource('uis', UIController::class)->names('ui');
});
