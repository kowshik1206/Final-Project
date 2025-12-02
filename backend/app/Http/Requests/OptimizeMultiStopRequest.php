<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Multi-stop route optimization request
 * 
 * Validates multi-stop route parameters with strict waypoint limits.
 * Maximum 7 waypoints enforced due to TSP computational complexity O(7!).
 */
class OptimizeMultiStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Origin location (required)
            'origin' => ['required', 'array'],
            'origin.lat' => ['required', 'numeric', 'between:-90,90'],
            'origin.lng' => ['required', 'numeric', 'between:-180,180'],

            // Destination location (required)
            'destination' => ['required', 'array'],
            'destination.lat' => ['required', 'numeric', 'between:-90,90'],
            'destination.lng' => ['required', 'numeric', 'between:-180,180'],

            // Waypoints (required, 1-7 waypoints max)
            'waypoints' => ['required', 'array', 'min:1', 'max:7'],
            'waypoints.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'waypoints.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'waypoints.*.name' => ['nullable', 'string', 'max:255'],

            // Optimization objective
            'optimize_for' => ['nullable', 'string', 'in:distance,time'],
        ];
    }

    public function messages(): array
    {
        return [
            'waypoints.max' => 'Maximum 7 waypoints allowed for route optimization.',
            'waypoints.min' => 'At least 1 waypoint is required.',
            'waypoints.required' => 'Waypoints are required.',
            'origin.required' => 'Origin location is required.',
            'destination.required' => 'Destination location is required.',
        ];
    }
}
