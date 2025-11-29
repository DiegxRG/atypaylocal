<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;


class ReferralCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referred_user_id',
        'level',
        'commission_amount',
        'points_generated',
        'source_type',
        'month',
        'year',
        'withdrawn',
        'locked' // <--- ¡IMPORTANTE: Agrega esto!
    ];

    // --- Filtros útiles para el cierre mensual ---

    public function scopeByMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
