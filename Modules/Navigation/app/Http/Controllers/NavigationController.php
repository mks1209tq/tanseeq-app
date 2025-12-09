<?php

namespace Modules\Navigation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Navigation\Services\NavigationService;

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
