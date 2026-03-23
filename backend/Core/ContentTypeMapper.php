<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class ContentTypeMapper
{
    /**
     * @var array<string, string>
     */
    private const ALIASES = [
        'guide' => 'guide',
        'poradnik' => 'guide',
        'ranking' => 'ranking',
        'review' => 'review',
        'recenzja' => 'review',
        'comparison' => 'comparison',
        'porownanie' => 'comparison',
        'tool' => 'tool',
        'narzedzie' => 'tool',
        'news' => 'news',
        'aktualnosc' => 'news',
        'pytanie' => 'guide',
        'odpowiedz' => 'guide',
        'affiliate' => 'review',
    ];

    /**
     * @return array<int, string>
     */
    public static function apiAllowedAliases(): array
    {
        return array_values(array_keys(self::ALIASES));
    }

    public static function normalizePostType(string $value, string $fallback = 'guide'): string
    {
        $normalized = sanitize_key($value);

        if ($normalized !== '' && isset(self::ALIASES[$normalized])) {
            return self::ALIASES[$normalized];
        }

        return isset(self::ALIASES[$fallback]) ? self::ALIASES[$fallback] : 'guide';
    }
}
