<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\AtipayTransfer;
use App\Models\Withdrawal;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Carbon\Carbon;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PARTNER = 'partner';

    protected $fillable = [
        'username',
        'email',
        'phone_number',
        'role_id',
        'password',
        'status',
        'atipay_money',
        'accumulated_points',
        'reference_code',
        'referred_by',
        'reference_code_valid',
        'registration_date',
        'registration_time',
        'last_login_at',
        'login_inactive',
    ];

    protected $appends = ['referral_url'];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'atipay_money' => 'float'
    ];

    //new
    public function isInactiveOverTwoMonths(): bool
    {
        if ($this->role && $this->role->name === self::ROLE_PARTNER) {
            if (!$this->last_login_at) {
                // Nunca inició sesión -> no bloquear
                return false;
            }

            return Carbon::parse($this->last_login_at)
                ->diffInMonths(Carbon::now()) >= 2;
        }
        return false;
    }

    public function getRegistrationTimeAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('h:i:s A') : null;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    // Devuelve la URL construida
    public function getReferralUrlAttribute()
    {
        return url("/atipay/{$this->username}/reference-code-register/{$this->reference_code}");
    }

    // Mutador para encriptar la contraseña
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Referido por
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    // Referencias a otros usuarios
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }


    // Transferencias enviadas por el usuario
    public function sentTransfers()
    {
        return $this->hasMany(AtipayTransfer::class, 'sender_id');
    }

    // Transferencias recibidas por el usuario
    public function receivedTransfers()
    {
        return $this->hasMany(AtipayTransfer::class, 'receiver_id');
    }

    // Relacion con la cuenta del usuario

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    // Recargas solicitadas por el usuario
    public function atipayRecharges()
    {
        return $this->hasMany(AtipayRecharge::class);
    }

    public function referralCommissions()
    {
        return $this->hasMany(ReferralCommission::class);
    }

    public function receivedReferralCommissions()
    {
        return $this->hasMany(ReferralCommission::class, 'referred_user_id');
    }

    public function monthlyPoints()
    {
        return $this->hasMany(MonthlyUserPoint::class);
    }

    //Inversiones
    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    protected static function booted()
    {
        static::updating(function ($user) {
            if ($user->isDirty('login_inactive')) {
                if ($user->login_inactive == 0) {
                    $user->last_login_at = now();
                }
            }
        });
    }
}
