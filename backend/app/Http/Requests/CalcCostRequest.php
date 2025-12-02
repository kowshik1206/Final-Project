<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CalcCostRequest
 *
 * Validates cost calculation requests with flexible vehicle parameters.
 */
class CalcCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Route information
            'route.distance_meters' => ['required', 'integer', 'min:1'],
            'route.duration_seconds' => ['nullable', 'integer', 'min:0'],
            'route.polyline' => ['nullable', 'string'],
            'route.points' => ['nullable', 'array'],

            // Mode preferences
            'mode_preferences' => ['required', 'array', 'min:1'],
            'mode_preferences.*' => ['string', 'in:car,ev,train,flight'],

            // Vehicle parameters (all optional, use defaults if missing)
            'vehicle' => ['nullable', 'array'],
            'vehicle.fuel_efficiency_km_per_l' => ['nullable', 'numeric', 'min:1'],
            'vehicle.fuel_price_per_l' => ['nullable', 'numeric', 'min:0'],
            'vehicle.driver_cost_per_km' => ['nullable', 'numeric', 'min:0'],
            'vehicle.toll_multiplier' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'vehicle.ev_kwh_per_km' => ['nullable', 'numeric', 'min:0.01'],
            'vehicle.price_per_kwh' => ['nullable', 'numeric', 'min:0'],
            'vehicle.base_fare' => ['nullable', 'numeric', 'min:0'],
            'vehicle.fare_per_km' => ['nullable', 'numeric', 'min:0'],
            'vehicle.airport_fee' => ['nullable', 'numeric', 'min:0'],
            'vehicle.cost_per_km' => ['nullable', 'numeric', 'min:0'],
            'vehicle.fuel_surcharge_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'route.distance_meters.required' => 'Distance in meters is required',
            'route.distance_meters.min' => 'Distance must be at least 1 meter',
            'mode_preferences.required' => 'At least one mode preference is required',
            'mode_preferences.*.in' => 'Mode must be one of: car, ev, train, flight',
        ];
    }
}
