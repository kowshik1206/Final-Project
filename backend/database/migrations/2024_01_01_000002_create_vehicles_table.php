<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['petrol', 'diesel', 'ev']);
            $table->decimal('fuel_efficiency_km_per_l', 8, 2)->nullable(); // for petrol/diesel
            $table->decimal('ev_kwh_per_km', 8, 3)->nullable(); // for EV
            $table->decimal('fuel_price_per_l', 8, 2)->nullable();
            $table->decimal('ev_price_per_kwh', 8, 2)->nullable();
            $table->integer('capacity_passengers')->default(4);
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
