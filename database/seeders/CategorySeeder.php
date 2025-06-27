<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
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

        $this->command->info('Categories seeded successfully!');
    }
}