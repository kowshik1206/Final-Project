<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\RecommendService;

class RecommendRequest extends FormRequest
{
    public function authorize()
    {
        return true; // authorization handled at route level if needed
    }

    public function rules()
    {
        return [
            'route' => 'required|array',
            'route.distance_meters' => 'required|integer|min:1',
            'route.duration_seconds' => 'nullable|integer|min:0',
            'mode_preferences' => 'required|array|min:1',
            'mode_preferences.*' => 'in:car,ev,train,flight',
            'passengers' => 'nullable|integer|min:1|max:100',
            'vehicle' => 'nullable|array',
            'preferences' => 'nullable|array',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}
