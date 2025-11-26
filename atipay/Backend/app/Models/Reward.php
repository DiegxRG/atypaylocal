<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'reward_image',
        'redeem_points',
        'stock'
    ];

    protected $hidden = ['created_at', 'updated_at', 'reward_image'];

    protected $casts = [
        'redeem_points' => 'float',
        'stock' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->reward_image ? asset('storage/' . $this->reward_image) : null;
    }
}
