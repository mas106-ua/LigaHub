<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'short_name',
        'city',
        'crest_url',
    ];

    public function leagues(){ return $this->belongsToMany(League::class, 'league_teams')->withPivot('group_name'); }
}
