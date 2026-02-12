<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoterRecord extends Model
{
    protected $fillable = [
        'list_number',
        'id_card_number',
        'photo_path',
        'name',
        'sex',
        'mobile',
        'dob',
        'age',
        'island',
        'majilis_con',
        'address',
        'dhaairaa',
        'mayor',
        'raeesa',
        'council',
        'wdc',
        're_reg_travel',
        'comments',
        'vote_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'list_number' => 'integer',
            'dob' => 'date',
            'age' => 'integer',
        ];
    }
}
