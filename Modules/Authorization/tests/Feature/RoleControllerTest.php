<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\Role;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can view roles index', function () {
    Role::factory()->count(5)->create();

    $response = $this->get(route('admin.authorization.roles.index'));

    $response->assertSuccessful();
    $response->assertViewIs('authorization::admin.roles.index');
});

it('can view role create form', function () {
    $response = $this->get(route('admin.authorization.roles.create'));

    $response->assertSuccessful();
    $response->assertViewIs('authorization::admin.roles.create');
});

it('can create a new role', function () {
    $roleData = [
        'name' => 'TestRole',
        'description' => 'Test Description',
    ];

    $response = $this->post(route('admin.authorization.roles.store'), $roleData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('roles', [
        'name' => 'TestRole',
        'description' => 'Test Description',
    ]);
});

it('validates required fields when creating role', function () {
    $response = $this->post(route('admin.authorization.roles.store'), []);

    $response->assertSessionHasErrors(['name']);
});

it('validates unique role name', function () {
    Role::factory()->create(['name' => 'ExistingRole']);

    $response = $this->post(route('admin.authorization.roles.store'), [
        'name' => 'ExistingRole',
        'description' => 'Test',
    ]);

    $response->assertSessionHasErrors('name');
});

it('can view role edit form', function () {
    $role = Role::factory()->create();

    $response = $this->get(route('admin.authorization.roles.edit', $role));

    $response->assertSuccessful();
    $response->assertViewIs('authorization::admin.roles.edit');
});

it('can update a role', function () {
    $role = Role::factory()->create();

    $response = $this->put(route('admin.authorization.roles.update', $role), [
        'name' => 'UpdatedRole',
        'description' => 'Updated Description',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'name' => 'UpdatedRole',
        'description' => 'Updated Description',
    ]);
});

it('can delete a role', function () {
    $role = Role::factory()->create();

    $response = $this->delete(route('admin.authorization.roles.destroy', $role));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

it('requires authentication to access roles', function () {
    auth()->logout();

    $response = $this->get(route('admin.authorization.roles.index'));

    $response->assertRedirect(route('login'));
});

