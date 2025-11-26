<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPaymentMethod extends Model
{
    protected $fillable = ['user_id', 'payment_method_id', 'data'];

    protected $casts = [
        'data' => 'array'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}

