<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtipayRecharge extends Model
{
    use HasFactory;

    protected $appends = ['proof_image_url'];

    protected $fillable = [
        'user_id',
        'full_names',
        'amount',
        'user_payment_method_id',
        'proof_image_path',
        'status',
        'approved_by',
        'atipays_granted',
        'request_date',
        'request_time',
        'processed_date',
        'processed_time',     
    ];

    protected $casts = [
        'amount' => 'float',
        'atipays_granted' => 'float',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'proof_image_path'
    ];

    // Usuario que solicita la recarga
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Admin que aprueba/rechaza
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    public function userPaymentMethod()
    {
        return $this->belongsTo(UserPaymentMethod::class);
    }

    public function getProofImageUrlAttribute()
    {
        return $this->proof_image_path
            ? asset('storage/' . $this->proof_image_path)
            : null;
    }
}
