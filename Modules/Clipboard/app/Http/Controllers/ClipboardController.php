<?php

namespace Modules\Clipboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Clipboard\Entities\ClipboardItem;
use Modules\Clipboard\Http\Requests\StoreClipboardItemRequest;
use Modules\Clipboard\Http\Requests\UpdateClipboardItemRequest;
use Modules\Navigation\Attributes\NavigationItem;

class ClipboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[NavigationItem(label: 'Clipboard', icon: 'clipboard', order: 5, group: 'main')]
    public function index(): View
    {
        $items = ClipboardItem::where('user_id', auth()->id())
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('clipboard::index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('clipboard::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClipboardItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['order'] = ClipboardItem::where('user_id', auth()->id())->max('order') + 1 ?? 0;

        ClipboardItem::create($data);

        return redirect()->route('clipboard.index')
            ->with('success', 'Item added to clipboard successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ClipboardItem $clipboardItem): View
    {
        // Ensure user can only access their own items
        abort_if($clipboardItem->user_id !== auth()->id(), 403);

        return view('clipboard::show', compact('clipboardItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClipboardItem $clipboardItem): View
    {
        // Ensure user can only edit their own items
        abort_if($clipboardItem->user_id !== auth()->id(), 403);

        return view('clipboard::edit', compact('clipboardItem'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClipboardItemRequest $request, ClipboardItem $clipboardItem): RedirectResponse
    {
        // Ensure user can only update their own items
        abort_if($clipboardItem->user_id !== auth()->id(), 403);

        $clipboardItem->update($request->validated());

        return redirect()->route('clipboard.index')
            ->with('success', 'Clipboard item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClipboardItem $clipboardItem): RedirectResponse
    {
        // Ensure user can only delete their own items
        abort_if($clipboardItem->user_id !== auth()->id(), 403);

        $clipboardItem->delete();

        return redirect()->route('clipboard.index')
            ->with('success', 'Clipboard item deleted successfully.');
    }

    /**
     * Copy item to system clipboard (via API/JSON response).
     */
    public function copy(ClipboardItem $clipboardItem)
    {
        // Ensure user can only copy their own items
        abort_if($clipboardItem->user_id !== auth()->id(), 403);

        return response()->json([
            'content' => $clipboardItem->content,
            'message' => 'Ready to copy',
        ]);
    }

    /**
     * Quick save item to clipboard (via AJAX).
     */
    public function quickSave(Request $request)
    {
        $request->validate([
            'content' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:text,url,code'],
        ]);

        // Check if item already exists (prevent duplicates)
        $existing = ClipboardItem::where('user_id', auth()->id())
            ->where('content', $request->content)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Item already in clipboard',
                'item' => $existing,
            ]);
        }

        $data = $request->only(['title', 'content', 'type']);
        $data['user_id'] = auth()->id();
        $data['type'] = $data['type'] ?? $this->detectType($request->content);
        $data['order'] = ClipboardItem::where('user_id', auth()->id())->max('order') + 1 ?? 0;

        $item = ClipboardItem::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Item saved to clipboard',
            'item' => $item,
        ]);
    }

    /**
     * Detect content type based on content.
     */
    protected function detectType(string $content): string
    {
        $content = trim($content);
        
        if (filter_var($content, FILTER_VALIDATE_URL)) {
            return 'url';
        }
        
        // Simple heuristic: if it looks like code (has common code patterns)
        if (preg_match('/[{}();=<>\[\]]/', $content) && strlen($content) < 500) {
            return 'code';
        }
        
        return 'text';
    }

    /**
     * Reorder items.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:clipboard.clipboard_items,id'],
            'items.*.order' => ['required', 'integer'],
        ]);

        foreach ($request->items as $item) {
            ClipboardItem::where('id', $item['id'])
                ->where('user_id', auth()->id())
                ->update(['order' => $item['order']]);
        }

        return redirect()->route('clipboard.index')
            ->with('success', 'Items reordered successfully.');
    }

    /**
     * Get recent clipboard items for API (latest first).
     */
    public function recent(Request $request)
    {
        $limit = $request->get('limit', 20);
        
        $items = ClipboardItem::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => $item->content,
                    'type' => $item->type,
                    'created_at' => $item->created_at->diffForHumans(),
                    'created_at_full' => $item->created_at->toIso8601String(),
                ];
            });

        return response()->json($items);
    }
}
