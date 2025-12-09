<?php

use Modules\Authentication\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Todo\Entities\Todo;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

it('requires authentication to view todos', function () {
    $response = $this->get(route('todo.index'));

    $response->assertRedirect();
});

it('displays todos for authenticated user', function () {
    $todo = Todo::factory()->for($this->user)->create();

    actingAs($this->user)
        ->get(route('todo.index'))
        ->assertOk()
        ->assertSee($todo->title);
});

it('does not display other users todos', function () {
    $myTodo = Todo::factory()->for($this->user)->create();
    $otherTodo = Todo::factory()->for($this->otherUser)->create();

    actingAs($this->user)
        ->get(route('todo.index'))
        ->assertOk()
        ->assertSee($myTodo->title)
        ->assertDontSee($otherTodo->title);
});

it('can create a new todo', function () {
    $todoData = [
        'title' => 'Test Todo',
        'description' => 'Test Description',
        'priority' => 'high',
        'due_date' => now()->addDay()->format('Y-m-d'),
    ];

    actingAs($this->user)
        ->post(route('todo.store'), $todoData)
        ->assertRedirect(route('todo.index'));

    $this->assertDatabaseHas('todos', [
        'user_id' => $this->user->id,
        'title' => 'Test Todo',
        'priority' => 'high',
    ]);
});

it('validates required fields when creating todo', function () {
    actingAs($this->user)
        ->post(route('todo.store'), [])
        ->assertSessionHasErrors('title');
});

it('can update a todo', function () {
    $todo = Todo::factory()->for($this->user)->create([
        'title' => 'Original Title',
    ]);

    actingAs($this->user)
        ->put(route('todo.update', $todo), [
            'title' => 'Updated Title',
            'priority' => 'high',
        ])
        ->assertRedirect(route('todo.index'));

    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => 'Updated Title',
        'priority' => 'high',
    ]);
});

it('cannot update other users todos', function () {
    $todo = Todo::factory()->for($this->otherUser)->create();

    actingAs($this->user)
        ->put(route('todo.update', $todo), [
            'title' => 'Hacked Title',
        ])
        ->assertForbidden();
});

it('can delete a todo', function () {
    $todo = Todo::factory()->for($this->user)->create();

    actingAs($this->user)
        ->delete(route('todo.destroy', $todo))
        ->assertRedirect(route('todo.index'));

    $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
});

it('cannot delete other users todos', function () {
    $todo = Todo::factory()->for($this->otherUser)->create();

    actingAs($this->user)
        ->delete(route('todo.destroy', $todo))
        ->assertForbidden();
});

it('can toggle todo completion status', function () {
    $todo = Todo::factory()->for($this->user)->create(['completed' => false]);

    actingAs($this->user)
        ->post(route('todo.toggle', $todo))
        ->assertRedirect();

    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'completed' => true,
    ]);

    actingAs($this->user)
        ->post(route('todo.toggle', $todo->fresh()))
        ->assertRedirect();

    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'completed' => false,
    ]);
});

it('can filter todos by status', function () {
    Todo::factory()->for($this->user)->completed()->create(['title' => 'Completed Todo']);
    Todo::factory()->for($this->user)->pending()->create(['title' => 'Pending Todo']);

    actingAs($this->user)
        ->get(route('todo.index', ['status' => 'completed']))
        ->assertOk()
        ->assertSee('Completed Todo')
        ->assertDontSee('Pending Todo');
});

it('can filter todos by priority', function () {
    Todo::factory()->for($this->user)->highPriority()->create(['title' => 'High Priority']);
    Todo::factory()->for($this->user)->lowPriority()->create(['title' => 'Low Priority']);

    actingAs($this->user)
        ->get(route('todo.index', ['priority' => 'high']))
        ->assertOk()
        ->assertSee('High Priority')
        ->assertDontSee('Low Priority');
});

it('can search todos', function () {
    Todo::factory()->for($this->user)->create(['title' => 'Find Me']);
    Todo::factory()->for($this->user)->create(['title' => 'Ignore Me']);

    actingAs($this->user)
        ->get(route('todo.index', ['search' => 'Find']))
        ->assertOk()
        ->assertSee('Find Me')
        ->assertDontSee('Ignore Me');
});

it('can sort todos by due date', function () {
    $todo1 = Todo::factory()->for($this->user)->create(['due_date' => now()->addDays(3)]);
    $todo2 = Todo::factory()->for($this->user)->create(['due_date' => now()->addDay()]);

    actingAs($this->user)
        ->get(route('todo.index', ['sort_by' => 'due_date', 'sort_order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder([$todo2->title, $todo1->title]);
});
