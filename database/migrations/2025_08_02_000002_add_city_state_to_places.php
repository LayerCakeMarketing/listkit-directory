<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('directory_entries', 'city')) {
                $table->string('city', 100)->nullable()->after('address_line2');
            }
            if (!Schema::hasColumn('directory_entries', 'state')) {
                $table->string('state', 50)->nullable()->after('city');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropColumn(['city', 'state']);
        });
    }
};