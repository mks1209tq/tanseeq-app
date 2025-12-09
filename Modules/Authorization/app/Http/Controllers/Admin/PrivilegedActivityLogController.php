<?php

namespace Modules\Authorization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Authorization\Entities\PrivilegedActivityLog;

class PrivilegedActivityLogController extends Controller
{
    /**
     * Display a listing of privileged activity logs.
     */
    public function index(): View
    {
        $logs = PrivilegedActivityLog::with('user')
            ->latest()
            ->paginate(50);

        return view('authorization::admin.privileged-activity-logs.index', compact('logs'));
    }

    /**
     * Display the specified privileged activity log.
     */
    public function show(PrivilegedActivityLog $privilegedActivityLog): View
    {
        $privilegedActivityLog->load('user');

        return view('authorization::admin.privileged-activity-logs.show', compact('privilegedActivityLog'));
    }
}

