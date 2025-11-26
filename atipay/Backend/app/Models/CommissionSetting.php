<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $fillable = [
        'level',
        'percentage'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public static function getPercentageForLevel($level)
    {
        return static::where('level', $level)->value('percentage') ?? 0;
    }
}
