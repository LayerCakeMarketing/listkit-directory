<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE directory_entries DROP CONSTRAINT IF EXISTS directory_entries_type_check');
        
        // Update existing entries to map old types to new types
        DB::table('directory_entries')
            ->where('type', 'physical_location')
            ->update(['type' => 'business_b2c']);
            
        DB::table('directory_entries')
            ->where('type', 'event')
            ->update(['type' => 'point_of_interest']);
            
        DB::table('directory_entries')
            ->where('type', 'resource')
            ->update(['type' => 'online']);
            
        DB::table('directory_entries')
            ->where('type', 'online_business')
            ->update(['type' => 'online']);

        // Add new check constraint with updated types
        DB::statement("ALTER TABLE directory_entries ADD CONSTRAINT directory_entries_type_check CHECK (type IN ('business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest', 'service', 'online'))");
    }

    public function down()
    {
        // Drop the new check constraint
        DB::statement('ALTER TABLE directory_entries DROP CONSTRAINT IF EXISTS directory_entries_type_check');
        
        // Map new types back to old types
        DB::table('directory_entries')
            ->where('type', 'business_b2c')
            ->update(['type' => 'physical_location']);
            
        DB::table('directory_entries')
            ->where('type', 'business_b2b')
            ->update(['type' => 'physical_location']);
            
        DB::table('directory_entries')
            ->where('type', 'religious_org')
            ->update(['type' => 'physical_location']);
            
        DB::table('directory_entries')
            ->where('type', 'point_of_interest')
            ->update(['type' => 'event']);
            
        DB::table('directory_entries')
            ->where('type', 'area_of_interest')
            ->update(['type' => 'event']);
            
        DB::table('directory_entries')
            ->where('type', 'online')
            ->update(['type' => 'online_business']);

        // Restore old check constraint
        DB::statement("ALTER TABLE directory_entries ADD CONSTRAINT directory_entries_type_check CHECK (type IN ('physical_location', 'online_business', 'service', 'event', 'resource'))");
    }
};