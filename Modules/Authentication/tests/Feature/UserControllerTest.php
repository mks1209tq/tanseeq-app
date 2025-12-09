<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can view users index', function () {
    User::factory()->count(5)->create();

    $response = $this->get(route('admin.authentication.users.index'));

    $response->assertSuccessful();
    $response->assertViewIs('authentication::admin.users.index');
});

it('can view user create form', function () {
    $response = $this->get(route('admin.authentication.users.create'));

    $response->assertSuccessful();
    $response->assertViewIs('authentication::admin.users.create');
});

it('can create a new user', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->post(route('admin.authentication.users.store'), $userData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});

it('validates required fields when creating user', function () {
    $response = $this->post(route('admin.authentication.users.store'), []);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
});

it('validates email format', function () {
    $response = $this->post(route('admin.authentication.users.store'), [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

it('validates password confirmation', function () {
    $response = $this->post(route('admin.authentication.users.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors('password');
});

it('can view user edit form', function () {
    $user = User::factory()->create();

    $response = $this->get(route('admin.authentication.users.edit', $user));

    $response->assertSuccessful();
    $response->assertViewIs('authentication::admin.users.edit');
});

it('can update a user', function () {
    $user = User::factory()->create();

    $response = $this->put(route('admin.authentication.users.update', $user), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

it('can delete a user', function () {
    $user = User::factory()->create();

    $response = $this->delete(route('admin.authentication.users.destroy', $user));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('requires authentication to access users', function () {
    auth()->logout();

    $response = $this->get(route('admin.authentication.users.index'));

    $response->assertRedirect(route('login'));
});

