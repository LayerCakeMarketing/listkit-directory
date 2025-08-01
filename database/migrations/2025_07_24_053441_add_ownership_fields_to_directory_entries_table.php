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
            // Skip if owner_user_id already exists
            if (!Schema::hasColumn('directory_entries', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            }
            
            // Subscription fields
            $table->enum('subscription_tier', ['free', 'tier1', 'tier2'])->default('free')->after('is_claimed');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_tier');
            $table->string('stripe_customer_id')->nullable()->after('subscription_expires_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            
            // Claim tracking
            $table->timestamp('claimed_at')->nullable()->after('is_claimed');
            $table->timestamp('ownership_transferred_at')->nullable()->after('claimed_at');
            $table->foreignId('ownership_transferred_by')->nullable()->after('ownership_transferred_at')->constrained('users');
            
            // Indexes
            $table->index('owner_user_id');
            $table->index('subscription_tier');
            $table->index('subscription_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropIndex(['owner_user_id']);
            $table->dropIndex(['subscription_tier']);
            $table->dropIndex(['subscription_expires_at']);
            
            $table->dropForeign(['ownership_transferred_by']);
            $table->dropColumn([
                'subscription_tier',
                'subscription_expires_at',
                'stripe_customer_id',
                'stripe_subscription_id',
                'claimed_at',
                'ownership_transferred_at',
                'ownership_transferred_by'
            ]);
            
            // Only drop owner_user_id if we added it
            if (Schema::hasColumn('directory_entries', 'owner_user_id')) {
                $table->dropForeign(['owner_user_id']);
                $table->dropColumn('owner_user_id');
            }
        });
    }
};
