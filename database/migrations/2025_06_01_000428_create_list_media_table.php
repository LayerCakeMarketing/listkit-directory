<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('list_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_id');
            $table->enum('type', ['image', 'youtube', 'rumble'])->default('image');
            $table->string('url');
            $table->string('caption')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
            
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('cascade');
            $table->index(['list_id', 'order_index']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('list_media');
    }
};