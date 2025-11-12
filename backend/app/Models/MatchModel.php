<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MatchModel extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'league_id',
        'matchday_number',   
        'home_team_id',
        'away_team_id',
        'scheduled_at',
        'status',            
        'home_goals',
        'away_goals',
        'venue_id',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
}
