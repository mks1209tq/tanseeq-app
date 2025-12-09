<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthorizationDebug\Http\Controllers\AuthorizationDebugController;
use Modules\Authorization\Services\AuthorizationService;

Route::middleware(['auth', 'auth.object:AUTHORIZATION_DEBUG'])->group(function () {
    // Authorization Debug Dashboard
    Route::get('authorization-debug/dashboard', [AuthorizationDebugController::class, 'index'])->name('authorization-debug.dashboard');
    
    // SU53-like authorization debug routes
    Route::get('/auth/su53', [AuthorizationDebugController::class, 'showSelf'])->name('authorization-debug.su53');
    Route::get('/auth/su53/{user}', [AuthorizationDebugController::class, 'showUser'])->name('authorization-debug.su53.user');
    
    // Test route to generate authorization check statistics
    Route::get('authorization-debug/test-check', function () {
        $user = auth()->user();
        $authorizationService = app(AuthorizationService::class);
        
        // Perform a test authorization check that will be logged
        $result = $authorizationService->check($user, 'TEST_OBJECT', ['ACTVT' => '01']);
        
        return redirect()->route('authorization-debug.dashboard')
            ->with('status', 'Test authorization check performed. Result: ' . ($result ? 'Allowed' : 'Denied'));
    })->name('authorization-debug.test-check');
});
