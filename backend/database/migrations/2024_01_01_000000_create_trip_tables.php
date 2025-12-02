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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('start_location');
            $table->string('end_location');
            $table->enum('mode', ['car', 'bike', 'walk', 'transit']);
            $table->decimal('distance', 10, 2)->nullable();
            $table->integer('duration')->nullable(); // in minutes
            $table->decimal('cost', 10, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('trip_mode_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->enum('mode', ['car', 'bike', 'walk', 'transit']);
            $table->decimal('base_cost', 10, 2);
            $table->decimal('distance_cost', 10, 2);
            $table->decimal('time_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->timestamps();
        });

        Schema::create('pois', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->timestamps();
        });

        Schema::create('poi_trip', function (Blueprint $table) {
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('poi_id')->constrained()->onDelete('cascade');
            $table->primary(['trip_id', 'poi_id']);
        });

        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poi_trip');
        Schema::dropIfExists('configs');
        Schema::dropIfExists('pois');
        Schema::dropIfExists('trip_mode_costs');
        Schema::dropIfExists('trips');
    }
};
