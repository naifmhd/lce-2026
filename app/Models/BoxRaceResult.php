<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxRaceResult extends Model
{
    protected $fillable = [
        'registered_box',
        'race_id',
        'invalid_votes',
    ];

    protected function casts(): array
    {
        return [
            'invalid_votes' => 'integer',
        ];
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(ElectionRace::class, 'race_id');
    }

    public function candidateVotes(): HasMany
    {
        return $this->hasMany(CandidateVote::class, 'box_race_result_id');
    }
}
