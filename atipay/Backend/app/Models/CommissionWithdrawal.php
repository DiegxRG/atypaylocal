<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionWithdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'month',
        'year',
        'withdrawn_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
