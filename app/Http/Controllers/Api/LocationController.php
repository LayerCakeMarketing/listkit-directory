<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index()
    {
        // You can implement location logic here
        // For now, returning empty array
        return response()->json([
            'locations' => []
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        // Implementation depends on your Location model
        // $location = Location::create($request->all());

        return response()->json([
            'message' => 'Location created successfully'
        ], 201);
    }

    /**
     * Display the specified location.
     */
    public function show(string $id)
    {
        // $location = Location::findOrFail($id);
        return response()->json([
            'location' => []
        ]);
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        // $location = Location::findOrFail($id);
        // $location->update($request->all());

        return response()->json([
            'message' => 'Location updated successfully'
        ]);
    }

    /**
     * Remove the specified location.
     */
    public function destroy(string $id)
    {
        // $location = Location::findOrFail($id);
        // $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully'
        ]);
    }
}