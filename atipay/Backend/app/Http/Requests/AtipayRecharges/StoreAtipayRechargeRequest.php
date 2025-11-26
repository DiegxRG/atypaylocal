<?php

namespace App\Http\Requests\AtipayRecharges;

use Illuminate\Foundation\Http\FormRequest;

class StoreAtipayRechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_names' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'user_payment_method_id' => 'required|exists:user_payment_methods,id',
            'proof_image' => 'required|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'user_payment_method_id.required' => 'Debes seleccionar un método de pago configurado.',
            'user_payment_method_id.exists' => 'El método de pago seleccionado no existe.',
        ];
    }
}

