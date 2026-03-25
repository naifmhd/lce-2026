<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateVote extends Model
{
    protected $fillable = [
        'box_race_result_id',
        'candidate_id',
        'votes',
    ];

    protected function casts(): array
    {
        return [
            'votes' => 'integer',
        ];
    }

    public function boxRaceResult(): BelongsTo
    {
        return $this->belongsTo(BoxRaceResult::class, 'box_race_result_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }
}
