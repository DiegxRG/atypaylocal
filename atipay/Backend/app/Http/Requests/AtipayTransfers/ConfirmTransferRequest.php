<?php

namespace App\Http\Requests\AtipayTransfers;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'transfer_id' => 'required|exists:atipay_transfers,id',
        ];
    }
}
