<?php

namespace App\Models;

use App\Enums\Role;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function guides(): HasMany
    {
        return $this->hasMany(Guide::class, 'created_by');
    }

    /**
     * Highest rank across all roles assigned to this user. Users normally
     * hold exactly one role, but spatie allows several, so the strongest is
     * taken rather than assuming the first.
     */
    public function roleLevel(): int
    {
        return $this->getRoleNames()
            ->map(fn (string $name) => Role::tryFrom($name)?->level() ?? 0)
            ->max() ?? 0;
    }

    public function hasAtLeastRole(Role $role): bool
    {
        return $this->roleLevel() >= $role->level();
    }

    public function isContributor(): bool
    {
        return $this->hasAtLeastRole(Role::Contributor);
    }

    public function isApprover(): bool
    {
        return $this->hasAtLeastRole(Role::Approver);
    }

    public function isAdmin(): bool
    {
        return $this->hasAtLeastRole(Role::Admin);
    }
}
