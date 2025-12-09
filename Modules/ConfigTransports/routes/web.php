<?php

use Illuminate\Support\Facades\Route;
use Modules\ConfigTransports\app\Http\Controllers\TransportRequestController;

Route::middleware(['web', 'auth', 'auth.object:TRANSPORT_MANAGEMENT'])->prefix('admin/transports')->name('admin.transports.')->group(function () {
    Route::get('/', [TransportRequestController::class, 'index'])->name('index');
    Route::get('/create', [TransportRequestController::class, 'create'])->name('create');
    Route::post('/', [TransportRequestController::class, 'store'])->name('store');
    Route::get('/{transportRequest}', [TransportRequestController::class, 'show'])->name('show');
    Route::post('/{transportRequest}/release', [TransportRequestController::class, 'release'])->name('release');
});

