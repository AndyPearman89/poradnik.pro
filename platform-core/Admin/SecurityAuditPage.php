<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Security\SecurityAuditLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class SecurityAuditPage
{
    private const PAGE_SLUG = 'poradnik-security-audit';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_poradnik_security_clear_log', [self::class, 'handleClearLog']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Security Audit', 'poradnik-platform'),
            __('Security Audit', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleClearLog(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to clear the security log.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_security_clear_log');

        SecurityAuditLogger::clearLog();
        SecurityAuditLogger::log('log_cleared', 'Security audit log cleared by admin.');

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'cleared' => '1'], admin_url('tools.php')));
        exit;
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        if (isset($_GET['cleared'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Security audit log cleared.', 'poradnik-platform') . '</p></div>';
        }

        $entries = SecurityAuditLogger::getEntries(100);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Security Audit Log', 'poradnik-platform') . '</h1>';
        echo '<p>' . esc_html__('This log records security-relevant events on the platform.', 'poradnik-platform') . '</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-bottom:16px;">';
        wp_nonce_field('poradnik_security_clear_log');
        echo '<input type="hidden" name="action" value="poradnik_security_clear_log" />';
        echo '<button type="submit" class="button button-secondary" onclick="return confirm(\'' . esc_js(__('Clear the entire security log? This cannot be undone.', 'poradnik-platform')) . '\')">';
        echo esc_html__('Clear Log', 'poradnik-platform');
        echo '</button>';
        echo '</form>';

        if ($entries === []) {
            echo '<p>' . esc_html__('No security events logged yet.', 'poradnik-platform') . '</p>';
        } else {
            echo '<table class="widefat striped" style="max-width:1200px;">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Time (UTC)', 'poradnik-platform') . '</th>';
            echo '<th>' . esc_html__('Event', 'poradnik-platform') . '</th>';
            echo '<th>' . esc_html__('Description', 'poradnik-platform') . '</th>';
            echo '<th>' . esc_html__('User ID', 'poradnik-platform') . '</th>';
            echo '<th>' . esc_html__('IP Address', 'poradnik-platform') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($entries as $entry) {
                echo '<tr>';
                echo '<td>' . esc_html((string) ($entry['time'] ?? '')) . '</td>';
                echo '<td><code>' . esc_html((string) ($entry['event'] ?? '')) . '</code></td>';
                echo '<td>' . esc_html((string) ($entry['description'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($entry['user_id'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($entry['ip'] ?? '')) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }
}
