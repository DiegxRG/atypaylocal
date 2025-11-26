<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity', 'payment_method', 'status', 'admin_message', 'request_date', 'request_time'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function user() { return $this->belongsTo(User::class); }
    
    public function product() { return $this->belongsTo(Product::class); }
}