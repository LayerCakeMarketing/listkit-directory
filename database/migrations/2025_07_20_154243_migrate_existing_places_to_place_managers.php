<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Place;
use App\Models\PlaceManager;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing place creators as owners
        Place::whereNotNull('created_by_user_id')->chunk(100, function ($places) {
            foreach ($places as $place) {
                PlaceManager::firstOrCreate([
                    'place_id' => $place->id,
                    'manageable_type' => 'App\Models\User',
                    'manageable_id' => $place->created_by_user_id,
                ], [
                    'role' => 'owner',
                    'is_active' => true,
                    'accepted_at' => $place->created_at,
                    'permissions' => null, // Owners have all permissions
                ]);
            }
        });

        // Migrate existing owners if different from creators
        Place::whereNotNull('owner_user_id')
            ->whereColumn('owner_user_id', '!=', 'created_by_user_id')
            ->chunk(100, function ($places) {
                foreach ($places as $place) {
                    PlaceManager::firstOrCreate([
                        'place_id' => $place->id,
                        'manageable_type' => 'App\Models\User',
                        'manageable_id' => $place->owner_user_id,
                    ], [
                        'role' => 'owner',
                        'is_active' => true,
                        'accepted_at' => $place->created_at,
                        'permissions' => null, // Owners have all permissions
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all migrated records
        PlaceManager::truncate();
    }
};