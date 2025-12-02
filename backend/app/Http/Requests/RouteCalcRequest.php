<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteCalcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => 'nullable|array|min:2|max:50',
            'points.*.lat' => 'required_with:points|numeric|between:-90,90',
            'points.*.lng' => 'required_with:points|numeric|between:-180,180',
            'polyline' => 'nullable|string',
            'mode' => 'nullable|string|in:car,ev,train,flight',
            'preference' => 'nullable|string|in:fastest,shortest,avoid_highways'
        ];
    }

    protected function prepareForValidation()
    {
        // If polyline is provided but points are absent, that's fine.
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}
