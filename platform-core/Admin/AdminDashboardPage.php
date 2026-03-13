<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin Dashboard – platform management panel.
 *
 * Sections: Overview | Users | Content | Ads | SEO | Analytics | Settings
 */
final class AdminDashboardPage
{
    private const PAGE_SLUG = 'peartree-admin-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_menu_page(
            __('PearTree Dashboard', 'poradnik-platform'),
            __('PearTree', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage'],
            'dashicons-chart-area',
            3
        );
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'overview';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('PearTree Admin Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab);

        echo '</div>';
    }

    private static function renderTabs(string $activeTab): void
    {
        $tabs = [
            'overview' => __('Overview', 'poradnik-platform'),
            'users' => __('Users', 'poradnik-platform'),
            'content' => __('Content', 'poradnik-platform'),
            'ads' => __('Ads', 'poradnik-platform'),
            'seo' => __('SEO', 'poradnik-platform'),
            'analytics' => __('Analytics', 'poradnik-platform'),
            'settings' => __('Settings', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('admin.php'));
            $class = $activeTab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($activeTab) {
            case 'users':
                self::renderUsersTab();
                break;
            case 'content':
                self::renderContentTab();
                break;
            case 'ads':
                self::renderAdsTab();
                break;
            case 'seo':
                self::renderSeoTab();
                break;
            case 'analytics':
                self::renderAnalyticsTab();
                break;
            case 'settings':
                self::renderSettingsTab();
                break;
            default:
                self::renderOverviewTab();
                break;
        }
    }

    private static function renderOverviewTab(): void
    {
        $userCount = count_users();
        $postCount = wp_count_posts('post');

        echo '<table class="widefat striped" style="max-width:600px;">';
        echo '<tbody>';
        echo '<tr><th>' . esc_html__('Total Users', 'poradnik-platform') . '</th><td>' . esc_html((string) ($userCount['total_users'] ?? 0)) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Published Posts', 'poradnik-platform') . '</th><td>' . esc_html((string) ($postCount->publish ?? 0)) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Platform', 'poradnik-platform') . '</th><td>PearTree Core / Dashboard.PRO</td></tr>';
        echo '</tbody></table>';
    }

    private static function renderUsersTab(): void
    {
        $users = get_users(['number' => 20, 'orderby' => 'registered', 'order' => 'DESC']);

        echo '<table class="widefat striped" style="max-width:960px;">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Username', 'poradnik-platform') . '</th><th>' . esc_html__('Email', 'poradnik-platform') . '</th><th>' . esc_html__('Role', 'poradnik-platform') . '</th><th>' . esc_html__('Registered', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $user->ID) . '</td>';
            echo '<td>' . esc_html($user->user_login) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html(implode(', ', (array) $user->roles)) . '</td>';
            echo '<td>' . esc_html($user->user_registered) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderContentTab(): void
    {
        $cptKeys = ['guide', 'ranking', 'review', 'comparison', 'news', 'tool', 'sponsored'];

        echo '<table class="widefat striped" style="max-width:600px;">';
        echo '<thead><tr><th>' . esc_html__('Content Type', 'poradnik-platform') . '</th><th>' . esc_html__('Published', 'poradnik-platform') . '</th><th>' . esc_html__('Draft', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($cptKeys as $cpt) {
            $counts = wp_count_posts($cpt);
            echo '<tr>';
            echo '<td>' . esc_html($cpt) . '</td>';
            echo '<td>' . esc_html((string) ($counts->publish ?? 0)) . '</td>';
            echo '<td>' . esc_html((string) ($counts->draft ?? 0)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderAdsTab(): void
    {
        echo '<p>' . esc_html__('Manage ad campaigns via the Ads Campaigns page in Tools menu.', 'poradnik-platform') . '</p>';
        $url = add_query_arg('page', 'poradnik-ads-campaigns', admin_url('tools.php'));
        echo '<a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Go to Ads Campaigns', 'poradnik-platform') . '</a>';
    }

    private static function renderSeoTab(): void
    {
        echo '<p>' . esc_html__('SEO Engine – programmatic SEO, schema automation, and meta management.', 'poradnik-platform') . '</p>';
        $url = add_query_arg('page', 'poradnik-programmatic-seo', admin_url('tools.php'));
        echo '<a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Go to Programmatic SEO', 'poradnik-platform') . '</a>';
    }

    private static function renderAnalyticsTab(): void
    {
        echo '<p>' . esc_html__('Analytics data is available via the REST API and third-party integrations.', 'poradnik-platform') . '</p>';
        echo '<table class="widefat striped" style="max-width:600px;">';
        echo '<tbody>';
        echo '<tr><th>' . esc_html__('API Endpoint', 'poradnik-platform') . '</th><td><code>/wp-json/peartree/v1/analytics</code></td></tr>';
        echo '</tbody></table>';
    }

    private static function renderSettingsTab(): void
    {
        echo '<p>' . esc_html__('Platform settings are managed via the Modules Flags panel.', 'poradnik-platform') . '</p>';
        $url = add_query_arg('page', 'poradnik-platform-modules', admin_url('tools.php'));
        echo '<a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Go to Module Settings', 'poradnik-platform') . '</a>';
    }
}
