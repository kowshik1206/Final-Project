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
        Schema::table('pois', function (Blueprint $table) {
            // Check MySQL version for spatial support
            try {
                $mysqlVersion = DB::selectOne("SELECT VERSION() as version");
                $version = $mysqlVersion->version ?? '5.7.0';

                // MySQL 5.7.6+ supports spatial types with SRID
                if (version_compare($version, '5.7.6', '>=')) {
                    // Add location column as POINT with WGS84 (SRID 4326)
                    $table->point('location')->nullable()->after('longitude');
                    $table->spatialIndex('location');
                } else {
                    // Fallback: create composite index on lat/lng
                    $table->index(['latitude', 'longitude'], 'pois_lat_lng_index');
                }
            } catch (\Exception $e) {
                // Fallback to composite index if version check fails
                $table->index(['latitude', 'longitude'], 'pois_lat_lng_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            // Drop spatial index if it exists
            try {
                $table->dropSpatialIndex('pois_location_spatialindex');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop composite index if it exists
            try {
                $table->dropIndex('pois_lat_lng_index');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop location column if it exists
            if (Schema::hasColumn('pois', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};
