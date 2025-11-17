<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    protected $fillable = [
        'match_id',
        'side',
        'minute',
        'extra_minute',
        'type',
        'player_id',
        'related_player_id',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'minute'        => 'integer',
        'extra_minute'  => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchModel::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function relatedPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'related_player_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
