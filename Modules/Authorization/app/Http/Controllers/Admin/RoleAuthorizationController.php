<?php

namespace Modules\Authorization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Modules\Authorization\Http\Requests\Admin\StoreRoleAuthorizationRequest;
use Modules\Authorization\Http\Requests\Admin\UpdateRoleAuthorizationRequest;

class RoleAuthorizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Role $role): View
    {
        $role->load(['roleAuthorizations.authObject', 'roleAuthorizations.fields']);

        return view('authorization::admin.role-authorizations.index', compact('role'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Role $role): View
    {
        $authObjects = AuthObject::with('fields')->get();

        return view('authorization::admin.role-authorizations.create', compact('role', 'authObjects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleAuthorizationRequest $request, Role $role): RedirectResponse
    {
        $roleAuthorization = RoleAuthorization::create([
            'role_id' => $role->id,
            'auth_object_id' => $request->auth_object_id,
            'label' => $request->label,
        ]);

        foreach ($request->fields as $fieldData) {
            RoleAuthorizationField::create([
                'role_authorization_id' => $roleAuthorization->id,
                'field_code' => $fieldData['field_code'],
                'operator' => $fieldData['operator'],
                'value_from' => $fieldData['value_from'] ?? null,
                'value_to' => $fieldData['value_to'] ?? null,
            ]);
        }

        return redirect()->route('admin.authorization.roles.edit', $role)
            ->with('success', 'Role authorization created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role, RoleAuthorization $roleAuthorization): View
    {
        $roleAuthorization->load('fields', 'authObject.fields');
        $authObjects = AuthObject::with('fields')->get();

        return view('authorization::admin.role-authorizations.edit', compact('role', 'roleAuthorization', 'authObjects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleAuthorizationRequest $request, Role $role, RoleAuthorization $roleAuthorization): RedirectResponse
    {
        // Load relationships before updating so observer can access them
        $roleAuthorization->load('role', 'authObject', 'fields');

        $roleAuthorization->update([
            'auth_object_id' => $request->auth_object_id,
            'label' => $request->label,
        ]);

        // Delete existing fields (observer will handle cache clearing)
        $roleAuthorization->fields()->delete();

        // Create new fields
        foreach ($request->fields as $fieldData) {
            RoleAuthorizationField::create([
                'role_authorization_id' => $roleAuthorization->id,
                'field_code' => $fieldData['field_code'],
                'operator' => $fieldData['operator'],
                'value_from' => $fieldData['value_from'] ?? null,
                'value_to' => $fieldData['value_to'] ?? null,
            ]);
        }

        return redirect()->route('admin.authorization.roles.edit', $role)
            ->with('success', 'Role authorization updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role, RoleAuthorization $roleAuthorization): RedirectResponse
    {
        // Load relationships before deletion so observer can access them
        $roleAuthorization->load('role', 'authObject');

        $roleAuthorization->delete();

        return redirect()->route('admin.authorization.roles.edit', $role)
            ->with('success', 'Role authorization deleted successfully.');
    }
}
