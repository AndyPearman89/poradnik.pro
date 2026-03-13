<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Multilingual\LanguageManager;

if (! defined('ABSPATH')) {
    exit;
}

final class MultilingualPage
{
    private const PAGE_SLUG = 'poradnik-multilingual';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_poradnik_multilingual_save', [self::class, 'handleSave']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Multilingual SEO', 'poradnik-platform'),
            __('Multilingual SEO', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleSave(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to save multilingual settings.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_multilingual_save');

        $enabledLanguages = isset($_POST['enabled_languages']) && is_array($_POST['enabled_languages'])
            ? array_map('sanitize_key', $_POST['enabled_languages'])
            : ['pl'];

        LanguageManager::saveSettings([
            'default_language' => isset($_POST['default_language']) ? sanitize_key((string) wp_unslash($_POST['default_language'])) : 'pl',
            'enabled_languages' => $enabledLanguages,
            'url_strategy' => isset($_POST['url_strategy']) ? sanitize_key((string) wp_unslash($_POST['url_strategy'])) : 'prefix',
            'switcher_position' => isset($_POST['switcher_position']) ? sanitize_key((string) wp_unslash($_POST['switcher_position'])) : 'header',
        ]);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'updated' => '1'], admin_url('tools.php')));
        exit;
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $settings = LanguageManager::getSettings();
        $languages = LanguageManager::supportedLanguages();

        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Multilingual settings saved.', 'poradnik-platform') . '</p></div>';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Multilingual SEO', 'poradnik-platform') . '</h1>';
        echo '<p>' . esc_html__('Configure supported languages, URL strategy, and hreflang mapping.', 'poradnik-platform') . '</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('poradnik_multilingual_save');
        echo '<input type="hidden" name="action" value="poradnik_multilingual_save" />';

        echo '<table class="form-table" role="presentation">';

        echo '<tr><th scope="row">' . esc_html__('Default Language', 'poradnik-platform') . '</th><td>';
        echo '<select name="default_language">';
        foreach ($languages as $code => $label) {
            echo '<option value="' . esc_attr($code) . '" ' . selected((string) $settings['default_language'], $code, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Enabled Languages', 'poradnik-platform') . '</th><td>';
        $enabledLangs = (array) $settings['enabled_languages'];
        foreach ($languages as $code => $label) {
            $checked = in_array($code, $enabledLangs, true) ? ' checked' : '';
            echo '<label style="margin-right:16px;"><input type="checkbox" name="enabled_languages[]" value="' . esc_attr($code) . '"' . $checked . '> ' . esc_html($label) . ' (' . esc_html($code) . ')</label>';
        }
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__('URL Strategy', 'poradnik-platform') . '</th><td>';
        echo '<select name="url_strategy">';
        $urlStrategies = ['prefix' => '/pl/, /en/, /de/...', 'subdomain' => 'pl.domain.com, en.domain.com', 'query' => '?lang=pl'];
        foreach ($urlStrategies as $key => $description) {
            echo '<option value="' . esc_attr($key) . '" ' . selected((string) $settings['url_strategy'], $key, false) . '>' . esc_html($description) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Language Switcher Position', 'poradnik-platform') . '</th><td>';
        echo '<select name="switcher_position">';
        $positions = ['header' => __('Header', 'poradnik-platform'), 'footer' => __('Footer', 'poradnik-platform'), 'sidebar' => __('Sidebar', 'poradnik-platform')];
        foreach ($positions as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected((string) $settings['switcher_position'], $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '</table>';
        submit_button(__('Save Settings', 'poradnik-platform'));
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__('Hreflang Preview', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('Active hreflang tags generated for the homepage:', 'poradnik-platform') . '</p>';
        echo '<code>';
        foreach ($enabledLangs as $lang) {
            $locale = ['pl' => 'pl-PL', 'en' => 'en-US', 'de' => 'de-DE', 'es' => 'es-ES', 'fr' => 'fr-FR'][$lang] ?? $lang;
            $href = LanguageManager::langUrl('', $lang);
            echo esc_html('<link rel="alternate" hreflang="' . $locale . '" href="' . $href . '" />') . '<br />';
        }
        echo '</code>';

        echo '</div>';
    }
}
