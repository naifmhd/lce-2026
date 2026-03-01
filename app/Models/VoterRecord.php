<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VoterRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_number',
        'id_card_number',
        'photo_path',
        'name',
        'sex',
        'mobile',
        'dob',
        'age',
        'registered_box',
        'majilis_con',
        'address',
        'dhaairaa',
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

    public function pledge(): HasOne
    {
        return $this->hasOne(Pledge::class, 'voter_id');
    }
}
