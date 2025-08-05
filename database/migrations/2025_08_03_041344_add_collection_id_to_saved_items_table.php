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
        Schema::table('saved_items', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->after('saveable_id')
                ->constrained('saved_collections')->onDelete('set null');
            
            $table->index(['user_id', 'collection_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_items', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropIndex(['user_id', 'collection_id']);
            $table->dropColumn('collection_id');
        });
    }
};
