<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionRace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'dhaaira',
        'type',
        'sort_order',
        'projected_winner',
        'projection_confidence',
        'projection_reasoning',
        'projection_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'projection_updated_at' => 'datetime',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'race_id');
    }

    public function boxRaceResults(): HasMany
    {
        return $this->hasMany(BoxRaceResult::class, 'race_id');
    }

    public function isIslandWide(): bool
    {
        return $this->dhaaira === null;
    }
}
