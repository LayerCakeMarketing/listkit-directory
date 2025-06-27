<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DirectoryEntry;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DirectoryEntrySeeder extends Seeder
{
    public function run()
    {
        // Create categories if they don't exist
        $categories = [
            'Restaurants' => ['Italian', 'Mexican', 'Asian', 'American', 'Seafood'],
            'Shopping' => ['Clothing', 'Electronics', 'Groceries', 'Home & Garden'],
            'Services' => ['Auto Repair', 'Beauty & Spa', 'Health & Medical', 'Professional'],
            'Entertainment' => ['Movies', 'Sports', 'Music Venues', 'Parks & Recreation'],
            'Online' => ['E-commerce', 'Digital Services', 'Educational', 'Software'],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::firstOrCreate([
                'name' => $parentName,
                'slug' => Str::slug($parentName)
            ]);

            foreach ($children as $childName) {
                Category::firstOrCreate([
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'parent_id' => $parent->id
                ]);
            }
        }

        // Create regions
        $regions = [
            ['name' => 'California', 'type' => 'state'],
            ['name' => 'New York', 'type' => 'state'],
            ['name' => 'Texas', 'type' => 'state'],
            ['name' => 'Florida', 'type' => 'state'],
        ];

        foreach ($regions as $regionData) {
            Region::firstOrCreate($regionData);
        }

        // Get users for ownership
        $adminUser = User::where('role', 'admin')->first();
        $businessOwner = User::where('role', 'business_owner')->first();

        // Sample directory entries data
        $entries = [
            // Physical locations
            [
                'title' => 'Joe\'s Italian Restaurant',
                'type' => 'physical_location',
                'description' => 'Authentic Italian cuisine in the heart of downtown. Family-owned since 1985.',
                'category' => 'Italian',
                'location' => [
                    'address_line1' => '123 Main Street',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'zip_code' => '90001',
                    'latitude' => 34.0522,
                    'longitude' => -118.2437,
                    'hours_of_operation' => [
                        'monday' => '11:00-22:00',
                        'tuesday' => '11:00-22:00',
                        'wednesday' => '11:00-22:00',
                        'thursday' => '11:00-22:00',
                        'friday' => '11:00-23:00',
                        'saturday' => '11:00-23:00',
                        'sunday' => '12:00-21:00',
                    ],
                ],
                'phone' => '(555) 123-4567',
                'email' => 'info@joesitalian.com',
                'website_url' => 'https://joesitalian.com',
            ],
            [
                'title' => 'TechHub Electronics',
                'type' => 'physical_location',
                'description' => 'Your one-stop shop for all electronics needs. Best prices guaranteed!',
                'category' => 'Electronics',
                'location' => [
                    'address_line1' => '456 Technology Blvd',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'zip_code' => '94105',
                    'latitude' => 37.7749,
                    'longitude' => -122.4194,
                    'hours_of_operation' => [
                        'monday' => '10:00-20:00',
                        'tuesday' => '10:00-20:00',
                        'wednesday' => '10:00-20:00',
                        'thursday' => '10:00-20:00',
                        'friday' => '10:00-21:00',
                        'saturday' => '10:00-21:00',
                        'sunday' => '11:00-18:00',
                    ],
                ],
                'phone' => '(555) 987-6543',
                'email' => 'support@techhub.com',
                'website_url' => 'https://techhub.com',
            ],
            [
                'title' => 'Serenity Spa & Wellness',
                'type' => 'physical_location',
                'description' => 'Relax and rejuvenate with our premium spa services.',
                'category' => 'Beauty & Spa',
                'location' => [
                    'address_line1' => '789 Wellness Way',
                    'city' => 'Miami',
                    'state' => 'FL',
                    'zip_code' => '33101',
                    'latitude' => 25.7617,
                    'longitude' => -80.1918,
                    'hours_of_operation' => [
                        'monday' => '09:00-19:00',
                        'tuesday' => '09:00-19:00',
                        'wednesday' => '09:00-19:00',
                        'thursday' => '09:00-19:00',
                        'friday' => '09:00-20:00',
                        'saturday' => '09:00-20:00',
                        'sunday' => '10:00-17:00',
                    ],
                ],
                'phone' => '(555) 456-7890',
                'email' => 'relax@serenityspa.com',
                'website_url' => 'https://serenityspa.com',
            ],
            // Online businesses
            [
                'title' => 'CloudTech Solutions',
                'type' => 'online_business',
                'description' => 'Enterprise cloud computing solutions for modern businesses.',
                'category' => 'Software',
                'phone' => '(800) 555-2468',
                'email' => 'contact@cloudtechsolutions.com',
                'website_url' => 'https://cloudtechsolutions.com',
            ],
            [
                'title' => 'LearnPro Online Academy',
                'type' => 'online_business',
                'description' => 'Professional development courses and certifications online.',
                'category' => 'Educational',
                'email' => 'info@learnpro.edu',
                'website_url' => 'https://learnpro.edu',
            ],
            // Service
            [
                'title' => 'Mobile Pet Grooming',
                'type' => 'service',
                'description' => 'We come to you! Professional pet grooming at your doorstep.',
                'category' => 'Professional',
                'phone' => '(555) 321-9876',
                'email' => 'woof@mobilepetgrooming.com',
                'website_url' => 'https://mobilepetgrooming.com',
            ],
        ];

        foreach ($entries as $entryData) {
            $categoryName = $entryData['category'];
            unset($entryData['category']);
            
            $category = Category::where('name', $categoryName)->first();
            $region = Region::where('name', $entryData['location']['state'] ?? 'California')->first();
            
            $locationData = $entryData['location'] ?? null;
            unset($entryData['location']);

            // Create directory entry
            $entry = DirectoryEntry::create([
                ...$entryData,
                'slug' => Str::slug($entryData['title']),
                'category_id' => $category?->id,
                'region_id' => $region?->id,
                'created_by_user_id' => $adminUser->id,
                'owner_user_id' => rand(0, 1) ? $businessOwner?->id : null,
                'status' => 'published',
                'is_featured' => rand(0, 1),
                'is_verified' => rand(0, 1),
                'is_claimed' => !empty($entryData['owner_user_id']),
                'published_at' => now(),
                'tags' => $this->generateTags($entryData['type']),
                'social_links' => [
                    'facebook' => 'https://facebook.com/' . Str::slug($entryData['title']),
                    'instagram' => 'https://instagram.com/' . Str::slug($entryData['title']),
                ],
            ]);

            // Create location if it's a physical location
            if ($locationData && in_array($entry->type, ['physical_location', 'event'])) {
                Location::create([
                    'directory_entry_id' => $entry->id,
                    ...$locationData,
                    'amenities' => $this->generateAmenities($entry->type),
                    'is_wheelchair_accessible' => rand(0, 1),
                    'has_parking' => rand(0, 1),
                ]);
            }
        }

        $this->command->info('Directory entries seeded successfully!');
    }

    private function generateTags($type)
    {
        $tagSets = [
            'physical_location' => ['local', 'in-person', 'walk-in', 'appointment'],
            'online_business' => ['online', 'digital', 'remote', 'worldwide'],
            'service' => ['mobile', 'on-demand', 'professional', 'licensed'],
        ];

        return array_rand(array_flip($tagSets[$type] ?? ['general']), rand(2, 3));
    }

    private function generateAmenities($type)
    {
        $amenities = ['wifi', 'parking', 'wheelchair_access', 'outdoor_seating', 'delivery', 'takeout', 'reservations'];
        return array_rand(array_flip($amenities), rand(3, 5));
    }
}