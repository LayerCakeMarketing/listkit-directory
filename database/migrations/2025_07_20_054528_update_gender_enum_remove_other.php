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
        // First, update any existing 'other' values to 'prefer_not_to_say'
        DB::table('users')
            ->where('gender', 'other')
            ->update(['gender' => 'prefer_not_to_say']);
        
        // For now, just leave the validation at the application level
        // PostgreSQL enum types are complex to modify
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse
    }
};