<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<UserSession> $query */
    public function scopeActive(Builder $query): void
    {
        $lifetime = (int) config('session.lifetime');
        $query->where('last_activity_at', '>=', now()->subMinutes($lifetime));
    }
}
