<?php 
namespace App\Http\Requests\AtipayRecharges;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => 'required|exists:payment_methods,id',
            'data' => 'required|array',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'Debes elegir un método de pago válido.',
            'data.required' => 'Debes completar los campos requeridos para este método de pago.',
        ];
    }
}
