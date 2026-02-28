<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'roles' => 'array',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function roleKeys(): array
    {
        $roles = $this->roles;

        if (! is_array($roles)) {
            return [];
        }

        $validRoles = UserRole::keys();

        return array_values(array_filter(
            array_map(static fn (mixed $role): string => is_string($role) ? trim($role) : '', $roles),
            static fn (string $role): bool => $role !== '' && in_array($role, $validRoles, true),
        ));
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roleKeys(), true);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($this->roleKeys(), $roles)) > 0;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    public function hasAnyAssignedRole(): bool
    {
        return $this->roleKeys() !== [];
    }

    public function hasFullVoterAccess(): bool
    {
        return $this->hasAnyRole(UserRole::fullAccessRoleKeys());
    }

    /**
     * @return array<int, string>
     */
    public function allowedDhaairaaCodes(): array
    {
        $codes = [];

        foreach ($this->roleKeys() as $role) {
            $dhaairaaCode = UserRole::dhaairaaCodeForRole($role);

            if ($dhaairaaCode !== null) {
                $codes[] = $dhaairaaCode;
            }
        }

        return array_values(array_unique($codes));
    }
}
