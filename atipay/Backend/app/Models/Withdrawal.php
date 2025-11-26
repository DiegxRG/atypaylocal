<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'holder',
        'phone_number',
        'account_number',
        'amount',
        'commission',
        'net_amount',
        'status',
        'date',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'float',
        'commission' => 'float',
        'net_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor para formatear la fecha
    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }
}
