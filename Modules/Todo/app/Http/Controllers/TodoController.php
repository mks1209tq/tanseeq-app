<?php

namespace Modules\Todo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Todo\Entities\Todo;
use Modules\Todo\Http\Requests\StoreTodoRequest;
use Modules\Todo\Http\Requests\UpdateTodoRequest;
use Modules\Navigation\Attributes\NavigationItem;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[NavigationItem(label: 'Todos', icon: 'check-square', order: 10, group: 'main')]
    public function index(Request $request): View
    {
        $query = Todo::query()->where('user_id', auth()->id());

        // Filter by completion status
        if ($request->filled('status')) {
            if ($request->status === 'completed') {
                $query->completed();
            } elseif ($request->status === 'pending') {
                $query->pending();
            }
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        // Search query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'due_date') {
            $query->orderByRaw('due_date IS NULL, due_date '.$sortOrder);
        } elseif ($sortBy === 'priority') {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low') ".$sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $todos = $query->paginate(15)->withQueryString();

        return view('todo::index', compact('todos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('todo::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTodoRequest $request): RedirectResponse
    {
        Todo::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'due_date' => $request->due_date,
        ]);

        return redirect()->route('todo.index')
            ->with('success', 'Todo created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo): View
    {
        $this->authorize('view', $todo);

        return view('todo::show', compact('todo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Todo $todo): View
    {
        $this->authorize('update', $todo);

        return view('todo::edit', compact('todo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTodoRequest $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $todo->update([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'due_date' => $request->due_date,
        ]);

        return redirect()->route('todo.index')
            ->with('success', 'Todo updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo): RedirectResponse
    {
        $this->authorize('delete', $todo);

        $todo->delete();

        return redirect()->route('todo.index')
            ->with('success', 'Todo deleted successfully.');
    }

    /**
     * Toggle the completed status of the todo.
     */
    public function toggle(Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $todo->update(['completed' => ! $todo->completed]);

        return redirect()->back()
            ->with('success', 'Todo status updated successfully.');
    }
}
