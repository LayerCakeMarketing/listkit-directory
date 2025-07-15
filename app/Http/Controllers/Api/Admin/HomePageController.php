<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageSettings;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomePageController extends Controller
{
    /**
     * Show the home page settings.
     */
    public function show()
    {
        $settings = HomePageSettings::getInstance();
        
        // Load featured places data
        if ($settings->featured_places) {
            $settings->featured_places_data = Place::whereIn('id', $settings->featured_places)
                ->select('id', 'title', 'slug')
                ->get();
        }
        
        return response()->json(['data' => $settings]);
    }

    /**
     * Update the home page settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'hero_title' => 'nullable|string|max:200',
            'hero_subtitle' => 'nullable|string|max:300',
            'cta_text' => 'nullable|string|max:50',
            'cta_link' => 'nullable|string|max:200',
            'featured_places' => 'nullable|array|max:5',
            'featured_places.*' => 'exists:places,id',
            'testimonials' => 'nullable|array|max:10',
            'testimonials.*.quote' => 'required_with:testimonials|string|max:500',
            'testimonials.*.author' => 'required_with:testimonials|string|max:100',
            'testimonials.*.company' => 'nullable|string|max:100',
            'custom_scripts' => 'nullable|string|max:10000',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            'remove_hero_image' => 'boolean',
        ]);

        $settings = HomePageSettings::getInstance();

        // Handle hero image upload
        if ($request->hasFile('hero_image')) {
            // Delete old image if exists
            if ($settings->hero_image_path) {
                Storage::disk('public')->delete($settings->hero_image_path);
            }

            // Store new image
            $path = $request->file('hero_image')->store('hero-images', 'public');
            $validated['hero_image_path'] = $path;
        }

        // Handle image removal
        if ($request->get('remove_hero_image') && $settings->hero_image_path) {
            Storage::disk('public')->delete($settings->hero_image_path);
            $validated['hero_image_path'] = null;
        }

        // Remove non-database fields
        unset($validated['hero_image'], $validated['remove_hero_image']);

        $settings->update($validated);

        return response()->json([
            'message' => 'Home page settings updated successfully',
            'data' => $settings
        ]);
    }

    /**
     * Get available places for selection.
     */
    public function getPlaces(Request $request)
    {
        $query = Place::published()
            ->select('id', 'title', 'slug');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'ilike', "%{$search}%");
        }

        $places = $query->orderBy('title')->limit(20)->get();

        return response()->json(['data' => $places]);
    }
}