<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing region slugs to be URL-friendly
        $regions = DB::table('regions')->get();
        
        foreach ($regions as $region) {
            $newSlug = Str::slug($region->name);
            if ($region->slug !== $newSlug) {
                DB::table('regions')
                    ->where('id', $region->id)
                    ->update(['slug' => $newSlug]);
            }
        }
        
        // Add all 50 US states if they don't exist
        $states = [
            ['name' => 'Alabama', 'abbreviation' => 'AL'],
            ['name' => 'Alaska', 'abbreviation' => 'AK'],
            ['name' => 'Arizona', 'abbreviation' => 'AZ'],
            ['name' => 'Arkansas', 'abbreviation' => 'AR'],
            ['name' => 'California', 'abbreviation' => 'CA'],
            ['name' => 'Colorado', 'abbreviation' => 'CO'],
            ['name' => 'Connecticut', 'abbreviation' => 'CT'],
            ['name' => 'Delaware', 'abbreviation' => 'DE'],
            ['name' => 'Florida', 'abbreviation' => 'FL'],
            ['name' => 'Georgia', 'abbreviation' => 'GA'],
            ['name' => 'Hawaii', 'abbreviation' => 'HI'],
            ['name' => 'Idaho', 'abbreviation' => 'ID'],
            ['name' => 'Illinois', 'abbreviation' => 'IL'],
            ['name' => 'Indiana', 'abbreviation' => 'IN'],
            ['name' => 'Iowa', 'abbreviation' => 'IA'],
            ['name' => 'Kansas', 'abbreviation' => 'KS'],
            ['name' => 'Kentucky', 'abbreviation' => 'KY'],
            ['name' => 'Louisiana', 'abbreviation' => 'LA'],
            ['name' => 'Maine', 'abbreviation' => 'ME'],
            ['name' => 'Maryland', 'abbreviation' => 'MD'],
            ['name' => 'Massachusetts', 'abbreviation' => 'MA'],
            ['name' => 'Michigan', 'abbreviation' => 'MI'],
            ['name' => 'Minnesota', 'abbreviation' => 'MN'],
            ['name' => 'Mississippi', 'abbreviation' => 'MS'],
            ['name' => 'Missouri', 'abbreviation' => 'MO'],
            ['name' => 'Montana', 'abbreviation' => 'MT'],
            ['name' => 'Nebraska', 'abbreviation' => 'NE'],
            ['name' => 'Nevada', 'abbreviation' => 'NV'],
            ['name' => 'New Hampshire', 'abbreviation' => 'NH'],
            ['name' => 'New Jersey', 'abbreviation' => 'NJ'],
            ['name' => 'New Mexico', 'abbreviation' => 'NM'],
            ['name' => 'New York', 'abbreviation' => 'NY'],
            ['name' => 'North Carolina', 'abbreviation' => 'NC'],
            ['name' => 'North Dakota', 'abbreviation' => 'ND'],
            ['name' => 'Ohio', 'abbreviation' => 'OH'],
            ['name' => 'Oklahoma', 'abbreviation' => 'OK'],
            ['name' => 'Oregon', 'abbreviation' => 'OR'],
            ['name' => 'Pennsylvania', 'abbreviation' => 'PA'],
            ['name' => 'Rhode Island', 'abbreviation' => 'RI'],
            ['name' => 'South Carolina', 'abbreviation' => 'SC'],
            ['name' => 'South Dakota', 'abbreviation' => 'SD'],
            ['name' => 'Tennessee', 'abbreviation' => 'TN'],
            ['name' => 'Texas', 'abbreviation' => 'TX'],
            ['name' => 'Utah', 'abbreviation' => 'UT'],
            ['name' => 'Vermont', 'abbreviation' => 'VT'],
            ['name' => 'Virginia', 'abbreviation' => 'VA'],
            ['name' => 'Washington', 'abbreviation' => 'WA'],
            ['name' => 'West Virginia', 'abbreviation' => 'WV'],
            ['name' => 'Wisconsin', 'abbreviation' => 'WI'],
            ['name' => 'Wyoming', 'abbreviation' => 'WY'],
            ['name' => 'District of Columbia', 'abbreviation' => 'DC'],
        ];
        
        foreach ($states as $state) {
            $exists = DB::table('regions')
                ->where('type', 'state')
                ->where('level', 1)
                ->where(function($query) use ($state) {
                    $query->where('name', $state['name'])
                          ->orWhere('abbreviation', $state['abbreviation']);
                })
                ->exists();
                
            if (!$exists) {
                DB::table('regions')->insert([
                    'name' => $state['name'],
                    'full_name' => $state['name'],
                    'abbreviation' => $state['abbreviation'],
                    'slug' => Str::slug($state['name']),
                    'type' => 'state',
                    'level' => 1,
                    'parent_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it updates existing data
    }
};
