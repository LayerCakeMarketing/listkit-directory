<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeaturedPlacesController extends Controller
{
    /**
     * Display featured places for a region
     */
    public function index(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id'
        ]);
        
        $region = Region::findOrFail($request->region_id);
        
        // Get featured places for this region
        $featuredPlaces = Place::whereHas('regions', function ($q) use ($region) {
                $q->where('regions.id', $region->id)
                  ->where('place_regions.is_featured', true);
            })
            ->with(['category', 'location'])
            ->orderBy('place_regions.featured_order')
            ->get();
            
        // Get metadata for the region
        $metadata = DB::table('region_featured_metadata')
            ->where('region_id', $region->id)
            ->first();
            
        return response()->json([
            'region' => $region,
            'featured_places' => $featuredPlaces,
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Search places to add as featured
     */
    public function search(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'q' => 'required|string|min:2'
        ]);
        
        $region = Region::findOrFail($request->region_id);
        
        // Get region IDs to search in (including children if applicable)
        $regionIds = [$region->id];
        if ($region->level < 3) {
            $childRegionIds = $region->descendants()->pluck('id')->toArray();
            $regionIds = array_merge($regionIds, $childRegionIds);
        }
        
        // Search for places in this region that aren't already featured
        $places = Place::whereHas('regions', function ($q) use ($regionIds) {
                $q->whereIn('regions.id', $regionIds);
            })
            ->whereDoesntHave('regions', function ($q) use ($region) {
                $q->where('regions.id', $region->id)
                  ->where('place_regions.is_featured', true);
            })
            ->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->q}%")
                  ->orWhere('description', 'like', "%{$request->q}%");
            })
            ->with(['category', 'location'])
            ->published()
            ->limit(20)
            ->get();
            
        return response()->json(['places' => $places]);
    }
    
    /**
     * Add a place as featured
     */
    public function store(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'place_id' => 'required|exists:places,id'
        ]);
        
        $region = Region::findOrFail($request->region_id);
        $place = Place::findOrFail($request->place_id);
        
        // Check if already featured
        $existing = DB::table('place_regions')
            ->where('place_id', $place->id)
            ->where('region_id', $region->id)
            ->where('is_featured', true)
            ->first();
            
        if ($existing) {
            return response()->json(['message' => 'Place is already featured'], 422);
        }
        
        // Get the next order number
        $maxOrder = DB::table('place_regions')
            ->where('region_id', $region->id)
            ->where('is_featured', true)
            ->max('featured_order') ?? 0;
            
        // Add or update the place_region record
        DB::table('place_regions')->updateOrInsert(
            [
                'place_id' => $place->id,
                'region_id' => $region->id
            ],
            [
                'is_featured' => true,
                'featured_order' => $maxOrder + 1,
                'featured_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        
        return response()->json([
            'message' => 'Place featured successfully',
            'place' => $place->load(['category', 'location'])
        ]);
    }
    
    /**
     * Remove featured status
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'place_id' => 'required|exists:places,id'
        ]);
        
        DB::table('place_regions')
            ->where('place_id', $request->place_id)
            ->where('region_id', $request->region_id)
            ->update([
                'is_featured' => false,
                'featured_order' => null,
                'featured_at' => null
            ]);
            
        return response()->json(['message' => 'Featured status removed']);
    }
    
    /**
     * Update featured order
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'place_ids' => 'required|array',
            'place_ids.*' => 'exists:places,id'
        ]);
        
        DB::transaction(function () use ($request) {
            foreach ($request->place_ids as $index => $placeId) {
                DB::table('place_regions')
                    ->where('place_id', $placeId)
                    ->where('region_id', $request->region_id)
                    ->where('is_featured', true)
                    ->update([
                        'featured_order' => $index + 1,
                        'updated_at' => now()
                    ]);
            }
        });
        
        return response()->json(['message' => 'Order updated successfully']);
    }
    
    /**
     * Update region metadata
     */
    public function updateMetadata(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'featured_title' => 'nullable|string|max:255',
            'featured_description' => 'nullable|string',
            'max_featured_places' => 'nullable|integer|min:1|max:50',
            'show_featured_section' => 'nullable|boolean'
        ]);
        
        DB::table('region_featured_metadata')->updateOrInsert(
            ['region_id' => $request->region_id],
            [
                'featured_title' => $request->featured_title,
                'featured_description' => $request->featured_description,
                'max_featured_places' => $request->max_featured_places ?? 10,
                'show_featured_section' => $request->show_featured_section ?? true,
                'updated_at' => now()
            ]
        );
        
        return response()->json(['message' => 'Metadata updated successfully']);
    }
}