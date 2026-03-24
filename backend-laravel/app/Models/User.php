<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected string $guard_name = 'api';
    protected $with = ['roles'];

    protected $fillable = [
        'name', 'email', 'password', 'type', 'avatar', 'lang',
        'plan', 'plan_expire_date', 'requested_plan', 'trial_expire_date',
        'trial_plan', 'is_login_enable', 'storage_limit', 'last_login',
        'is_active', 'referral_code', 'used_referral_code', 'commission_amount',
        'active_status', 'is_disable', 'google2fa_secret', 'google2fa_enable',
        'dark_mode', 'messenger_color', 'created_by',
    ];

    protected $hidden = ['password', 'remember_token', 'google2fa_secret'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'storage_limit' => 'float',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    // convenience helpers
    public function isActive(): bool
    {
        return $this->is_active === 1;
    }

    public function assignDefaultRole()
    {
        if (! $this->roles()->exists()) {
            $this->assignRole('user');
        }
    }

    /**
     * JWTSubject implementation required by jwt-auth package
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
