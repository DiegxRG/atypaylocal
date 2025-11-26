<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Course;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'points_earned',
        'unit_type',
        'stock',
        'status',
        'image_path',
        'type'
    ];

    protected $hidden = ['created_at', 'updated_at', 'image_path'];
    protected $appends = ['image_url'];

    protected $casts = [
        'price' => 'float',
        'points_earned' => 'float',
        'stock' => 'integer'
    ];

    public function getImageUrlAttribute()
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }

    public function course()
    {
        return $this->hasOne(Course::class);
    }
}

