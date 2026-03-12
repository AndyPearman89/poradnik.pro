<?php

namespace Poradnik\Platform\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class StripeSettingsPage
{
    private const PAGE_SLUG  = 'poradnik-stripe-settings';
    private const OPTION_KEY = 'poradnik_stripe_settings';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_init', [self::class, 'handleSave']);
    }

    public static function registerPage(): void
    {
        add_options_page(
            __('Stripe Settings – Poradnik Platform', 'poradnik-platform'),
            __('Stripe (Poradnik)', 'poradnik-platform'),
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

        if (empty($_POST['poradnik_stripe_save'])) {
            return;
        }

        check_admin_referer('poradnik_stripe_settings_save');

        $secretKey      = sanitize_text_field((string) ($_POST['stripe_secret_key'] ?? ''));
        $publishableKey = sanitize_text_field((string) ($_POST['stripe_publishable_key'] ?? ''));
        $webhookSecret  = sanitize_text_field((string) ($_POST['stripe_webhook_secret'] ?? ''));

        update_option('poradnik_stripe_secret_key', $secretKey);
        update_option('poradnik_stripe_publishable_key', $publishableKey);
        update_option('poradnik_stripe_webhook_secret', $webhookSecret);

        $redirect = add_query_arg(
            ['page' => self::PAGE_SLUG, 'updated' => '1'],
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

        $secretKey      = (string) get_option('poradnik_stripe_secret_key', '');
        $publishableKey = (string) get_option('poradnik_stripe_publishable_key', '');
        $webhookSecret  = (string) get_option('poradnik_stripe_webhook_secret', '');

        $webhookUrl = rest_url('poradnik/v1/stripe/webhook');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Stripe Settings – Poradnik Platform', 'poradnik-platform') . '</h1>';

        if (isset($_GET['updated']) && $_GET['updated'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__('Stripe settings saved.', 'poradnik-platform')
                . '</p></div>';
        }

        echo '<p>'
            . esc_html__('Webhook URL to register in your Stripe Dashboard:', 'poradnik-platform')
            . ' <code>' . esc_html($webhookUrl) . '</code></p>';

        echo '<form method="post" action="">';
        wp_nonce_field('poradnik_stripe_settings_save');
        echo '<input type="hidden" name="poradnik_stripe_save" value="1" />';

        echo '<table class="form-table" role="presentation">';

        self::renderField(
            'stripe_secret_key',
            __('Secret Key', 'poradnik-platform'),
            $secretKey,
            'sk_live_... or sk_test_...',
            true
        );

        self::renderField(
            'stripe_publishable_key',
            __('Publishable Key', 'poradnik-platform'),
            $publishableKey,
            'pk_live_... or pk_test_...',
            false
        );

        self::renderField(
            'stripe_webhook_secret',
            __('Webhook Secret', 'poradnik-platform'),
            $webhookSecret,
            'whsec_...',
            true
        );

        echo '</table>';
        submit_button(__('Save Stripe Settings', 'poradnik-platform'));
        echo '</form>';
        echo '</div>';
    }

    private static function renderField(
        string $name,
        string $label,
        string $value,
        string $placeholder,
        bool $masked
    ): void {
        $displayValue = ($masked && $value !== '') ? str_repeat('*', 12) . substr($value, -4) : esc_attr($value);
        $inputType    = $masked ? 'password' : 'text';

        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input type="' . esc_attr($inputType) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" '
            . 'value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '" />';

        if ($masked && $value !== '') {
            echo '<p class="description">'
                . esc_html__('Key is set. Leave blank to keep current value, or enter new key to replace.', 'poradnik-platform')
                . '</p>';
        }

        echo '</td>';
        echo '</tr>';
    }
}
