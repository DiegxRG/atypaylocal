<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtipayTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'amount',
        'status',
        'registration_date',
        'registration_time',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    //El usuario que recibió los Atipay
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Atributos personalizados
    protected $appends = ['sender_username', 'receiver_username'];

    public function getSenderUsernameAttribute()
    {
        return $this->sender ? $this->sender->username : null;
    }

    public function getReceiverUsernameAttribute()
    {
        return $this->receiver ? $this->receiver->username : null;
    }
}

