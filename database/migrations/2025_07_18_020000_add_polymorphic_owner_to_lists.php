<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            // Add polymorphic columns as nullable first
            $table->nullableMorphs('owner'); // Creates owner_id and owner_type as nullable
            
            // Add indexes for performance
            $table->index(['owner_id', 'owner_type']);
        });
        
        // Migrate existing data - set all existing lists to be owned by users
        DB::table('lists')->whereNotNull('user_id')->update([
            'owner_id' => DB::raw('user_id'),
            'owner_type' => 'App\\Models\\User'
        ]);
        
        // If any lists have channel_id set, update those to be owned by channels
        DB::table('lists')->whereNotNull('channel_id')->update([
            'owner_id' => DB::raw('channel_id'),
            'owner_type' => 'App\\Models\\Channel'
        ]);
        
        // Now make the columns non-nullable
        Schema::table('lists', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();
            $table->string('owner_type')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore user_id from owner_id where owner_type is User
        DB::table('lists')
            ->where('owner_type', 'App\\Models\\User')
            ->update(['user_id' => DB::raw('owner_id')]);
            
        // Restore channel_id from owner_id where owner_type is Channel
        DB::table('lists')
            ->where('owner_type', 'App\\Models\\Channel')
            ->update(['channel_id' => DB::raw('owner_id')]);
            
        Schema::table('lists', function (Blueprint $table) {
            $table->dropMorphs('owner');
        });
    }
};