<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VoterRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'list_number',
        'id_card_number',
        'agent',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['agent', 'registered_box', 'mobile', 're_reg_travel', 'comments'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('voter');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Voter record {$eventName}";
    }

    public function pledge(): HasOne
    {
        return $this->hasOne(Pledge::class, 'voter_id');
    }
}
