<?php

namespace Poradnik\Platform\Modules\Adsense\Services;

if (! defined('ABSPATH')) {
    exit;
}

final class AdsenseSlots
{
    public const HERO = 'hero';
    public const SIDEBAR = 'sidebar';
    public const INLINE = 'inline';
    public const FOOTER = 'footer';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::HERO,
            self::SIDEBAR,
            self::INLINE,
            self::FOOTER,
        ];
    }

    public static function sanitize(string $slot): string
    {
        $slot = sanitize_key($slot);

        if (! in_array($slot, self::all(), true)) {
            return self::INLINE;
        }

        return $slot;
    }
}
