<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    public $timestamps = false;

    protected $fillable = ['name', 'level', 'gender'];

    public function leagues()
    {
        return $this->hasMany(League::class);
    }
}
