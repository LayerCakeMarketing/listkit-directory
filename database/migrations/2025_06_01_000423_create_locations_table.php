<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('directory_entry_id')->unique();
            
            // Physical address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->default('USA');
            
            // Geospatial data
            if (config('database.default') === 'pgsql') {
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
            } else {
                $table->point('coordinates')->nullable();
            }
            
            // Location-specific information
            $table->json('hours_of_operation')->nullable();
            $table->json('holiday_hours')->nullable();
            $table->boolean('is_wheelchair_accessible')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->json('amenities')->nullable(); // ["wifi", "outdoor_seating", etc.]
            $table->string('place_id')->nullable(); // Google Places ID
            
            $table->timestamps();
            
            $table->foreign('directory_entry_id')->references('id')->on('directory_entries')->onDelete('cascade');
            $table->index(['city', 'state']);
        });
        
        // Add PostGIS geometry column for PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE locations ADD COLUMN geom geography(Point, 4326)');
            DB::statement('CREATE INDEX locations_geom_gist ON locations USING GIST(geom)');
            DB::statement('
                CREATE OR REPLACE FUNCTION update_location_geom() RETURNS trigger AS $$
                BEGIN
                    NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ');
            DB::statement('
                CREATE TRIGGER update_location_geom_trigger
                BEFORE INSERT OR UPDATE ON locations
                FOR EACH ROW
                WHEN (NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL)
                EXECUTE FUNCTION update_location_geom();
            ');
        }
    }

    public function down()
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS update_location_geom_trigger ON locations');
            DB::statement('DROP FUNCTION IF EXISTS update_location_geom()');
        }
        Schema::dropIfExists('locations');
    }
};