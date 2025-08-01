<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LocalPageSetting;
use App\Models\Region;
use App\Models\Place;
use App\Models\UserList;

class LocalPageController extends Controller
{
    /**
     * Get local page settings
     */
    public function show(Request $request)
    {
        $pageType = $request->input('page_type', 'index');
        $regionId = $request->input('region_id');
        
        $settings = LocalPageSetting::getForPage($pageType, $regionId);
        
        return response()->json([
            'settings' => $settings,
            'page_type' => $pageType,
            'region_id' => $regionId
        ]);
    }
    
    /**
     * Update local page settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'page_type' => 'required|string|in:index,state,city,neighborhood',
            'region_id' => 'nullable|exists:regions,id',
            'title' => 'nullable|string|max:255',
            'intro_text' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'featured_lists' => 'nullable|array',
            'featured_places' => 'nullable|array',
            'content_sections' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean'
        ]);
        
        $settings = LocalPageSetting::updateOrCreate(
            [
                'page_type' => $validated['page_type'],
                'region_id' => $validated['region_id'] ?? null
            ],
            $validated
        );
        
        // Handle featured lists
        if (isset($validated['featured_lists'])) {
            $settings->setFeaturedListsData($validated['featured_lists']);
        }
        
        // Handle featured places
        if (isset($validated['featured_places'])) {
            $settings->setFeaturedPlacesData($validated['featured_places']);
        }
        
        $settings->save();
        
        return response()->json([
            'message' => 'Local page settings updated successfully',
            'settings' => $settings->fresh()
        ]);
    }
    
    /**
     * Search for lists to feature
     */
    public function searchLists(Request $request)
    {
        $query = $request->input('q');
        $regionId = $request->input('region_id');
        
        $lists = UserList::query()
            ->public()
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->when($regionId, function ($q) use ($regionId) {
                $region = Region::find($regionId);
                if ($region) {
                    $q->whereHas('items.directoryEntry', function ($q) use ($region) {
                        if ($region->level == 1) {
                            $q->where('state_region_id', $region->id);
                        } elseif ($region->level == 2) {
                            $q->where('city_region_id', $region->id);
                        } elseif ($region->level == 3) {
                            $q->where('neighborhood_region_id', $region->id);
                        }
                    });
                }
            })
            ->with(['user:id,name,username,custom_url'])
            ->withCount('items')
            ->limit(20)
            ->get();
        
        return response()->json($lists);
    }
    
    /**
     * Search for places to feature
     */
    public function searchPlaces(Request $request)
    {
        $query = $request->input('q');
        $regionId = $request->input('region_id');
        
        $places = Place::query()
            ->published()
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%");
            })
            ->when($regionId, function ($q) use ($regionId) {
                $region = Region::find($regionId);
                if ($region) {
                    if ($region->level == 1) {
                        $q->where('state_region_id', $region->id);
                    } elseif ($region->level == 2) {
                        $q->where('city_region_id', $region->id);
                    } elseif ($region->level == 3) {
                        $q->where('neighborhood_region_id', $region->id);
                    }
                }
            })
            ->with(['category:id,name,slug', 'location'])
            ->limit(20)
            ->get();
        
        return response()->json($places);
    }
    
    /**
     * Get all local page settings
     */
    public function index()
    {
        $settings = LocalPageSetting::with('region')
            ->orderBy('page_type')
            ->orderBy('region_id')
            ->paginate(20);
        
        return response()->json($settings);
    }
    
    /**
     * Delete local page settings
     */
    public function destroy($id)
    {
        $settings = LocalPageSetting::findOrFail($id);
        $settings->delete();
        
        return response()->json([
            'message' => 'Local page settings deleted successfully'
        ]);
    }
}
