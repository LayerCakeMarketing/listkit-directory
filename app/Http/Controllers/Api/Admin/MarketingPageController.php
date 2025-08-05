<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingPageController extends Controller
{
    /**
     * Display a listing of marketing pages.
     */
    public function index(Request $request)
    {
        $query = MarketingPage::query();

        // Search functionality
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('heading', 'like', '%' . $request->search . '%')
                  ->orWhere('paragraph', 'like', '%' . $request->search . '%')
                  ->orWhere('page_key', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * Display the specified marketing page.
     */
    public function show($id)
    {
        $page = MarketingPage::findOrFail($id);
        return response()->json($page);
    }

    /**
     * Update the specified marketing page.
     */
    public function update(Request $request, $id)
    {
        $page = MarketingPage::findOrFail($id);

        $validated = $request->validate([
            'heading' => 'nullable|string|max:255',
            'paragraph' => 'nullable|string|max:1000',
            'cover_image_id' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|url|max:500',
            'remove_cover_image' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        // Handle image removal
        if ($request->get('remove_cover_image')) {
            $validated['cover_image_id'] = null;
            $validated['cover_image_url'] = null;
        }

        // Remove non-database fields
        unset($validated['remove_cover_image']);

        $page->update($validated);

        return response()->json([
            'message' => 'Marketing page updated successfully',
            'data' => $page
        ]);
    }

    /**
     * Get marketing page by key (for public pages).
     */
    public function getByKey($key)
    {
        $validKeys = ['places', 'lists', 'register'];
        
        if (!in_array($key, $validKeys)) {
            return response()->json(['message' => 'Invalid page key'], 404);
        }

        $page = MarketingPage::getByKey($key);
        
        return response()->json($page);
    }

    /**
     * Update marketing page by key.
     */
    public function updateByKey(Request $request, $key)
    {
        $validKeys = ['places', 'lists', 'register'];
        
        if (!in_array($key, $validKeys)) {
            return response()->json(['message' => 'Invalid page key'], 404);
        }

        $page = MarketingPage::getByKey($key);

        $validated = $request->validate([
            'heading' => 'nullable|string|max:255',
            'paragraph' => 'nullable|string|max:1000',
            'cover_image_id' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|url|max:500',
            'remove_cover_image' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        // Handle image removal
        if ($request->get('remove_cover_image')) {
            $validated['cover_image_id'] = null;
            $validated['cover_image_url'] = null;
        }

        // Remove non-database fields
        unset($validated['remove_cover_image']);

        $page->update($validated);

        return response()->json([
            'message' => 'Marketing page updated successfully',
            'data' => $page
        ]);
    }
}