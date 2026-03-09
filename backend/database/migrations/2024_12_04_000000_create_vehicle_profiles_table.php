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
        Schema::create('vehicle_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Maruti Swift", "Toyota Innova"
            $table->enum('fuel', ['petrol', 'diesel', 'cng', 'electric']); // fuel type
            $table->decimal('efficiency_km_per_unit', 5, 2); // km per liter or km per unit
            $table->enum('unit', ['L', 'kg', 'kWh']); // liter, kilogram, or kilowatt-hour
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_profiles');
    }
};
