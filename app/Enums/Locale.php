<?php

namespace App\Enums;

enum Locale: string
{
    case Arabic = 'ar';
    case English = 'en';

    /**
     * The language's own name, not its English name -- a switcher that says
     * "Arabic" is useless to someone who cannot read the current interface.
     */
    public function nativeLabel(): string
    {
        return match ($this) {
            self::Arabic => 'العربية',
            self::English => 'English',
        };
    }

    public function direction(): string
    {
        return match ($this) {
            self::Arabic => 'rtl',
            self::English => 'ltr',
        };
    }

    public function isRtl(): bool
    {
        return $this->direction() === 'rtl';
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): self
    {
        return self::Arabic;
    }
}
