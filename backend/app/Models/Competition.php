<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Competition extends Model
{
    use HasFactory;

    protected $table = 'competitions';

    protected $fillable = [
        'name',
        'code',
        'category_id',
        'level',
        'gender',
        'region_id',
        'province_id',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relaciones básicas
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    // Ligas/ediciones asociadas a esta competición
    public function leagues()
    {
        return $this->hasMany(League::class);
    }

    // Temporadas asociadas a través de las ligas
    public function seasons()
    {
        return $this->hasManyThrough(Season::class, League::class);
    }

    // Scopes opcionales
    public function scopeOfficial($q)
    {
        return $q->where('type', 'official');
    }

    public function scopePrivate($q)
    {
        return $q->where('type', 'private');
    }
}
