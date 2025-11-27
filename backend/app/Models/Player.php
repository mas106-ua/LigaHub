<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Player extends Model
{
    use HasFactory;

    protected $table = 'players';
    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'date_of_birth',
        'position',
        'doc_number',
    ];

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_players')
            ->withPivot(['shirt_number', 'from_date', 'to_date']);
    }
}
