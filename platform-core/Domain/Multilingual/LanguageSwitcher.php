<?php

namespace Poradnik\Platform\Domain\Multilingual;

if (! defined('ABSPATH')) {
    exit;
}

final class LanguageSwitcher
{
    public static function renderHtml(): string
    {
        $settings = LanguageManager::getSettings();
        $enabled = (array) ($settings['enabled_languages'] ?? ['pl']);
        $current = LanguageManager::currentLanguage();
        $all = LanguageManager::supportedLanguages();

        $path = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = (string) $_SERVER['REQUEST_URI'];
            $parts = explode('/', trim($uri, '/'));
            $supported = array_keys($all);
            $firstPart = isset($parts[0]) ? sanitize_key($parts[0]) : '';

            if ($firstPart !== '' && in_array($firstPart, $supported, true)) {
                array_shift($parts);
            }

            $path = implode('/', $parts);
        }

        $items = '';
        foreach ($enabled as $lang) {
            if (! isset($all[$lang])) {
                continue;
            }

            $href = LanguageManager::langUrl($path, $lang);
            $label = $all[$lang];
            $active = ($lang === $current) ? ' class="poradnik-lang-active"' : '';

            $items .= '<li' . $active . '><a href="' . esc_url($href) . '" hreflang="' . esc_attr($lang) . '">' . esc_html($label) . '</a></li>';
        }

        if ($items === '') {
            return '';
        }

        return '<nav class="poradnik-language-switcher" aria-label="' . esc_attr__('Language switcher', 'poradnik-platform') . '"><ul>' . $items . '</ul></nav>';
    }
}
