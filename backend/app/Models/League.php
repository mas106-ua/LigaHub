<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class League extends Model
{
    use HasFactory;

    protected $table = 'leagues';

    protected $fillable = [
        'name', 'type', 'region_id', 'category_id', 'season_id',
        'visibility', 'access_uuid', 'owner_user_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function region(){ return $this->belongsTo(Region::class); }
    public function season(){ return $this->belongsTo(Season::class); }
    public function category(){ return $this->belongsTo(Category::class); }
    public function province(){ return $this->belongsTo(Province::class); }


    // Scopes útiles
    public function scopeOfficial($q){ return $q->where('type', 'official'); }
    public function scopePublic($q){ return $q->where('visibility', 'public'); }
}
