<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can view auth objects index', function () {
    AuthObject::factory()->count(5)->create();

    $response = $this->get(route('admin.authorization.auth-objects.index'));

    $response->assertSuccessful();
    $response->assertViewIs('authorization::admin.auth-objects.index');
});

it('can view auth object create form', function () {
    $response = $this->get(route('admin.authorization.auth-objects.create'));

    $response->assertSuccessful();
    $response->assertViewIs('authorization::admin.auth-objects.create');
});

it('can create a new auth object', function () {
    $authObjectData = [
        'code' => 'TEST_OBJECT',
        'description' => 'Test Description',
        'fields' => [
            [
                'code' => 'FIELD1',
                'label' => 'Field 1',
                'is_org_level' => false,
                'sort' => 1,
            ],
        ],
    ];

    $response = $this->post(route('admin.authorization.auth-objects.store'), $authObjectData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('auth_objects', [
        'code' => 'TEST_OBJECT',
        'description' => 'Test Description',
    ]);

    $authObject = AuthObject::where('code', 'TEST_OBJECT')->first();
    expect($authObject->fields)->toHaveCount(1);
    expect($authObject->fields->first()->code)->toBe('FIELD1');
});

it('validates required fields when creating auth object', function () {
    $response = $this->post(route('admin.authorization.auth-objects.store'), []);

    $response->assertSessionHasErrors(['code']);
});

it('validates unique auth object code', function () {
    AuthObject::factory()->create(['code' => 'EXISTING_OBJECT']);

    $response = $this->post(route('admin.authorization.auth-objects.store'), [
        'code' => 'EXISTING_OBJECT',
        'description' => 'Test',
    ]);

    $response->assertSessionHasErrors('code');
});

it('can view auth object edit form', function () {
    $authObject = AuthObject::factory()->create();

    $response = $this->get(route('admin.authorization.auth-objects.edit', $authObject));

    $response->assertSuccessful();
    $response->assertViewIs('authorization::admin.auth-objects.edit');
});

it('can update an auth object', function () {
    $authObject = AuthObject::factory()->create();
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id]);

    $response = $this->put(route('admin.authorization.auth-objects.update', $authObject), [
        'code' => 'UPDATED_OBJECT',
        'description' => 'Updated Description',
        'fields' => [
            [
                'code' => 'NEW_FIELD',
                'label' => 'New Field',
                'is_org_level' => true,
                'sort' => 1,
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('auth_objects', [
        'id' => $authObject->id,
        'code' => 'UPDATED_OBJECT',
        'description' => 'Updated Description',
    ]);

    // Old field should be deleted, new one created
    expect($authObject->fresh()->fields)->toHaveCount(1);
    expect($authObject->fresh()->fields->first()->code)->toBe('NEW_FIELD');
});

it('can delete an auth object', function () {
    $authObject = AuthObject::factory()->create();

    $response = $this->delete(route('admin.authorization.auth-objects.destroy', $authObject));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('auth_objects', ['id' => $authObject->id]);
});

it('requires authentication to access auth objects', function () {
    auth()->logout();

    $response = $this->get(route('admin.authorization.auth-objects.index'));

    $response->assertRedirect(route('login'));
});

