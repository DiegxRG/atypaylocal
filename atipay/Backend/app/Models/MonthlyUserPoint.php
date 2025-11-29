<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyUserPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month',
        'year',
        'points',
    ];

    // Relación para saber de qué usuario son los puntos
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}