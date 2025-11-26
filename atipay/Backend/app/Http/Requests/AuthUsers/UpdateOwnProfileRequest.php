<?php

namespace App\Http\Requests\AuthUsers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateOwnProfileRequest extends FormRequest
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
        ];
    }

    public function messages()
    {
        return [
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'email.unique'    => 'El correo electrónico ya está en uso.',
        ];
    }
}
