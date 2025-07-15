<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Models\Region;
use App\Models\Category;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CuratedListController extends Controller
{
    /**
     * Display a listing of curated lists
     */
    public function index(Request $request)
    {
        $query = UserList::where('type', 'curated')
            ->with(['user', 'region', 'category']);
            
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $lists = $query->orderBy('order_index')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json($lists);
    }
    
    /**
     * Store a newly created curated list
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
            'category_id' => 'nullable|exists:categories,id',
            'is_region_specific' => 'boolean',
            'is_category_specific' => 'boolean',
            'order_index' => 'nullable|integer',
            'place_ids' => 'nullable|array',
            'place_ids.*' => 'exists:places,id'
        ]);
        
        $data = $request->all();
        $data['type'] = 'curated';
        $data['user_id'] = auth()->id();
        $data['slug'] = Str::slug($request->name);
        $data['status'] = 'active';
        $data['is_draft'] = false;
        $data['published_at'] = now();
        
        // Ensure unique slug
        $count = 1;
        $originalSlug = $data['slug'];
        while (UserList::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count;
            $count++;
        }
        
        $list = UserList::create($data);
        
        return response()->json([
            'success' => true,
            'list' => $list->load(['user', 'region', 'category'])
        ]);
    }
    
    /**
     * Display the specified curated list
     */
    public function show($id)
    {
        $list = UserList::where('type', 'curated')
            ->with(['user', 'region', 'category'])
            ->findOrFail($id);
            
        // Load places
        if ($list->place_ids) {
            $list->places = Place::whereIn('id', $list->place_ids)
                ->with(['category', 'stateRegion', 'cityRegion'])
                ->orderByRaw('ARRAY_POSITION(ARRAY[' . implode(',', $list->place_ids) . '], id)')
                ->get();
        }
        
        return response()->json($list);
    }
    
    /**
     * Update the specified curated list
     */
    public function update(Request $request, $id)
    {
        $list = UserList::where('type', 'curated')->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
            'category_id' => 'nullable|exists:categories,id',
            'is_region_specific' => 'boolean',
            'is_category_specific' => 'boolean',
            'order_index' => 'nullable|integer',
            'status' => 'in:active,on_hold,draft',
            'place_ids' => 'nullable|array',
            'place_ids.*' => 'exists:places,id'
        ]);
        
        $data = $request->all();
        
        // Update slug if name changed
        if ($request->name !== $list->name) {
            $data['slug'] = Str::slug($request->name);
            
            // Ensure unique slug
            $count = 1;
            $originalSlug = $data['slug'];
            while (UserList::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }
        
        $list->update($data);
        
        return response()->json([
            'success' => true,
            'list' => $list->load(['user', 'region', 'category'])
        ]);
    }
    
    /**
     * Remove the specified curated list
     */
    public function destroy($id)
    {
        $list = UserList::where('type', 'curated')->findOrFail($id);
        $list->delete();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Add a place to the curated list
     */
    public function addPlace(Request $request, $id)
    {
        $list = UserList::where('type', 'curated')->findOrFail($id);
        
        $request->validate([
            'place_id' => 'required|exists:places,id'
        ]);
        
        $placeIds = $list->place_ids ?? [];
        
        // Check if place already in list
        if (!in_array($request->place_id, $placeIds)) {
            $placeIds[] = $request->place_id;
            $list->place_ids = $placeIds;
            $list->save();
        }
        
        return response()->json([
            'success' => true,
            'list' => $list
        ]);
    }
    
    /**
     * Remove a place from the curated list
     */
    public function removePlace($id, $placeId)
    {
        $list = UserList::where('type', 'curated')->findOrFail($id);
        
        $placeIds = $list->place_ids ?? [];
        $placeIds = array_values(array_diff($placeIds, [$placeId]));
        
        $list->place_ids = $placeIds;
        $list->save();
        
        return response()->json([
            'success' => true,
            'list' => $list
        ]);
    }
    
    /**
     * Reorder places in the curated list
     */
    public function reorderPlaces(Request $request, $id)
    {
        $list = UserList::where('type', 'curated')->findOrFail($id);
        
        $request->validate([
            'place_ids' => 'required|array',
            'place_ids.*' => 'exists:places,id'
        ]);
        
        // Verify all place IDs are in the list
        $currentIds = $list->place_ids ?? [];
        if (count($request->place_ids) !== count($currentIds) || 
            array_diff($request->place_ids, $currentIds) || 
            array_diff($currentIds, $request->place_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid place IDs'
            ], 400);
        }
        
        $list->place_ids = $request->place_ids;
        $list->save();
        
        return response()->json([
            'success' => true,
            'list' => $list
        ]);
    }
    
    /**
     * Get curated lists for a specific region
     */
    public function forRegion($regionId)
    {
        $lists = UserList::where('type', 'curated')
            ->where('status', 'active')
            ->where('is_draft', false)
            ->where('is_region_specific', true)
            ->where('region_id', $regionId)
            ->with(['user', 'category'])
            ->orderBy('order_index')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Load places for each list
        foreach ($lists as $list) {
            if ($list->place_ids) {
                $list->places = Place::whereIn('id', $list->place_ids)
                    ->with(['category', 'stateRegion', 'cityRegion'])
                    ->orderByRaw('ARRAY_POSITION(ARRAY[' . implode(',', $list->place_ids) . '], id)')
                    ->get();
            }
        }
        
        return response()->json($lists);
    }
    
    /**
     * Get curated lists for a specific category
     */
    public function forCategory($categoryId)
    {
        $lists = UserList::where('type', 'curated')
            ->where('status', 'active')
            ->where('is_draft', false)
            ->where('is_category_specific', true)
            ->where('category_id', $categoryId)
            ->with(['user', 'region'])
            ->orderBy('order_index')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Load places for each list
        foreach ($lists as $list) {
            if ($list->place_ids) {
                $list->places = Place::whereIn('id', $list->place_ids)
                    ->with(['category', 'stateRegion', 'cityRegion'])
                    ->orderByRaw('ARRAY_POSITION(ARRAY[' . implode(',', $list->place_ids) . '], id)')
                    ->get();
            }
        }
        
        return response()->json($lists);
    }
}