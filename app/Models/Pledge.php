<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pledge extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'pledge';

    protected $fillable = [
        'voter_id',
        'mayor',
        'raeesa',
        'council',
        'wdc',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['mayor', 'raeesa', 'council', 'wdc'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pledge');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Pledge {$eventName}";
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(VoterRecord::class, 'voter_id');
    }
}
