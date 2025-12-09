<?php

use Illuminate\Support\Facades\Route;
use Modules\Todo\Http\Controllers\TodoController;

Route::middleware(['auth', 'auth.object:TODO_MANAGEMENT'])->group(function () {
    Route::resource('todos', TodoController::class)->names('todo');
    Route::post('todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todo.toggle');
    Route::get('api/todos/search', [TodoController::class, 'search'])->name('todo.search');
});
