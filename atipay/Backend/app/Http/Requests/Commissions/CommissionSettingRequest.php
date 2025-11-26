<?php

namespace App\Http\Requests\Commissions;

use Illuminate\Foundation\Http\FormRequest;

class CommissionSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'integer', 'between:1,5'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'level.between' => 'El nivel debe estar entre 1 y 5.',
            'percentage.max' => 'El porcentaje no puede ser mayor a 100.',
            'percentage.min' => 'El porcentaje no puede ser negativo.',
        ];
    }
}
