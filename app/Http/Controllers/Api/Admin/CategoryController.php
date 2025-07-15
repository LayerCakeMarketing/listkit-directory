<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::with('parent')->withCount('places');

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            if ($request->parent_id === '0' || $request->parent_id === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'places_count') {
            $query->orderBy('places_count', $sortOrder);
        } elseif ($sortBy === 'display_order') {
            $query->orderBy('order_index', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Return all if no pagination
        if (!$request->has('page') && !$request->has('limit')) {
            return response()->json($query->get());
        }

        // Paginate
        $categories = $query->paginate($request->get('limit', 20));
        return response()->json($categories);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'icon' => 'nullable|string|max:10',
            'svg_icon' => 'nullable|string',
            'cover_image_cloudflare_id' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|url|max:500',
            'quotes' => 'nullable|array',
            'quotes.*' => 'string',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show($id)
    {
        $category = Category::with('children', 'parent')->findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
            'icon' => 'nullable|string|max:10',
            'svg_icon' => 'nullable|string',
            'cover_image_cloudflare_id' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|url|max:500',
            'quotes' => 'nullable|array',
            'quotes.*' => 'string',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $id,
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);

        // Prevent circular parent relationships
        if ($validated['parent_id'] ?? null) {
            $parent = Category::find($validated['parent_id']);
            if ($parent && $this->wouldCreateCircularRelationship($category, $parent)) {
                return response()->json([
                    'message' => 'Cannot set parent - would create circular relationship'
                ], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has children
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories'
            ], 422);
        }

        // Check if category has places
        if ($category->places()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with places'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Get category statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'with_places' => Category::has('places')->count(),
            'root_categories' => Category::whereNull('parent_id')->count(),
            'subcategories' => Category::whereNotNull('parent_id')->count(),
            'with_svg_icons' => Category::whereNotNull('svg_icon')->where('svg_icon', '!=', '')->count(),
            'with_cover_images' => Category::where(function($q) {
                $q->whereNotNull('cover_image_cloudflare_id')->orWhereNotNull('cover_image_url');
            })->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Check if setting a parent would create a circular relationship
     */
    private function wouldCreateCircularRelationship($category, $potentialParent)
    {
        $current = $potentialParent;
        while ($current) {
            if ($current->id === $category->id) {
                return true;
            }
            $current = $current->parent;
        }
        return false;
    }
}