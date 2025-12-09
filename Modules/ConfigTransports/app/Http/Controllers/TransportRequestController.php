<?php

namespace Modules\ConfigTransports\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\Navigation\Attributes\NavigationItem;

class TransportRequestController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! \Illuminate\Support\Facades\Gate::allows('transport_operator')) {
                abort(403, 'You do not have permission to manage transport requests.');
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of transport requests.
     */
    #[NavigationItem(label: 'Transports', icon: 'truck', order: 11, group: 'admin')]
    public function index(): View
    {
        $transports = TransportRequest::with(['creator', 'releaser'])
            ->latest()
            ->paginate(15);

        return view('config-transports::admin.index', compact('transports'));
    }

    /**
     * Show the form for creating a new transport request.
     */
    public function create(): View
    {
        return view('config-transports::admin.create');
    }

    /**
     * Store a newly created transport request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:security,config,master_data,mixed',
            'description' => 'nullable|string|max:1000',
            'target_environments' => 'nullable|array',
            'target_environments.*' => 'in:qa,prod',
        ]);

        $recorder = app(\Modules\ConfigTransports\Services\TransportRecorder::class);
        $number = $this->generateTransportNumber();

        $transport = TransportRequest::create([
            'number' => $number,
            'type' => $validated['type'],
            'status' => 'open',
            'source_environment' => config('system.environment_role', 'dev'),
            'target_environments' => $validated['target_environments'] ?? ['qa', 'prod'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.transports.show', $transport)
            ->with('success', 'Transport request created successfully.');
    }

    /**
     * Display the specified transport request.
     */
    public function show(TransportRequest $transportRequest): View
    {
        $transportRequest->load(['items', 'creator', 'releaser']);

        return view('config-transports::admin.show', compact('transportRequest'));
    }

    /**
     * Release the transport request.
     */
    public function release(TransportRequest $transportRequest): RedirectResponse
    {
        if (! \Illuminate\Support\Facades\Gate::allows('transport_admin')) {
            abort(403, 'You do not have permission to release transport requests.');
        }

        if (! $transportRequest->canBeReleased()) {
            return redirect()->route('admin.transports.show', $transportRequest)
                ->with('error', 'Transport request cannot be released in current status.');
        }

        $transportRequest->release(auth()->id());

        return redirect()->route('admin.transports.show', $transportRequest)
            ->with('success', 'Transport request released successfully.');
    }

    /**
     * Generate a transport number.
     */
    protected function generateTransportNumber(): string
    {
        $tenant = app('tenant');
        $envPrefix = strtoupper(config('system.environment_role', 'dev'));
        $tenantPrefix = $tenant ? "{$tenant->id}_" : '';

        $lastNumber = TransportRequest::where('number', 'like', $tenantPrefix.$envPrefix.'K%')
            ->orderBy('number', 'desc')
            ->value('number');

        if ($lastNumber) {
            $fullPrefix = $tenantPrefix.$envPrefix.'K';
            $sequence = (int) substr($lastNumber, strlen($fullPrefix));
            $sequence++;
        } else {
            $sequence = 900001;
        }

        return $tenantPrefix.$envPrefix.'K'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}

