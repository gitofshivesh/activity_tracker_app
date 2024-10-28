<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'mobile',        
        'is_active',
        'last_login_at',
        'mobile_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'mobile_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];


    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function otpTrails()
    {
        return $this->hasMany(OtpTrail::class);
    }

    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }

    public function challengeActivities()
    {
        return $this->hasMany(ChallengeActivity::class);
    }

    public function challengeTrails()
    {
        return $this->hasMany(ChallengeTrail::class);
    }
}
