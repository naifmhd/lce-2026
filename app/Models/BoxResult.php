<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxResult extends Model
{
    protected $fillable = [
        'registered_box',
        'election_type',
        'invalid_votes',
    ];

    public static function electionTypeForRaceType(string $raceType): string
    {
        return match ($raceType) {
            'council', 'mayor' => 'local_council',
            'wdc', 'raeesa' => 'wdc',
            default => $raceType,
        };
    }

    protected function casts(): array
    {
        return [
            'invalid_votes' => 'integer',
        ];
    }
}
