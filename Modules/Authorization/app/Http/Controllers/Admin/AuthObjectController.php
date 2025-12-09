<?php

namespace Modules\Authorization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authorization\Http\Requests\Admin\StoreAuthObjectRequest;
use Modules\Authorization\Http\Requests\Admin\UpdateAuthObjectRequest;
use Modules\ConfigTransports\Http\Middleware\TransportEditProtection;
use Modules\Navigation\Attributes\NavigationItem;

class AuthObjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[NavigationItem(label: 'Auth Objects', icon: 'list', order: 8, group: 'admin')]
    public function index(): View
    {
        $authObjects = AuthObject::with('fields')->latest()->paginate(15);

        return view('authorization::admin.auth-objects.index', compact('authObjects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('authorization::admin.auth-objects.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuthObjectRequest $request): RedirectResponse
    {
        $authObject = AuthObject::create($request->only(['code', 'description']));

        if ($request->has('fields')) {
            foreach ($request->fields as $fieldData) {
                AuthObjectField::create([
                    'auth_object_id' => $authObject->id,
                    'code' => $fieldData['code'],
                    'label' => $fieldData['label'] ?? null,
                    'is_org_level' => $fieldData['is_org_level'] ?? false,
                    'sort' => $fieldData['sort'] ?? 0,
                ]);
            }
        }

        return redirect()->route('admin.authorization.auth-objects.index')
            ->with('success', 'Authorization object created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AuthObject $authObject): View
    {
        $authObject->load('fields');

        return view('authorization::admin.auth-objects.edit', compact('authObject'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuthObjectRequest $request, AuthObject $authObject): RedirectResponse
    {
        $authObject->update($request->only(['code', 'description']));

        // Delete existing fields
        $authObject->fields()->delete();

        // Create new fields
        if ($request->has('fields')) {
            foreach ($request->fields as $fieldData) {
                AuthObjectField::create([
                    'auth_object_id' => $authObject->id,
                    'code' => $fieldData['code'],
                    'label' => $fieldData['label'] ?? null,
                    'is_org_level' => $fieldData['is_org_level'] ?? false,
                    'sort' => $fieldData['sort'] ?? 0,
                ]);
            }
        }

        return redirect()->route('admin.authorization.auth-objects.index')
            ->with('success', 'Authorization object updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuthObject $authObject): RedirectResponse
    {
        $authObject->delete();

        return redirect()->route('admin.authorization.auth-objects.index')
            ->with('success', 'Authorization object deleted successfully.');
    }
}

