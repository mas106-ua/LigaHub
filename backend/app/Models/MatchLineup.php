<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MatchLineup extends Model
{
    use HasFactory;

    protected $table = 'match_lineups';

    protected $fillable = [
        'match_id',
        'team_id',
        'side',        // 'home' | 'away'
        'formation',
        'coach_name',
        'starters',
        'bench',
    ];

    protected $casts = [
        'starters' => 'array',
        'bench'    => 'array',
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
