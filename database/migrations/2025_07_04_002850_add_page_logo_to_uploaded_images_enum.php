<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to use raw SQL to modify enum constraints
        DB::statement("ALTER TABLE uploaded_images DROP CONSTRAINT uploaded_images_type_check");
        DB::statement("ALTER TABLE uploaded_images ADD CONSTRAINT uploaded_images_type_check CHECK (type IN ('avatar', 'cover', 'page_logo', 'list_image', 'entry_logo'))");
        
        DB::statement("ALTER TABLE image_uploads DROP CONSTRAINT image_uploads_type_check");
        DB::statement("ALTER TABLE image_uploads ADD CONSTRAINT image_uploads_type_check CHECK (type IN ('avatar', 'cover', 'page_logo', 'list_image', 'entry_logo'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the changes
        DB::statement("ALTER TABLE uploaded_images DROP CONSTRAINT uploaded_images_type_check");
        DB::statement("ALTER TABLE uploaded_images ADD CONSTRAINT uploaded_images_type_check CHECK (type IN ('avatar', 'cover', 'list_image', 'entry_logo'))");
        
        DB::statement("ALTER TABLE image_uploads DROP CONSTRAINT image_uploads_type_check");
        DB::statement("ALTER TABLE image_uploads ADD CONSTRAINT image_uploads_type_check CHECK (type IN ('avatar', 'cover', 'list_image', 'entry_logo'))");
    }
};
