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
        \Log::info('LoginPageController update called', ['request_data' => $request->all()]);
        
        $validated = $request->validate([
            'welcome_message' => 'nullable|string|max:500',
            'custom_css' => 'nullable|string|max:10000',
            'social_login_enabled' => 'boolean',
            'background_image_id' => 'nullable|string|max:255',
            'remove_background_image' => 'boolean',
        ]);

        \Log::info('LoginPageController validated data', ['validated' => $validated]);

        $settings = LoginPageSettings::getInstance();

        // Handle image removal
        if ($request->get('remove_background_image')) {
            $validated['background_image_id'] = null;
            // Also clear old path if exists
            if ($settings->background_image_path) {
                Storage::disk('public')->delete($settings->background_image_path);
                $validated['background_image_path'] = null;
            }
        }

        // Remove non-database fields
        unset($validated['remove_background_image']);

        $settings->update($validated);
        
        \Log::info('LoginPageController after update', ['settings' => $settings->toArray()]);

        return response()->json([
            'message' => 'Login page settings updated successfully',
            'data' => $settings
        ]);
    }
}