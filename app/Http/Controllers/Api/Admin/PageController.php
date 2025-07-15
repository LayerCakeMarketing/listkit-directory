<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Requests\PageRequest;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Page::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%")
                  ->orWhere('content', 'ilike', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['draft', 'published'])) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $pages = $query->paginate($request->get('per_page', 15));

        return response()->json($pages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PageRequest $request)
    {
        $page = Page::create($request->validated());

        return response()->json([
            'message' => 'Page created successfully',
            'page' => $page
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        return response()->json(['data' => $page]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PageRequest $request, Page $page)
    {
        $page->update($request->validated());

        return response()->json([
            'message' => 'Page updated successfully',
            'page' => $page
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json([
            'message' => 'Page deleted successfully'
        ]);
    }

    /**
     * Get page statistics.
     */
    public function stats()
    {
        $stats = [
            'total' => Page::count(),
            'published' => Page::published()->count(),
            'draft' => Page::draft()->count(),
        ];

        return response()->json($stats);
    }
}