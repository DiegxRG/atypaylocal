<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Course extends Model
{
    protected $fillable = [
        'product_id',
        'duration',
        'tutor',
        'modality',
        'schedule',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}