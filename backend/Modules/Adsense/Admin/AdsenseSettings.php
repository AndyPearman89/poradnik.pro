<?php

namespace Poradnik\Platform\Modules\Adsense\Admin;

use Poradnik\Platform\Modules\Adsense\Services\AdsenseSlots;

if (! defined('ABSPATH')) {
    exit;
}

final class AdsenseSettings
{
    private const PAGE_SLUG = 'poradnik-adsense-settings';
    private const OPTION_KEY = 'poradnik_adsense_settings';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_init', [self::class, 'handleSave']);
    }

    public static function registerPage(): void
    {
        add_options_page(
            __('AdSense Settings - Poradnik Platform', 'poradnik-platform'),
            __('AdSense (Poradnik)', 'poradnik-platform'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleSave(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        if (! isset($_POST['poradnik_adsense_save'])) {
            return;
        }

        check_admin_referer('poradnik_adsense_settings_save');

        $settings = [
            'enabled' => isset($_POST['enabled']) ? '1' : '0',
            'client_id' => isset($_POST['client_id']) ? sanitize_text_field((string) wp_unslash($_POST['client_id'])) : '',
            'auto_insert_inline' => isset($_POST['auto_insert_inline']) ? '1' : '0',
            'insert_after_paragraph' => isset($_POST['insert_after_paragraph']) ? max(1, absint((string) wp_unslash($_POST['insert_after_paragraph']))) : 3,
            'auto_ads_code' => isset($_POST['auto_ads_code']) ? wp_kses_post((string) wp_unslash($_POST['auto_ads_code'])) : '',
        ];

        foreach (AdsenseSlots::all() as $slot) {
            $key = 'slot_' . $slot;
            $settings[$key] = isset($_POST[$key]) ? sanitize_text_field((string) wp_unslash($_POST[$key])) : '';
        }

        update_option(self::OPTION_KEY, $settings, false);

        $redirect = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                'updated' => '1',
            ],
            admin_url('options-general.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'poradnik-platform'));
        }

        $settings = get_option(self::OPTION_KEY, []);
        if (! is_array($settings)) {
            $settings = [];
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AdSense Settings - Poradnik Platform', 'poradnik-platform') . '</h1>';

        if (isset($_GET['updated']) && (string) wp_unslash($_GET['updated']) === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('AdSense settings saved.', 'poradnik-platform') . '</p></div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field('poradnik_adsense_settings_save');
        echo '<input type="hidden" name="poradnik_adsense_save" value="1" />';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row">' . esc_html__('Enable AdSense', 'poradnik-platform') . '</th><td><label><input type="checkbox" name="enabled" value="1" ' . checked(($settings['enabled'] ?? '0') === '1', true, false) . ' /> ' . esc_html__('Enabled', 'poradnik-platform') . '</label></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-adsense-client">' . esc_html__('AdSense Client ID', 'poradnik-platform') . '</label></th><td><input id="poradnik-adsense-client" type="text" name="client_id" class="regular-text" value="' . esc_attr((string) ($settings['client_id'] ?? '')) . '" placeholder="ca-pub-xxxxxxxxxxxxxxxx" /></td></tr>';

        foreach (AdsenseSlots::all() as $slot) {
            $key = 'slot_' . $slot;
            echo '<tr><th scope="row"><label for="poradnik-adsense-' . esc_attr($slot) . '">' . esc_html(sprintf('Slot ID (%s)', $slot)) . '</label></th><td><input id="poradnik-adsense-' . esc_attr($slot) . '" type="text" name="' . esc_attr($key) . '" class="regular-text" value="' . esc_attr((string) ($settings[$key] ?? '')) . '" /></td></tr>';
        }

        echo '<tr><th scope="row">' . esc_html__('Inline Auto Insert', 'poradnik-platform') . '</th><td><label><input type="checkbox" name="auto_insert_inline" value="1" ' . checked(($settings['auto_insert_inline'] ?? '0') === '1', true, false) . ' /> ' . esc_html__('Insert inline ad into content', 'poradnik-platform') . '</label></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-adsense-after-paragraph">' . esc_html__('Insert after paragraph', 'poradnik-platform') . '</label></th><td><input id="poradnik-adsense-after-paragraph" type="number" min="1" class="small-text" name="insert_after_paragraph" value="' . esc_attr((string) ($settings['insert_after_paragraph'] ?? 3)) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-adsense-auto-ads">' . esc_html__('Auto Ads Script (optional)', 'poradnik-platform') . '</label></th><td><textarea id="poradnik-adsense-auto-ads" name="auto_ads_code" rows="6" class="large-text code">' . esc_textarea((string) ($settings['auto_ads_code'] ?? '')) . '</textarea></td></tr>';
        echo '</table>';

        submit_button(__('Save AdSense Settings', 'poradnik-platform'));
        echo '</form>';
        echo '</div>';
    }
}
