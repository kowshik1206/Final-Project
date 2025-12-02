<?php

namespace App\Services\DTOs;

readonly class VehicleData
{
    public function __construct(
        public string $type, // petrol, diesel, ev
        public ?float $fuelEfficiencyKmPerL = null,
        public ?float $evKwhPerKm = null,
        public ?float $fuelPricePerL = null,
        public ?float $evPricePerKwh = null,
    ) {}
}
