<?php

namespace App\Enums;

enum GuideStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending review',
            self::Published => 'Published',
        };
    }

    /**
     * Tailwind classes for the status badge. brand-gold flags "needs
     * attention" (pending); brand-gray marks drafts as inert. Both are used
     * sparingly, per the brand rules.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-brand-gray/15 text-brand-muted border-brand-gray/30',
            self::Pending => 'bg-brand-gold/15 text-brand-gold border-brand-gold/40',
            self::Published => 'bg-brand-blue/10 text-brand-blue border-brand-blue/30',
        };
    }
}
