<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pledge extends Model
{
    use HasFactory;

    protected $table = 'pledge';

    protected $fillable = [
        'voter_id',
        'mayor',
        'raeesa',
        'council',
        'wdc',
    ];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(VoterRecord::class, 'voter_id');
    }
}
