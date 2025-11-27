<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $table = 'venues';
    public $timestamps = false; // tu tabla venues no tiene created_at/updated_at

    protected $fillable = ['name','address','city','lat','lng'];
}
