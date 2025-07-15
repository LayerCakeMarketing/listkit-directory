<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
            CaliforniaRegionSeeder::class,
            MarketingPagesSeeder::class,
            ComprehensiveSettingsSeeder::class,
            // DirectoryEntrySeeder::class, // Uncomment if you want sample directory entries
        ]);
    }
}