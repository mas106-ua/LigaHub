<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';
    public $timestamps = false;

    protected $fillable = ['name', 'code'];

    public function leagues()
    {
        return $this->hasMany(League::class);
    }
}
