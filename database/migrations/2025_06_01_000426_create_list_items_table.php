<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_id');
            $table->unsignedBigInteger('directory_entry_id'); // Changed from location_id
            $table->unsignedInteger('order_index')->default(0);
            
            // Item-specific data
            $table->text('notes')->nullable(); // User's notes about why they added this
            $table->string('affiliate_url')->nullable();
            $table->json('custom_data')->nullable(); // For future extensibility
            
            $table->timestamps();
            
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('cascade');
            $table->foreign('directory_entry_id')->references('id')->on('directory_entries')->onDelete('cascade');
            
            $table->unique(['list_id', 'directory_entry_id']);
            $table->index(['list_id', 'order_index']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('list_items');
    }
};