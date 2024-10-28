<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile',
        'otp',
        'otp_expire_at',
        'is_verified',
    ];

    protected $casts = [
        'otp_expire_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
