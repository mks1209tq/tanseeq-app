<?php

namespace Modules\Navigation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Navigation\Services\NavigationService;
use Modules\Navigation\Services\QuickLaunchService;

class NavigationController extends Controller
{
    /**
     * Display all navigation items.
     */
    public function index(NavigationService $navigationService): View
    {
        $groupedItems = $navigationService->getGroupedItems();

        return view('navigation::index', compact('groupedItems'));
    }

    /**
     * Search navigation items for quick launch.
     */
    public function search(Request $request, NavigationService $navigationService): JsonResponse
    {
        $query = $request->input('q', '');
        $items = $navigationService->getAuthorizedItems();

        if (empty($query)) {
            return response()->json([
                'items' => array_slice($items, 0, 10), // Return first 10 items when no query
            ]);
        }

        $queryLower = strtolower($query);
        $filtered = array_filter($items, function ($item) use ($queryLower) {
            $label = strtolower($item['label'] ?? '');
            $route = strtolower($item['route'] ?? '');
            $group = strtolower($item['group'] ?? '');

            return str_contains($label, $queryLower) ||
                   str_contains($route, $queryLower) ||
                   str_contains($group, $queryLower);
        });

        return response()->json([
            'items' => array_values($filtered),
        ]);
    }

    /**
     * Get available models for quick launch.
     */
    public function getModels(QuickLaunchService $quickLaunchService): JsonResponse
    {
        $models = $quickLaunchService->discoverModels();

        return response()->json([
            'models' => $models,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('navigation::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('navigation::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('navigation::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
