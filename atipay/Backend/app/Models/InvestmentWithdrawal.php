<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_id',
        'amount',
        'transferred_at'
    ];

    protected $casts = [
        'amount'        => 'float',
        'transferred_at'=> 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
