<?php

namespace Poradnik\Platform\Modules\Multilingual;

use Poradnik\Platform\Admin\MultilingualPage;
use Poradnik\Platform\Domain\Multilingual\HreflangService;
use Poradnik\Platform\Domain\Multilingual\LanguageSwitcher;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            MultilingualPage::init();
        }

        add_action('wp_head', [HreflangService::class, 'renderHreflangTags'], 4);
        add_action('wp_footer', [self::class, 'maybeRenderSwitcherInFooter'], 20);
        add_filter('the_content', [self::class, 'maybeInjectSwitcherInContent'], 5);

        add_shortcode('poradnik_language_switcher', [self::class, 'shortcode']);
    }

    public static function maybeRenderSwitcherInFooter(): void
    {
        $settings = \Poradnik\Platform\Domain\Multilingual\LanguageManager::getSettings();

        if (($settings['switcher_position'] ?? 'header') !== 'footer') {
            return;
        }

        echo LanguageSwitcher::renderHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public static function maybeInjectSwitcherInContent(string $content): string
    {
        if (! is_singular() || ! is_main_query()) {
            return $content;
        }

        $settings = \Poradnik\Platform\Domain\Multilingual\LanguageManager::getSettings();

        if (($settings['switcher_position'] ?? 'header') !== 'sidebar') {
            return $content;
        }

        return LanguageSwitcher::renderHtml() . $content;
    }

    /**
     * @param array<string, string> $atts
     */
    public static function shortcode(array $atts = []): string
    {
        return LanguageSwitcher::renderHtml();
    }
}
