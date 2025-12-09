<?php

use Illuminate\Support\Facades\Route;
use Modules\Navigation\Http\Controllers\NavigationController;

Route::middleware(['auth'])->group(function () {
    Route::get('navigation', [NavigationController::class, 'index'])->name('navigation.index');
    Route::get('api/navigation/search', [NavigationController::class, 'search'])->name('navigation.search');
    Route::get('api/quick-launch/models', [NavigationController::class, 'getModels'])->name('quick-launch.models');
});
