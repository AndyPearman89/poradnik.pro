<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\ModuleRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class ModuleFlagsPage
{
    private const PAGE_SLUG = 'poradnik-platform-modules';
    private const OPTION_KEY = 'poradnik_platform_module_flags';
    private const ACTION_SAVE = 'save_module_flags';
    private const ACTION_RESET = 'reset_module_flags';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_init', [self::class, 'handleSave']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Platform Modules', 'poradnik-platform'),
            __('Poradnik Platform Modules', 'poradnik-platform'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleSave(): void
    {
        if (! is_admin()) {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        if (! isset($_POST['poradnik_platform_action'])) {
            return;
        }

        $action = sanitize_key((string) wp_unslash($_POST['poradnik_platform_action']));

        if ($action === self::ACTION_RESET) {
            check_admin_referer('poradnik_platform_reset_module_flags');

            delete_option(self::OPTION_KEY);

            $redirect = add_query_arg(
                [
                    'page' => self::PAGE_SLUG,
                    'reset' => '1',
                ],
                admin_url('tools.php')
            );

            wp_safe_redirect($redirect);
            exit;
        }

        if ($action !== self::ACTION_SAVE) {
            return;
        }

        check_admin_referer('poradnik_platform_save_module_flags');

        $modules = ModuleRegistry::discoverModules();
        $submitted = (isset($_POST['module_flags']) && is_array($_POST['module_flags']))
            ? wp_unslash($_POST['module_flags'])
            : [];

        $flags = [];

        foreach ($modules as $module) {
            $flags[$module] = isset($submitted[$module]) && $submitted[$module] === '1';
        }

        update_option(self::OPTION_KEY, $flags);

        $redirect = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                'updated' => '1',
            ],
            admin_url('tools.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $flags = ModuleRegistry::getFlags();
        $modules = ModuleRegistry::discoverModules();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Poradnik Platform Modules', 'poradnik-platform') . '</h1>';

        if (isset($_GET['updated']) && (string) wp_unslash($_GET['updated']) === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Module flags saved.', 'poradnik-platform') . '</p></div>';
        }

        if (isset($_GET['reset']) && (string) wp_unslash($_GET['reset']) === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Module flags reset to defaults.', 'poradnik-platform') . '</p></div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field('poradnik_platform_save_module_flags');
        echo '<input type="hidden" name="poradnik_platform_action" value="save_module_flags" />';

        echo '<table class="widefat striped" style="max-width: 720px;">';
        echo '<thead><tr><th>' . esc_html__('Module', 'poradnik-platform') . '</th><th>' . esc_html__('Enabled', 'poradnik-platform') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($modules as $module) {
            $checked = ! empty($flags[$module]);

            echo '<tr>';
            echo '<td><label for="module-' . esc_attr($module) . '"><strong>' . esc_html($module) . '</strong></label></td>';
            echo '<td><input id="module-' . esc_attr($module) . '" type="checkbox" name="module_flags[' . esc_attr($module) . ']" value="1" ' . checked($checked, true, false) . ' /></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        echo '<p class="submit">';
        submit_button(__('Save Module Flags', 'poradnik-platform'), 'primary', 'submit', false);
        echo '</p>';
        echo '</form>';

        echo '<form id="poradnik-platform-reset-form" method="post" action="" style="margin-top: 8px;">';
        wp_nonce_field('poradnik_platform_reset_module_flags');
        echo '<input type="hidden" name="poradnik_platform_action" value="' . esc_attr(self::ACTION_RESET) . '" />';
        submit_button(__('Reset to Defaults', 'poradnik-platform'), 'secondary', 'submit', false, ['onclick' => "return confirm('Reset all module flags to enabled defaults?');"]);
        echo '</form>';
        echo '</div>';
    }
}
