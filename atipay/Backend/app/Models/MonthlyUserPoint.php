<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MonthlyUserPoint extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'points'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
