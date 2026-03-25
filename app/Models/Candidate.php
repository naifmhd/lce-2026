<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'candidate_number',
        'affiliation',
        'address',
        'race_id',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(ElectionRace::class, 'race_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CandidateVote::class, 'candidate_id');
    }
}
