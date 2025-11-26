<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promotion_id',
        'status',
        'admin_message',
        'daily_earning',
        'total_earning',
        'already_earned',
        'approved_at',
        'rejected_at',
        'start_date',
        'end_date'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'daily_earning'   => 'float',
        'total_earning'   => 'float',
        'already_earned'  => 'float',
        'approved_at'     => 'datetime',
        'rejected_at'     => 'datetime',
        'start_date'      => 'datetime',
        'end_date'        => 'datetime',
    ];

    protected function approvedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? Carbon::parse($value)->format('Y-m-d H:i:s')
                : null,
        );
    }

    protected function rejectedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? Carbon::parse($value)->format('Y-m-d H:i:s')
                : null,
        );
    }

    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null
        );
    }

    protected function endDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null
        );
    }

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(InvestmentWithdrawal::class);
    }
}
