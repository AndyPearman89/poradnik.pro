<?php

namespace Poradnik\Platform\Domain\Multilingual;

if (! defined('ABSPATH')) {
    exit;
}

final class LanguageManager
{
    private const OPTION_KEY = 'poradnik_multilingual_settings';

    /**
     * @return array<string, string>
     */
    public static function supportedLanguages(): array
    {
        return [
            'pl' => 'Polski',
            'en' => 'English',
            'de' => 'Deutsch',
            'es' => 'Español',
            'fr' => 'Français',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSettings(): array
    {
        $defaults = [
            'default_language' => 'pl',
            'enabled_languages' => ['pl', 'en', 'de', 'es', 'fr'],
            'url_strategy' => 'prefix',
            'switcher_position' => 'header',
        ];

        $stored = get_option(self::OPTION_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        return array_merge($defaults, $stored);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function saveSettings(array $settings): void
    {
        $clean = [
            'default_language' => sanitize_key((string) ($settings['default_language'] ?? 'pl')),
            'enabled_languages' => self::sanitizeLanguageList($settings['enabled_languages'] ?? []),
            'url_strategy' => in_array($settings['url_strategy'] ?? '', ['prefix', 'subdomain', 'query'], true)
                ? (string) $settings['url_strategy']
                : 'prefix',
            'switcher_position' => in_array($settings['switcher_position'] ?? '', ['header', 'footer', 'sidebar'], true)
                ? (string) $settings['switcher_position']
                : 'header',
        ];

        update_option(self::OPTION_KEY, $clean, false);
    }

    public static function currentLanguage(): string
    {
        $settings = self::getSettings();
        $default = (string) $settings['default_language'];
        $supported = array_keys(self::supportedLanguages());

        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = (string) $_SERVER['REQUEST_URI'];
            $parts = explode('/', trim($uri, '/'));
            $maybeLang = isset($parts[0]) ? sanitize_key($parts[0]) : '';

            if ($maybeLang !== '' && in_array($maybeLang, $supported, true)) {
                return $maybeLang;
            }
        }

        return $default;
    }

    /**
     * @param string $path
     * @param string $lang
     */
    public static function langUrl(string $path = '', string $lang = ''): string
    {
        if ($lang === '') {
            $lang = self::currentLanguage();
        }

        $settings = self::getSettings();
        $default = (string) $settings['default_language'];

        $base = home_url('/');
        $path = ltrim($path, '/');

        if ($lang === $default) {
            return $base . $path;
        }

        return $base . $lang . '/' . $path;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function sanitizeLanguageList($value): array
    {
        if (! is_array($value)) {
            return ['pl'];
        }

        $supported = array_keys(self::supportedLanguages());
        $clean = [];

        foreach ($value as $lang) {
            $lang = sanitize_key((string) $lang);
            if (in_array($lang, $supported, true)) {
                $clean[] = $lang;
            }
        }

        return $clean === [] ? ['pl'] : $clean;
    }
}
