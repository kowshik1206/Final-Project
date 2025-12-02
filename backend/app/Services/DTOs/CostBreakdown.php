<?php

namespace App\Services\DTOs;

class CostBreakdown
{
    public function __construct(
        public string $mode,
        public float $totalCost,
        public array $breakdown,
        public array $assumptions,
        public string $explanation = '',
    ) {}

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'total_cost' => $this->totalCost,
            'breakdown' => $this->breakdown,
            'assumptions' => $this->assumptions,
            'explanation' => $this->explanation,
        ];
    }
}
