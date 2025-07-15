<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginPageSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LoginPageController extends Controller
{
    /**
     * Show the login page settings.
     */
    public function show()
    {
        $settings = LoginPageSettings::getInstance();
        
        return response()->json(['data' => $settings]);
    }

    /**
     * Update the login page settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'welcome_message' => 'nullable|string|max:500',
            'custom_css' => 'nullable|string|max:10000',
            'social_login_enabled' => 'boolean',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            'remove_background_image' => 'boolean',
        ]);

        $settings = LoginPageSettings::getInstance();

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            // Delete old image if exists
            if ($settings->background_image_path) {
                Storage::disk('public')->delete($settings->background_image_path);
            }

            // Store new image
            $path = $request->file('background_image')->store('login-backgrounds', 'public');
            $validated['background_image_path'] = $path;
        }

        // Handle image removal
        if ($request->get('remove_background_image') && $settings->background_image_path) {
            Storage::disk('public')->delete($settings->background_image_path);
            $validated['background_image_path'] = null;
        }

        // Remove non-database fields
        unset($validated['background_image'], $validated['remove_background_image']);

        $settings->update($validated);

        return response()->json([
            'message' => 'Login page settings updated successfully',
            'data' => $settings
        ]);
    }
}