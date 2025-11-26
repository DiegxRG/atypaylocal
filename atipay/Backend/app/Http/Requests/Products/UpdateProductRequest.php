<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'points_earned' => 'sometimes|integer|min:0',
            'unit_type' => 'nullable|string|in:unit,package,kilo,talla,education',
            'stock' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,inactive',
            'image' => 'nullable|image|max:2048',
            'type' => 'sometimes|in:product,course',
        ];

        if ($this->input('type') === 'course' || $this->type === 'course') {
            $rules = array_merge($rules, [
                'duration' => 'sometimes|string|max:255',
                'tutor' => 'sometimes|string|max:255',
                'modality' => 'nullable|string|max:255',
                'schedule' => 'nullable|string|max:255',
            ]);
        }

        return $rules;
    }
}

