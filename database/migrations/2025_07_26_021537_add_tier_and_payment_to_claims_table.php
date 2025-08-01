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
        Schema::table('claims', function (Blueprint $table) {
            // Add tier selection
            $table->enum('tier', ['free', 'tier1', 'tier2'])->default('free')->after('status');
            
            // Add Stripe payment fields
            $table->string('stripe_payment_intent_id')->nullable()->after('tier');
            $table->string('stripe_payment_status')->nullable()->after('stripe_payment_intent_id');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('stripe_payment_status');
            $table->timestamp('payment_completed_at')->nullable()->after('payment_amount');
            
            // Add subscription fields
            $table->string('stripe_subscription_id')->nullable()->after('payment_completed_at');
            $table->timestamp('subscription_starts_at')->nullable()->after('stripe_subscription_id');
            
            // Add indexes
            $table->index('tier');
            $table->index('stripe_payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'tier',
                'stripe_payment_intent_id',
                'stripe_payment_status',
                'payment_amount',
                'payment_completed_at',
                'stripe_subscription_id',
                'subscription_starts_at'
            ]);
        });
    }
};