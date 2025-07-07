<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::getAllGrouped(),
        ]);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'required|string|in:string,boolean,integer,float,json,array',
        ]);

        foreach ($validated['settings'] as $settingData) {
            $setting = Setting::where('key', $settingData['key'])->first();
            
            if ($setting) {
                Setting::set(
                    $settingData['key'],
                    $settingData['value'],
                    $settingData['type'] ?? $setting->type,
                    $setting->group,
                    $setting->description
                );
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Update a single setting (used for toggle switches)
     */
    public function updateSingle(Request $request, string $key)
    {
        $setting = Setting::where('key', $key)->firstOrFail();
        
        $validated = $request->validate([
            'value' => 'required',
        ]);

        Setting::set(
            $key,
            $validated['value'],
            $setting->type,
            $setting->group,
            $setting->description
        );

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully.',
            'setting' => [
                'key' => $key,
                'value' => Setting::get($key),
            ],
        ]);
    }
}