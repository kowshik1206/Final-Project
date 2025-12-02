<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pois', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['temple', 'fuel', 'charger', 'toll', 'restaurant', 'hospital', 'other']);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable(); // {charger_type: [...], power_kW: 50, fuel_types: [...], etc}
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('type');
            $table->index(['latitude', 'longitude']);
            $table->spatialIndex(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pois');
    }
};
