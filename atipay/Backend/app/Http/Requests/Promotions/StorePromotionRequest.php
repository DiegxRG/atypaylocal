<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'percentaje' => 'required|numeric|min:0|max:100',
            'points_earned' => 'required|numeric|min:0', 
            'atipay_price_promotion' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ];
    }
}
