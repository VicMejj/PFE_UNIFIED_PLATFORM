<?php

namespace App\Models;

use App\Models\Employee\Employee;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\URL;
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

    protected $appends = ['avatar_url'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'storage_limit' => 'float',
        'is_active' => 'boolean',
        'active_status' => 'boolean',
        'is_disable' => 'boolean',
        'dark_mode' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
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

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return URL::to('/').'/'.ltrim($this->avatar, '/');
    }
}
