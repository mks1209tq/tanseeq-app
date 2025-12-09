<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\Role;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

it('allows edits in dev environment', function () {
    config(['system.environment_role' => 'dev']);
    config(['system.transport_edit_protection' => false]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.authorization.roles.create'));
    $response->assertSuccessful();
});

it('blocks direct edits in qa environment', function () {
    config(['system.environment_role' => 'qa']);
    config(['system.transport_edit_protection' => true]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.authorization.roles.create'));
    $response->assertForbidden();
});

it('blocks direct edits in prod environment', function () {
    config(['system.environment_role' => 'prod']);
    config(['system.transport_edit_protection' => true]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.authorization.roles.create'));
    $response->assertForbidden();
});

it('allows viewing in protected environments', function () {
    config(['system.environment_role' => 'qa']);
    config(['system.transport_edit_protection' => true]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $role = Role::factory()->create();

    $response = $this->get(route('admin.authorization.roles.index'));
    $response->assertSuccessful();
});

