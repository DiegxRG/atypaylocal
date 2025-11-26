<?php

namespace App\Http\Requests\Rewards;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rewardId = $this->route('reward'); 

        return [
            'name' => 'sometimes|string|max:255|unique:rewards,name,' . $rewardId,
            'description' => 'nullable|string',
            'reward_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'redeem_points' => 'sometimes|integer|min:1',
            'stock' => 'sometimes|integer|min:0',
        ];
    }
}
