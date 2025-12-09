<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Http\Controllers\Admin\AuthObjectController;
use Modules\Authorization\Http\Controllers\Admin\RoleAuthorizationController;
use Modules\Authorization\Http\Controllers\Admin\RoleController;
use Modules\Authorization\Http\Controllers\Admin\UserRoleController;

Route::middleware(['auth', 'auth.object:AUTHORIZATION_MODULE'])->prefix('admin/authorization')->name('admin.authorization.')->group(function () {
    // Privileged Activity Logs (only accessible by users with AUTHORIZATION_DEBUG permission)
    Route::middleware(['auth.object:AUTHORIZATION_DEBUG'])->group(function () {
        Route::get('privileged-activity-logs', [\Modules\Authorization\Http\Controllers\Admin\PrivilegedActivityLogController::class, 'index'])->name('privileged-activity-logs.index');
        Route::get('privileged-activity-logs/{privilegedActivityLog}', [\Modules\Authorization\Http\Controllers\Admin\PrivilegedActivityLogController::class, 'show'])->name('privileged-activity-logs.show');
    });

    // Auth Objects - apply TransportEditProtection middleware to create, store, update, destroy
    Route::middleware([\Modules\ConfigTransports\Http\Middleware\TransportEditProtection::class])->group(function () {
        Route::post('auth-objects', [AuthObjectController::class, 'store'])->name('auth-objects.store');
        Route::put('auth-objects/{authObject}', [AuthObjectController::class, 'update'])->name('auth-objects.update');
        Route::delete('auth-objects/{authObject}', [AuthObjectController::class, 'destroy'])->name('auth-objects.destroy');
    });
    Route::get('auth-objects', [AuthObjectController::class, 'index'])->name('auth-objects.index');
    Route::get('auth-objects/create', [AuthObjectController::class, 'create'])->name('auth-objects.create');
    Route::get('auth-objects/{authObject}', [AuthObjectController::class, 'show'])->name('auth-objects.show');
    Route::get('auth-objects/{authObject}/edit', [AuthObjectController::class, 'edit'])->name('auth-objects.edit');

    // Roles - apply TransportEditProtection middleware to create, store, update, destroy
    Route::middleware([\Modules\ConfigTransports\Http\Middleware\TransportEditProtection::class])->group(function () {
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');

    // Role Authorizations
    Route::prefix('roles/{role}')->name('role-authorizations.')->group(function () {
        Route::get('authorizations', [RoleAuthorizationController::class, 'index'])->name('index');
        Route::get('authorizations/create', [RoleAuthorizationController::class, 'create'])->name('create');
        Route::post('authorizations', [RoleAuthorizationController::class, 'store'])->name('store');
        Route::get('authorizations/{roleAuthorization}/edit', [RoleAuthorizationController::class, 'edit'])->name('edit');
        Route::put('authorizations/{roleAuthorization}', [RoleAuthorizationController::class, 'update'])->name('update');
        Route::delete('authorizations/{roleAuthorization}', [RoleAuthorizationController::class, 'destroy'])->name('destroy');
    });

    // User Roles
    Route::get('users/{user}/edit-roles', [UserRoleController::class, 'edit'])->name('users.edit-roles');
    Route::put('users/{user}/roles', [UserRoleController::class, 'update'])->name('users.update-roles');
});

// Authorization Dashboard - also protect with authorization check
Route::middleware(['auth', 'auth.object:AUTHORIZATION_MODULE'])->get('authorization/dashboard', [\Modules\Authorization\Http\Controllers\AuthorizationController::class, 'index'])->name('authorization.dashboard');
