<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

class CaliforniaRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create California state
        $california = Region::updateOrCreate(
            ['slug' => 'california', 'level' => 1],
            [
                'name' => 'California',
                'type' => 'state',
                'metadata' => [
                    'abbreviation' => 'CA',
                    'capital' => 'Sacramento',
                    'population' => 39237836,
                    'area_sq_miles' => 163696
                ],
                'intro_text' => 'California is the most populous state in the United States and the third-largest by area.',
                'is_featured' => true,
                'display_priority' => 1,
                // 'centroid' => DB::raw("ST_GeomFromText('POINT(-119.4179 36.7783)', 4326)") // Uncomment if spatial support is enabled
            ]
        );

        // Create some major cities
        $cities = [
            [
                'name' => 'Los Angeles',
                'slug' => 'los-angeles',
                'metadata' => ['population' => 3898747],
                'intro_text' => 'The second-largest city in the United States and the entertainment capital of the world.'
            ],
            [
                'name' => 'San Francisco',
                'slug' => 'san-francisco',
                'metadata' => ['population' => 873965],
                'intro_text' => 'Known for the Golden Gate Bridge, cable cars, and tech innovation.'
            ],
            [
                'name' => 'San Diego',
                'slug' => 'san-diego',
                'metadata' => ['population' => 1386932],
                'intro_text' => 'Known for its beaches, parks, and warm climate year-round.'
            ],
            [
                'name' => 'Sacramento',
                'slug' => 'sacramento',
                'metadata' => ['population' => 513624],
                'intro_text' => 'The capital city of California.'
            ],
            [
                'name' => 'San Jose',
                'slug' => 'san-jose',
                'metadata' => ['population' => 1021795],
                'intro_text' => 'The heart of Silicon Valley and the largest city in Northern California.'
            ],
            [
                'name' => 'Oakland',
                'slug' => 'oakland',
                'metadata' => ['population' => 433031],
                'intro_text' => 'A major West Coast port city in the San Francisco Bay Area.'
            ],
            [
                'name' => 'Irvine',
                'slug' => 'irvine',
                'metadata' => ['population' => 307670],
                'intro_text' => 'A master-planned city in Orange County known for its safety and education.'
            ]
        ];

        foreach ($cities as $cityData) {
            $city = Region::updateOrCreate(
                ['slug' => $cityData['slug'], 'level' => 2, 'parent_id' => $california->id],
                array_merge($cityData, [
                    'type' => 'city',
                    'is_featured' => true,
                    'display_priority' => rand(1, 10)
                ])
            );

            // Add some neighborhoods for major cities
            if (in_array($city->slug, ['los-angeles', 'san-francisco', 'irvine'])) {
                $this->createNeighborhoods($city);
            }
        }
    }

    private function createNeighborhoods($city)
    {
        $neighborhoods = [];

        switch ($city->slug) {
            case 'los-angeles':
                $neighborhoods = [
                    ['name' => 'Hollywood', 'slug' => 'hollywood'],
                    ['name' => 'Beverly Hills', 'slug' => 'beverly-hills'],
                    ['name' => 'Santa Monica', 'slug' => 'santa-monica'],
                    ['name' => 'Venice Beach', 'slug' => 'venice-beach'],
                    ['name' => 'Downtown LA', 'slug' => 'downtown-la'],
                ];
                break;
            
            case 'san-francisco':
                $neighborhoods = [
                    ['name' => 'Mission District', 'slug' => 'mission-district'],
                    ['name' => 'Castro', 'slug' => 'castro'],
                    ['name' => 'Chinatown', 'slug' => 'chinatown'],
                    ['name' => 'Fisherman\'s Wharf', 'slug' => 'fishermans-wharf'],
                    ['name' => 'Haight-Ashbury', 'slug' => 'haight-ashbury'],
                ];
                break;
                
            case 'irvine':
                $neighborhoods = [
                    ['name' => 'Woodbridge', 'slug' => 'woodbridge'],
                    ['name' => 'Northwood', 'slug' => 'northwood'],
                    ['name' => 'University Park', 'slug' => 'university-park'],
                    ['name' => 'Turtle Rock', 'slug' => 'turtle-rock'],
                    ['name' => 'Westpark', 'slug' => 'westpark'],
                ];
                break;
        }

        foreach ($neighborhoods as $neighborhoodData) {
            Region::updateOrCreate(
                ['slug' => $neighborhoodData['slug'], 'level' => 3, 'parent_id' => $city->id],
                [
                    'name' => $neighborhoodData['name'],
                    'type' => 'neighborhood',
                    'metadata' => [],
                    'is_featured' => false,
                    'display_priority' => rand(1, 20)
                ]
            );
        }
    }
}