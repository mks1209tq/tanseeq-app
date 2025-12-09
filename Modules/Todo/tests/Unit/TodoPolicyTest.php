<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Todo\Entities\Todo;
use Modules\Todo\Policies\TodoPolicy;
use Modules\Authentication\Entities\User;
use App\Contracts\Services\AuthenticationServiceInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->authService = $this->mock(AuthenticationServiceInterface::class);
    $this->policy = new TodoPolicy($this->authService);
});

it('allows super admin to view any todos', function () {
    $user = User::factory()->create();
    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(true);

    expect($this->policy->viewAny($user))->toBeTrue();
});

it('allows regular user to view any todos', function () {
    $user = User::factory()->create();
    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->viewAny($user))->toBeTrue();
});

it('allows super admin to view any todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for(User::factory()->create())->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(true);

    expect($this->policy->view($user, $todo))->toBeTrue();
});

it('allows user to view their own todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->view($user, $todo))->toBeTrue();
});

it('denies user from viewing other users todo', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->for($otherUser)->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->view($user, $todo))->toBeFalse();
});

it('allows super admin to create todos', function () {
    $user = User::factory()->create();
    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(true);

    expect($this->policy->create($user))->toBeTrue();
});

it('allows regular user to create todos', function () {
    $user = User::factory()->create();
    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->create($user))->toBeTrue();
});

it('allows super admin to update any todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for(User::factory()->create())->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(true);

    expect($this->policy->update($user, $todo))->toBeTrue();
});

it('allows user to update their own todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->update($user, $todo))->toBeTrue();
});

it('denies user from updating other users todo', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->for($otherUser)->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->update($user, $todo))->toBeFalse();
});

it('allows super admin to delete any todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for(User::factory()->create())->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(true);

    expect($this->policy->delete($user, $todo))->toBeTrue();
});

it('allows user to delete their own todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->delete($user, $todo))->toBeTrue();
});

it('denies user from deleting other users todo', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->for($otherUser)->create();

    $this->authService->shouldReceive('isSuperAdmin')
        ->with($user->id)
        ->andReturn(false);

    expect($this->policy->delete($user, $todo))->toBeFalse();
});

