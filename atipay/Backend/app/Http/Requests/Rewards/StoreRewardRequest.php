<?php

namespace App\Http\Requests\Rewards;

use Illuminate\Foundation\Http\FormRequest;

class StoreRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reward_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'redeem_points' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
        ];
    }
}
