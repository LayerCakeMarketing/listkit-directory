<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SiteSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get all settings grouped by category
     */
    public function index()
    {
        $settings = $this->settingsService->getAllGrouped();
        
        // Transform settings for frontend consumption
        $transformed = [];
        foreach ($settings as $group => $items) {
            $groupSettings = [];
            foreach ($items as $setting) {
                $groupSettings[$setting['key']] = [
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                    'group' => $group,
                ];
            }
            $transformed[$group] = $groupSettings;
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
                Rule::in(['string', 'boolean', 'integer', 'float', 'json', 'array'])
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
                $existingSetting = Setting::where('key', $settingData['key'])->first();
                
                $setting = $this->settingsService->set(
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

        // Clear cache after updates
        $this->settingsService->clearCache();

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
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'setting' => [
                'key' => $setting->key,
                'value' => $this->settingsService->get($setting->key),
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
            'keys' => 'array',
            'keys.*' => 'string|exists:settings,key',
            'groups' => 'array',
            'groups.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // If specific keys are provided, reset only those
        if ($request->has('keys')) {
            $keys = $request->input('keys');
            // Run the seeder to reset specific settings
            $seeder = new \Database\Seeders\ComprehensiveSettingsSeeder();
            $seeder->run();
        } 
        // If groups are provided, reset all settings in those groups
        else if ($request->has('groups')) {
            $groups = $request->input('groups');
            Setting::whereIn('group', $groups)->delete();
            $seeder = new \Database\Seeders\ComprehensiveSettingsSeeder();
            $seeder->run();
        }
        // Otherwise reset all settings
        else {
            Setting::truncate();
            $seeder = new \Database\Seeders\ComprehensiveSettingsSeeder();
            $seeder->run();
        }

        $this->settingsService->clearCache();

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
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], true)) {
                    return 'Value must be a boolean';
                }
                break;
                
            case 'integer':
                if ($value !== null && !is_numeric($value) || (is_numeric($value) && floor($value) != $value)) {
                    return 'Value must be an integer';
                }
                break;
                
            case 'float':
                if ($value !== null && !is_numeric($value)) {
                    return 'Value must be a number';
                }
                break;
                
            case 'json':
            case 'array':
                if ($value !== null) {
                    if (is_string($value)) {
                        json_decode($value);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return 'Value must be valid JSON';
                        }
                    } elseif (!is_array($value)) {
                        return 'Value must be an array or valid JSON string';
                    }
                }
                break;
                
            case 'string':
                // Specific validations for known keys
                if ($key === 'contact_email' || $key === 'email_from_address') {
                    if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return 'Value must be a valid email address';
                    }
                }
                if (($key === 'primary_color' || $key === 'secondary_color') && $value !== null && $value !== '') {
                    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                        return 'Value must be a valid hex color code';
                    }
                }
                if (in_array($key, ['logo_url', 'favicon_url', 'og_image']) && $value !== null && $value !== '') {
                    if (!filter_var($value, FILTER_VALIDATE_URL) && !file_exists(public_path($value))) {
                        return 'Value must be a valid URL or file path';
                    }
                }
                break;
        }
        
        return null;
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
            'security' => [
                'label' => 'Security',
                'description' => 'Security and authentication settings',
                'icon' => 'shield',
                'order' => 5,
            ],
            'features' => [
                'label' => 'Features',
                'description' => 'Enable or disable features',
                'icon' => 'toggle-on',
                'order' => 6,
            ],
            'email' => [
                'label' => 'Email Settings',
                'description' => 'Email configuration',
                'icon' => 'envelope',
                'order' => 7,
            ],
            'api' => [
                'label' => 'API Settings',
                'description' => 'API configuration and limits',
                'icon' => 'code',
                'order' => 8,
            ],
            'maintenance' => [
                'label' => 'Maintenance',
                'description' => 'Maintenance mode settings',
                'icon' => 'wrench',
                'order' => 9,
            ],
        ];
    }
}