<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchReport extends Model
{
    use HasFactory;

    protected $table = 'match_reports';

    // La migración no tiene created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'match_id',
        'file_path',
        'generated_at',
        'checksum',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }
}
