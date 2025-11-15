<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueMembership extends Model
{
    use HasFactory;

    protected $table = 'league_memberships';

    // Solo tienes joined_at, sin created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'league_id',
        'user_id',
        'role_in_league',
        'joined_at',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
