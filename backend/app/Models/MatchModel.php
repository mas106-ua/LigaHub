<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\MatchLineup;

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

    public function league(): BelongsTo { return $this->belongsTo(League::class); }
    public function homeTeam(): BelongsTo { return $this->belongsTo(Team::class, 'home_team_id'); }
    public function awayTeam(): BelongsTo { return $this->belongsTo(Team::class, 'away_team_id'); }
    public function venue(): BelongsTo { return $this->belongsTo(Venue::class); }
    public function lineups(): HasMany { return $this->hasMany(MatchLineup::class, 'match_id'); }
}
