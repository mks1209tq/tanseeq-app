<?php

namespace Modules\Authorization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Http\Requests\Admin\StoreRoleRequest;
use Modules\Authorization\Http\Requests\Admin\UpdateRoleRequest;
use Modules\ConfigTransports\Http\Middleware\TransportEditProtection;
use Modules\Navigation\Attributes\NavigationItem;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[NavigationItem(label: 'Roles', icon: 'users', order: 9, group: 'admin')]
    public function index(): View
    {
        $roles = Role::withCount('roleAuthorizations')
            ->addSelect([
                'users_count' => DB::raw('(SELECT COUNT(*) FROM role_user WHERE role_user.role_id = roles.id)'),
            ])
            ->latest()
            ->paginate(15);

        return view('authorization::admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('authorization::admin.roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::create($request->validated());

        return redirect()->route('admin.authorization.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): View
    {
        return view('authorization::admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        return redirect()->route('admin.authorization.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        return redirect()->route('admin.authorization.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
