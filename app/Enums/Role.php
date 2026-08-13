<?php

namespace App\Enums;

enum Role: string
{
    case Viewer = 'viewer';
    case Contributor = 'contributor';
    case Approver = 'approver';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Viewer => 'Viewer',
            self::Contributor => 'Contributor',
            self::Approver => 'Approver',
            self::Admin => 'Admin',
        };
    }

    /**
     * Rank used for "at least this role" checks.
     *
     * spatie/laravel-permission roles are a flat set, not a hierarchy. This
     * layers one on top so a route can say "requires at least contributor"
     * and have an approver or admin satisfy it without being assigned every
     * lower role individually.
     */
    public function level(): int
    {
        return match ($this) {
            self::Viewer => 1,
            self::Contributor => 2,
            self::Approver => 3,
            self::Admin => 4,
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
