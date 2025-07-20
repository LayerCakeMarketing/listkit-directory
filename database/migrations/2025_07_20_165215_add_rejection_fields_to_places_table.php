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
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('rejected_at')->nullable()->after('published_at');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('updated_by_user_id');
            
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });

        // PostgreSQL doesn't use ENUM type the same way as MySQL
        // The status column is likely a string, so we just need to ensure 'rejected' is a valid value
        // This is handled by validation in the model/controller
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['rejection_reason', 'rejected_at', 'rejected_by']);
        });

        // No need to revert enum in PostgreSQL
    }
};