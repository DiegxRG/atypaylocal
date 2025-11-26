<?php

namespace App\Http\Requests\AuthUsers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = Auth::id();

        return [
            'username' => 'sometimes|string|min:3|max:50|unique:users,username,' . $userId,
            'email'    => 'sometimes|email|unique:users,email,' . $userId,
            'phone_number'  => 'sometimes|string|max:20|unique:users,phone_number,' . $userId,
            'password' => 'nullable|string|min:6|confirmed',
            'status'   => 'sometimes|in:active,inactive',
        ];
    }

    public function messages()
    {
        return [
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'email.unique'    => 'El correo electrónico ya está en uso.',
            'phone_number.unique'    => 'El numero de celular ya está en uso.',
            'status.in'       => 'El estado debe ser "active" o "inactive".',
        ];
    }
}
