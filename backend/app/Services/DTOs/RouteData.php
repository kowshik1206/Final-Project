<?php

namespace App\Services\DTOs;

readonly class RouteData
{
    public function __construct(
        public float $distanceMeters,
        public int $durationSeconds,
        public string $polyline = '',
        public array $points = [],
        public string $preference = 'fastest',
    ) {}

    public function getDistanceKm(): float
    {
        return $this->distanceMeters / 1000;
    }
}
