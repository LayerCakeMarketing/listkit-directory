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
        Schema::table('lists', function (Blueprint $table) {
            $table->enum('status', ['active', 'on_hold', 'draft'])->default('active')->after('is_featured');
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_reason');
            $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_changed_at');
            
            // Add index for status queries
            $table->index('status');
            
            // Add foreign key for the admin who changed the status
            $table->foreign('status_changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropForeign(['status_changed_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'status_reason', 'status_changed_at', 'status_changed_by']);
        });
    }
};