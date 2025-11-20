<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaguePlayerStatOverride extends Model
{
    protected $table = 'league_player_stat_overrides';

    protected $fillable = [
        'league_id',
        'player_id',
        'goals_delta',
        'assists_delta',
        'yellow_cards_delta',
        'red_cards_delta',
        'reason',
        'created_by',
    ];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
