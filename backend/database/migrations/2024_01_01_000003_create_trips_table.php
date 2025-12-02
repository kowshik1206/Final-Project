<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name')->nullable();
            $table->json('origin'); // {lat, lng, address}
            $table->json('destination'); // {lat, lng, address}
            $table->json('waypoints')->nullable(); // ordered array
            $table->longText('polyline')->nullable();
            $table->integer('distance_meters');
            $table->integer('duration_seconds');
            $table->enum('mode', ['car', 'ev', 'train', 'flight', 'auto'])->default('auto');
            $table->json('cost'); // {car: {...}, ev: {...}, train: {...}, flight: {...}}
            $table->integer('passengers')->default(1);
            $table->json('saved_preferences')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('mode');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
