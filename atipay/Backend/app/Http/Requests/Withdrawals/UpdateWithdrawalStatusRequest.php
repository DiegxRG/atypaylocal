<?php

namespace App\Http\Requests\Withdrawals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWithdrawalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:approved,rejected',
        ];
    }
}