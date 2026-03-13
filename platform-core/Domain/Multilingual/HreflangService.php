<?php

namespace Poradnik\Platform\Domain\Multilingual;

if (! defined('ABSPATH')) {
    exit;
}

final class HreflangService
{
    public static function renderHreflangTags(): void
    {
        $settings = LanguageManager::getSettings();
        $enabled = (array) ($settings['enabled_languages'] ?? ['pl']);
        $current = LanguageManager::currentLanguage();
        $default = (string) ($settings['default_language'] ?? 'pl');

        $path = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = (string) $_SERVER['REQUEST_URI'];
            $parts = explode('/', trim($uri, '/'));
            $supported = array_keys(LanguageManager::supportedLanguages());
            $firstPart = isset($parts[0]) ? sanitize_key($parts[0]) : '';

            if ($firstPart !== '' && in_array($firstPart, $supported, true)) {
                array_shift($parts);
            }

            $path = implode('/', $parts);
        }

        foreach ($enabled as $lang) {
            $href = LanguageManager::langUrl($path, $lang);
            $locale = self::langToLocale($lang);

            echo '<link rel="alternate" hreflang="' . esc_attr($locale) . '" href="' . esc_url($href) . '" />' . "\n";
        }

        $xDefault = LanguageManager::langUrl($path, $default);
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($xDefault) . '" />' . "\n";
    }

    private static function langToLocale(string $lang): string
    {
        $map = [
            'pl' => 'pl-PL',
            'en' => 'en-US',
            'de' => 'de-DE',
            'es' => 'es-ES',
            'fr' => 'fr-FR',
        ];

        return $map[$lang] ?? $lang;
    }
}
