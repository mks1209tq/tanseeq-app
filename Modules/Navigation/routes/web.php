<?php

use Illuminate\Support\Facades\Route;
use Modules\Navigation\Http\Controllers\NavigationController;

Route::middleware(['auth'])->group(function () {
    Route::get('navigation', [NavigationController::class, 'index'])->name('navigation.index');
});
