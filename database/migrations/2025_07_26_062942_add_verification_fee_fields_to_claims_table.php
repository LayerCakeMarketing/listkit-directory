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
            $table->boolean('verification_fee_paid')->default(false)->after('payment_amount');
            $table->boolean('fee_kept')->default(false)->after('verification_fee_paid');
            $table->string('fee_payment_intent_id')->nullable()->after('fee_kept');
            $table->timestamp('fee_refunded_at')->nullable()->after('fee_payment_intent_id');
            $table->string('fee_refund_id')->nullable()->after('fee_refunded_at');
            $table->decimal('verification_fee_amount', 10, 2)->default(5.99)->after('fee_refund_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'verification_fee_paid',
                'fee_kept',
                'fee_payment_intent_id',
                'fee_refunded_at',
                'fee_refund_id',
                'verification_fee_amount'
            ]);
        });
    }
};
