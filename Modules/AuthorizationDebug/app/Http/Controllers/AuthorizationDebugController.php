<?php

namespace Modules\AuthorizationDebug\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Authentication\Entities\User;
use Modules\AuthorizationDebug\Services\AuthorizationDebugService;
use Modules\Navigation\Attributes\NavigationItem;

class AuthorizationDebugController extends Controller
{
    /**
     * Display the authorization debug dashboard.
     */
    #[NavigationItem(label: 'Auth Debug', icon: 'bug', order: 12, group: 'admin')]
    public function index(): View
    {
        $user = auth()->user();
        $debugService = app(AuthorizationDebugService::class);
        
        // Get statistics for current user
        $userStats = $debugService->getStatisticsForUser($user);
        
        // Get global statistics if user is super-admin
        $globalStats = null;
        if ($user->isSuperAdmin()) {
            $globalStats = $debugService->getGlobalStatistics();
        }

        return view('authorization-debug::dashboard', [
            'userStats' => $userStats,
            'globalStats' => $globalStats,
        ]);
    }

    /**
     * Show the last authorization failure for the current user (SU53-style).
     */
    public function showSelf(): View
    {
        $user = auth()->user();
        $debugService = app(AuthorizationDebugService::class);
        $failure = $debugService->getLastFailureForUser($user);

        return view('authorization-debug::su53', [
            'failure' => $failure,
            'user' => $user,
            'isViewingOtherUser' => false,
        ]);
    }

    /**
     * Show the last authorization failure for a specific user (admin only).
     */
    public function showUser(User $user): View
    {
        // Check admin authorization
        $this->authorize('super-admin');

        $debugService = app(AuthorizationDebugService::class);
        $failure = $debugService->getLastFailureForUser($user);

        return view('authorization-debug::su53', [
            'failure' => $failure,
            'user' => $user,
            'isViewingOtherUser' => true,
        ]);
    }
}
