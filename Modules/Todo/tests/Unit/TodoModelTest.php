<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Todo\Entities\Todo;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

it('can create a todo', function () {
    $user = User::factory()->create();

    $todo = Todo::create([
        'user_id' => $user->id,
        'title' => 'Test Todo',
        'description' => 'Test Description',
        'completed' => false,
        'priority' => 'high',
        'due_date' => now()->addDay(),
    ]);

    expect($todo->title)->toBe('Test Todo');
    expect($todo->description)->toBe('Test Description');
    expect($todo->completed)->toBeFalse();
    expect($todo->priority)->toBe('high');
});

it('casts completed to boolean', function () {
    $user = User::factory()->create();

    $todo = Todo::create([
        'user_id' => $user->id,
        'title' => 'Test Todo',
        'completed' => true,
    ]);

    expect($todo->completed)->toBeTrue();
    expect($todo->completed)->toBeBool();
});

it('casts due_date to date', function () {
    $user = User::factory()->create();
    $dueDate = now()->addDay();

    $todo = Todo::create([
        'user_id' => $user->id,
        'title' => 'Test Todo',
        'due_date' => $dueDate->format('Y-m-d'),
    ]);

    expect($todo->due_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($todo->due_date->format('Y-m-d'))->toBe($dueDate->format('Y-m-d'));
});

it('scopes completed todos', function () {
    $user = User::factory()->create();

    Todo::factory()->for($user)->completed()->create();
    Todo::factory()->for($user)->pending()->create();

    $completed = Todo::completed()->get();

    expect($completed)->toHaveCount(1);
    expect($completed->first()->completed)->toBeTrue();
});

it('scopes pending todos', function () {
    $user = User::factory()->create();

    Todo::factory()->for($user)->completed()->create();
    Todo::factory()->for($user)->pending()->create();

    $pending = Todo::pending()->get();

    expect($pending)->toHaveCount(1);
    expect($pending->first()->completed)->toBeFalse();
});

it('scopes by priority', function () {
    $user = User::factory()->create();

    Todo::factory()->for($user)->highPriority()->create();
    Todo::factory()->for($user)->lowPriority()->create();

    $highPriority = Todo::byPriority('high')->get();

    expect($highPriority)->toHaveCount(1);
    expect($highPriority->first()->priority)->toBe('high');
});

it('gets user id', function () {
    $user = User::factory()->create();

    $todo = Todo::factory()->for($user)->create();

    expect($todo->getUserId())->toBe($user->id);
});

