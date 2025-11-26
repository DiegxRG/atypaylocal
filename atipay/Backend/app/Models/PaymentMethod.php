<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'fields'];

    protected $casts = [
        'fields' => 'array'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function userMethods()
    {
        return $this->hasMany(UserPaymentMethod::class);
    }
}

