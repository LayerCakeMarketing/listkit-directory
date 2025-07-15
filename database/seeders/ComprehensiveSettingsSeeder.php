<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class ComprehensiveSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'Listerino',
                'type' => 'string',
                'group' => 'general',
                'description' => 'The name of your site',
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Your Local Directory',
                'type' => 'string',
                'group' => 'general',
                'description' => 'A short tagline for your site',
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@listerino.com',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Primary contact email address',
            ],
            [
                'key' => 'timezone',
                'value' => 'America/Los_Angeles',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default timezone for the site',
            ],
            
            // SEO Settings
            [
                'key' => 'meta_description',
                'value' => 'Discover the best places, businesses, and services in your area',
                'type' => 'string',
                'group' => 'seo',
                'description' => 'Default meta description for search engines',
            ],
            [
                'key' => 'meta_keywords',
                'value' => 'directory, local business, places, services, listings',
                'type' => 'string',
                'group' => 'seo',
                'description' => 'Default meta keywords (comma-separated)',
            ],
            [
                'key' => 'google_analytics_id',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
                'description' => 'Google Analytics tracking ID (e.g., G-XXXXXXXXXX)',
            ],
            [
                'key' => 'robots_txt',
                'value' => "User-agent: *\nAllow: /",
                'type' => 'string',
                'group' => 'seo',
                'description' => 'Content for robots.txt file',
            ],
            
            // Social Media Settings
            [
                'key' => 'og_image',
                'value' => '',
                'type' => 'string',
                'group' => 'social',
                'description' => 'Default Open Graph image URL for social media sharing',
            ],
            [
                'key' => 'twitter_handle',
                'value' => '',
                'type' => 'string',
                'group' => 'social',
                'description' => 'Twitter/X handle (without @)',
            ],
            [
                'key' => 'facebook_url',
                'value' => '',
                'type' => 'string',
                'group' => 'social',
                'description' => 'Facebook page URL',
            ],
            [
                'key' => 'instagram_url',
                'value' => '',
                'type' => 'string',
                'group' => 'social',
                'description' => 'Instagram profile URL',
            ],
            [
                'key' => 'linkedin_url',
                'value' => '',
                'type' => 'string',
                'group' => 'social',
                'description' => 'LinkedIn company page URL',
            ],
            
            // Appearance Settings
            [
                'key' => 'primary_color',
                'value' => '#3B82F6',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'Primary brand color (hex code)',
            ],
            [
                'key' => 'secondary_color',
                'value' => '#1E40AF',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'Secondary brand color (hex code)',
            ],
            [
                'key' => 'logo_url',
                'value' => '/images/listerino_logo.svg',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'Site logo URL',
            ],
            [
                'key' => 'favicon_url',
                'value' => '/favicon.ico',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'Site favicon URL',
            ],
            [
                'key' => 'custom_css',
                'value' => '',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'Custom CSS to be injected into all pages',
            ],
            
            // Security Settings (keeping existing)
            [
                'key' => 'allow_registration',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Allow new users to register',
            ],
            [
                'key' => 'require_email_verification',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Require email verification for new users',
            ],
            [
                'key' => 'enable_social_login',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Enable social media login options',
            ],
            [
                'key' => 'password_min_length',
                'value' => '8',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Minimum password length for users',
            ],
            
            // Feature Settings
            [
                'key' => 'max_upload_size',
                'value' => '10',
                'type' => 'integer',
                'group' => 'features',
                'description' => 'Maximum file upload size in MB',
            ],
            [
                'key' => 'items_per_page',
                'value' => '20',
                'type' => 'integer',
                'group' => 'features',
                'description' => 'Default number of items per page in listings',
            ],
            [
                'key' => 'enable_comments',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable comments on places and lists',
            ],
            [
                'key' => 'enable_reviews',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable reviews on places',
            ],
            [
                'key' => 'enable_user_profiles',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable public user profiles',
            ],
            [
                'key' => 'enable_posts',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable user posts/status updates',
            ],
            
            // Email Settings
            [
                'key' => 'email_from_address',
                'value' => 'noreply@listerino.com',
                'type' => 'string',
                'group' => 'email',
                'description' => 'Default "from" email address',
            ],
            [
                'key' => 'email_from_name',
                'value' => 'Listerino',
                'type' => 'string',
                'group' => 'email',
                'description' => 'Default "from" name for emails',
            ],
            [
                'key' => 'send_welcome_email',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'email',
                'description' => 'Send welcome email to new users',
            ],
            
            // API Settings
            [
                'key' => 'api_rate_limit',
                'value' => '60',
                'type' => 'integer',
                'group' => 'api',
                'description' => 'API rate limit per minute',
            ],
            [
                'key' => 'enable_public_api',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'api',
                'description' => 'Enable public API access',
            ],
            
            // Maintenance
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Enable maintenance mode',
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'We are currently performing scheduled maintenance. Please check back soon.',
                'type' => 'string',
                'group' => 'maintenance',
                'description' => 'Message to display during maintenance mode',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
        
        // Clear the settings cache
        Setting::clearCache();
    }
}