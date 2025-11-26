<?php

namespace App\Http\Requests\Investments;

use Illuminate\Foundation\Http\FormRequest;

class RequestWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'investment_id' => ['required', 'exists:investments,id'],
            'amount' => ['required', 'numeric', 'min:0.1'],
        ];
    }
}
