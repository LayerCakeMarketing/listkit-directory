<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Get all settings grouped by category
     */
    public function index()
    {
        $settings = SiteSetting::getAllGrouped();
        
        // Transform settings for frontend consumption
        $transformed = [];
        foreach ($settings as $group => $items) {
            $transformed[$group] = $items->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $this->castSettingValue($setting),
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'group' => $setting->group,
                ];
            })->keyBy('key')->toArray();
        }
        
        return response()->json([
            'settings' => $transformed,
            'groups' => $this->getGroupMetadata(),
        ]);
    }

    /**
     * Update multiple settings at once
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => [
                'required',
                Rule::in(['text', 'textarea', 'boolean', 'number', 'json', 'array', 'image'])
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $updated = [];
        $errors = [];

        foreach ($request->input('settings', []) as $settingData) {
            try {
                // Validate specific setting value based on type
                $validationError = $this->validateSettingValue(
                    $settingData['key'],
                    $settingData['value'],
                    $settingData['type']
                );

                if ($validationError) {
                    $errors[$settingData['key']] = $validationError;
                    continue;
                }

                // Find existing setting or use provided data
                $existingSetting = SiteSetting::where('key', $settingData['key'])->first();
                
                $setting = SiteSetting::set(
                    $settingData['key'],
                    $settingData['value'],
                    $settingData['type'],
                    $existingSetting->group ?? 'general',
                    $existingSetting->description ?? null
                );

                $updated[] = $setting->key;
            } catch (\Exception $e) {
                $errors[$settingData['key']] = 'Failed to update setting: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some settings could not be updated',
                'errors' => $errors,
                'updated' => $updated,
            ], 422);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'updated' => $updated,
        ]);
    }

    /**
     * Get a specific setting
     */
    public function show($key)
    {
        $setting = SiteSetting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'setting' => [
                'key' => $setting->key,
                'value' => $this->castSettingValue($setting),
                'type' => $setting->type,
                'group' => $setting->group,
                'description' => $setting->description,
            ]
        ]);
    }

    /**
     * Reset settings to defaults
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keys' => 'required|array',
            'keys.*' => 'required|string|exists:site_settings,key',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Run the seeder to reset specific settings
        $seeder = new \Database\Seeders\SiteSettingsSeeder();
        $seeder->run();

        SiteSetting::clearCache();

        return response()->json([
            'message' => 'Settings reset to defaults successfully'
        ]);
    }

    /**
     * Validate setting value based on type
     */
    private function validateSettingValue($key, $value, $type)
    {
        switch ($type) {
            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'])) {
                    return 'Value must be a boolean';
                }
                break;
                
            case 'number':
                if ($value !== null && !is_numeric($value)) {
                    return 'Value must be a number';
                }
                break;
                
            case 'json':
                if ($value !== null && is_string($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return 'Value must be valid JSON';
                    }
                }
                break;
                
            case 'image':
                if ($value !== null && $value !== '') {
                    if (!filter_var($value, FILTER_VALIDATE_URL) && !file_exists(public_path($value))) {
                        return 'Value must be a valid URL or file path';
                    }
                }
                break;
                
            case 'text':
                // Specific validations for known keys
                if ($key === 'contact_email' || $key === 'email_from_address') {
                    if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return 'Value must be a valid email address';
                    }
                }
                if (($key === 'primary_color') && $value !== null && $value !== '') {
                    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                        return 'Value must be a valid hex color code';
                    }
                }
                break;
        }
        
        return null;
    }

    /**
     * Cast setting value based on type
     */
    private function castSettingValue($setting)
    {
        return SiteSetting::get($setting->key);
    }

    /**
     * Get metadata about setting groups
     */
    private function getGroupMetadata()
    {
        return [
            'general' => [
                'label' => 'General Settings',
                'description' => 'Basic site configuration',
                'icon' => 'cog',
                'order' => 1,
            ],
            'seo' => [
                'label' => 'SEO Settings',
                'description' => 'Search engine optimization',
                'icon' => 'search',
                'order' => 2,
            ],
            'social' => [
                'label' => 'Social Media',
                'description' => 'Social media integration',
                'icon' => 'share',
                'order' => 3,
            ],
            'appearance' => [
                'label' => 'Appearance',
                'description' => 'Visual customization',
                'icon' => 'paint-brush',
                'order' => 4,
            ],
            'features' => [
                'label' => 'Features',
                'description' => 'Enable or disable features',
                'icon' => 'toggle-on',
                'order' => 5,
            ],
            'email' => [
                'label' => 'Email Settings',
                'description' => 'Email configuration',
                'icon' => 'envelope',
                'order' => 6,
            ],
        ];
    }
}