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
        Schema::create('login_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('background_image_path')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('custom_css')->nullable();
            $table->boolean('social_login_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_page_settings');
    }
};