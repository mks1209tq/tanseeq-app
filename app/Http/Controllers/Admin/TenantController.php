<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Navigation\Attributes\NavigationItem;

class TenantController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Display a listing of tenants.
     */
    #[NavigationItem(label: 'Tenants', icon: 'server', order: 3, group: 'admin')]
    public function index(): View
    {
        $tenants = Tenant::latest()->paginate(15);

        return view('admin.tenants.index', compact('tenants'));
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(): View
    {
        return view('admin.tenants.create');
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value && DB::connection('system')->table('tenants')->where('domain', $value)->exists()) {
                        $fail('The domain has already been taken.');
                    }
                },
            ],
            'subdomain' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value && DB::connection('system')->table('tenants')->where('subdomain', $value)->exists()) {
                        $fail('The subdomain has already been taken.');
                    }
                },
            ],
            'plan' => 'required|in:basic,premium,enterprise',
            'max_users' => 'required|integer|min:1',
        ]);

        $tenant = $this->tenantService->createTenant($validated);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant created successfully.');
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): View
    {
        return view('admin.tenants.show', compact('tenant'));
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($tenant) {
                    if ($value && DB::connection('system')->table('tenants')->where('domain', $value)->where('id', '!=', $tenant->id)->exists()) {
                        $fail('The domain has already been taken.');
                    }
                },
            ],
            'subdomain' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($tenant) {
                    if ($value && DB::connection('system')->table('tenants')->where('subdomain', $value)->where('id', '!=', $tenant->id)->exists()) {
                        $fail('The subdomain has already been taken.');
                    }
                },
            ],
            'plan' => 'required|in:basic,premium,enterprise',
            'max_users' => 'required|integer|min:1',
            'status' => 'required|in:active,suspended,expired',
        ]);

        $tenant->update($validated);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->deleteTenant($tenant);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }
}
