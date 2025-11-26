<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,course',
            'price' => 'required|numeric|min:0',
            'points_earned' => 'required|integer|min:0',
            'unit_type' => 'nullable|string|in:unit,package,kilo,talla,education',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ];

        if ($this->input('type') === 'course') {
            $rules = array_merge($rules, [
                'duration' => 'required|string|max:255',
                'tutor' => 'required|string|max:255',
                'modality' => 'nullable|string|max:255',
                'schedule' => 'nullable|string|max:255',
            ]);
        }

        return $rules;
    }
}

