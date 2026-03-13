<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Core\ModuleRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class PlatformAdminPanel
{
    public const MENU_SLUG = 'poradnik-platform';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerMenu']);
    }

    public static function registerMenu(): void
    {
        add_menu_page(
            __('Poradnik Platform', 'poradnik-platform'),
            __('Poradnik Platform', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::MENU_SLUG,
            [self::class, 'renderDashboard'],
            'dashicons-chart-area',
            3
        );
    }

    public static function renderDashboard(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $modules  = ModuleRegistry::discoverModules();
        $flags    = ModuleRegistry::getFlags();
        $dbVersion = (string) get_option('poradnik_platform_db_version', '—');

        $enabled  = array_filter($flags, static fn (bool $v): bool => $v);
        $disabled = array_filter($flags, static fn (bool $v): bool => ! $v);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Poradnik Platform – Dashboard', 'poradnik-platform') . '</h1>';
        echo '<p class="description">' . esc_html__('Central administration panel for the Poradnik Platform.', 'poradnik-platform') . '</p>';

        echo '<div style="display:flex;gap:24px;flex-wrap:wrap;margin:24px 0;">';

        self::renderStatCard(
            __('Modules Total', 'poradnik-platform'),
            (string) count($modules),
            '#6b4eff'
        );

        self::renderStatCard(
            __('Modules Active', 'poradnik-platform'),
            (string) count($enabled),
            '#28a745'
        );

        self::renderStatCard(
            __('Modules Disabled', 'poradnik-platform'),
            (string) count($disabled),
            '#dc3545'
        );

        self::renderStatCard(
            __('DB Schema Version', 'poradnik-platform'),
            esc_html($dbVersion),
            '#17a2b8'
        );

        echo '</div>';

        echo '<h2>' . esc_html__('Module Status', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:640px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Module', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Status', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($modules as $module) {
            $isEnabled = ! empty($flags[$module]);
            $badge = $isEnabled
                ? '<span style="color:#28a745;font-weight:600;">&#10003; ' . esc_html__('Enabled', 'poradnik-platform') . '</span>'
                : '<span style="color:#dc3545;font-weight:600;">&#10007; ' . esc_html__('Disabled', 'poradnik-platform') . '</span>';

            echo '<tr>';
            echo '<td><strong>' . esc_html($module) . '</strong></td>';
            echo '<td>' . $badge . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge is constructed from escaped values
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<p style="margin-top:24px;">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=poradnik-platform-modules')) . '" class="button button-secondary">'
            . esc_html__('Manage Module Flags', 'poradnik-platform') . '</a>';
        echo ' <a href="' . esc_url(admin_url('options-general.php?page=poradnik-stripe-settings')) . '" class="button button-secondary">'
            . esc_html__('Stripe Settings', 'poradnik-platform') . '</a>';
        echo '</p>';

        echo '</div>';
    }

    private static function renderStatCard(string $label, string $value, string $color): void
    {
        echo '<div style="background:#fff;border-left:4px solid ' . esc_attr($color) . ';padding:16px 24px;min-width:160px;box-shadow:0 1px 3px rgba(0,0,0,.1);border-radius:4px;">';
        echo '<div style="font-size:28px;font-weight:700;color:' . esc_attr($color) . ';">' . esc_html($value) . '</div>';
        echo '<div style="font-size:13px;color:#555;margin-top:4px;">' . esc_html($label) . '</div>';
        echo '</div>';
    }
}
