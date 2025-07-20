<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListCategory;
use Illuminate\Http\Request;

class ListCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ListCategory::withCount('lists');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'lists_count') {
            $query->orderBy('lists_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $categories = $query->paginate(20);

        return response()->json($categories);
    }

    public function stats()
    {
        return response()->json([
            'total_categories' => ListCategory::count(),
            'active_categories' => ListCategory::where('is_active', true)->count(),
            'inactive_categories' => ListCategory::where('is_active', false)->count(),
            'total_lists_categorized' => ListCategory::withCount('lists')->get()->sum('lists_count'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:list_categories,slug',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'svg_icon' => 'nullable|string',
            'cover_image_cloudflare_id' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|url|max:500',
            'quotes' => 'nullable|array',
            'quotes.*' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $category = ListCategory::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category->load('lists'),
        ], 201);
    }

    public function show($id)
    {
        $category = ListCategory::withCount('lists')->findOrFail($id);
        
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = ListCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:list_categories,slug,' . $id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'svg_icon' => 'nullable|string',
            'cover_image_cloudflare_id' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|url|max:500',
            'quotes' => 'nullable|array',
            'quotes.*' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category->fresh()->load('lists'),
        ]);
    }

    public function destroy($id)
    {
        $category = ListCategory::findOrFail($id);
        
        // Check if category has lists
        if ($category->lists()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category that has lists. Please move or delete the lists first.',
            ], 422);
        }
        
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:list_categories,id',
            'action' => 'required|string|in:delete,activate,deactivate,reorder',
            'is_active' => 'required_if:action,activate,deactivate|boolean',
            'sort_orders' => 'required_if:action,reorder|array',
        ]);

        $categories = ListCategory::whereIn('id', $validated['category_ids'])->get();

        switch ($validated['action']) {
            case 'delete':
                foreach ($categories as $category) {
                    if ($category->lists()->count() === 0) {
                        $category->delete();
                    }
                }
                $message = 'Categories deleted successfully (categories with lists were skipped)';
                break;

            case 'activate':
            case 'deactivate':
                ListCategory::whereIn('id', $validated['category_ids'])
                    ->update(['is_active' => $validated['is_active']]);
                $message = 'Categories updated successfully';
                break;

            case 'reorder':
                foreach ($validated['sort_orders'] as $categoryId => $sortOrder) {
                    if (in_array($categoryId, $validated['category_ids'])) {
                        ListCategory::where('id', $categoryId)->update(['sort_order' => $sortOrder]);
                    }
                }
                $message = 'Categories reordered successfully';
                break;

            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }

        return response()->json(['message' => $message]);
    }

    public function options()
    {
        $categories = ListCategory::active()
                                 ->ordered()
                                 ->select('id', 'name', 'slug', 'color')
                                 ->get();

        return response()->json($categories);
    }

    public function publicOptions()
    {
        $categories = ListCategory::active()
                                 ->ordered()
                                 ->select('id', 'name', 'slug', 'color', 'svg_icon')
                                 ->withCount(['lists' => function ($query) {
                                     $query->searchable()->notOnHold();
                                 }])
                                 ->get();

        return response()->json($categories);
    }
}