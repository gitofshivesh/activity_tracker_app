<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    // public $table = 'challenges';

    protected $fillable = [
        'user_id',
        'challenge_title',
        'challenge_description',
        'start_date',
        'end_date',
        'frequency',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities()
    {
        return $this->hasMany(ChallengeActivity::class);
    }

    public function trails()
    {
        return $this->hasMany(ChallengeTrail::class);
    }
}
