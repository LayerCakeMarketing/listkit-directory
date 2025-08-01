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
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable'); // Can be used for claims or other entities
            $table->string('type'); // 'email' or 'phone'
            $table->string('code', 6);
            $table->string('destination'); // Email address or phone number
            $table->integer('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['verifiable_id', 'verifiable_type', 'type']);
            $table->index(['code', 'expires_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
