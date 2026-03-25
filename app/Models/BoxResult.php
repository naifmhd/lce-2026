<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxResult extends Model
{
    protected $fillable = [
        'registered_box',
        'invalid_votes',
    ];

    protected function casts(): array
    {
        return [
            'invalid_votes' => 'integer',
        ];
    }
}
