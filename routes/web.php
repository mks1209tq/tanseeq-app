<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\Navigation\Services\NavigationRegistry;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'auth.object:DASHBOARD_ACCESS'])->name('dashboard');

// Register dashboard navigation
app(NavigationRegistry::class)->register('dashboard', [
    'label' => 'Dashboard',
    'icon' => 'home',
    'order' => 1,
    'group' => 'main',
]);

Route::middleware(['web', 'auth', 'auth.object:TENANT_MANAGEMENT'])->prefix('admin/tenants')->name('admin.tenants.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\TenantController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\TenantController::class, 'store'])->name('store');
    Route::get('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'show'])->name('show');
    Route::get('/{tenant}/edit', [\App\Http\Controllers\Admin\TenantController::class, 'edit'])->name('edit');
    Route::put('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'update'])->name('update');
    Route::delete('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'destroy'])->name('destroy');
});
