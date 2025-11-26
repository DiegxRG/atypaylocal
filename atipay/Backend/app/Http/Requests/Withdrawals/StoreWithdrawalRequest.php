<?php 

namespace App\Http\Requests\Withdrawals;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'holder' => 'required|string|max:255',
            'method' => 'required|in:yape,plin,transferencia_bancaria,transferencia_electronica',
            'phone_number' => 'nullable|required_if:method,yape,plin|string|max:20',
            'account_number' => 'nullable|required_if:method,transferencia_bancaria,transferencia_electronica|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required_if' => 'Debe ingresar un número de celular si el método es Yape o Plin.',
            'account_number.required_if' => 'Debe ingresar un número de cuenta si el método es Transferencia.',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth('api')->id(),
            'date'    => now()->toDateString(),
        ]);
    }
}