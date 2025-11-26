<?php

namespace App\Http\Requests\AuthUsers;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class UpdatePartnerByAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->role->name === User::ROLE_ADMIN;
    }

    public function rules(): array
    {
        return [
            'username'    => 'sometimes|string|max:255',
            'email'       => 'sometimes|email|max:255|unique:users,email,' . $this->route('id'),
            'phone_number'=> 'sometimes|string|max:20|unique:users,phone_number,' . $this->route('id'),
            'password'    => 'sometimes|string|min:6|confirmed',
            'status'      => 'sometimes|in:active,inactive',
            'login_inactive'    => 'boolean',
        ];
    }
}
