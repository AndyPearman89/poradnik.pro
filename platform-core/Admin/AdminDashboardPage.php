<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Dashboard\AdminStats;

if (! defined('ABSPATH')) {
    exit;
}

final class AdminDashboardPage
{
    private const PAGE_SLUG = 'poradnik-admin-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_menu_page(
            __('Dashboard.PRO', 'poradnik-platform'),
            __('Dashboard.PRO', 'poradnik-platform'),
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

        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'overview';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Dashboard.PRO — Admin Panel', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab);

        echo '</div>';
    }

    private static function renderTabs(string $tab): void
    {
        $tabs = [
            'overview'  => __('Overview', 'poradnik-platform'),
            'users'     => __('Users', 'poradnik-platform'),
            'content'   => __('Content', 'poradnik-platform'),
            'ads'       => __('Ads', 'poradnik-platform'),
            'seo'       => __('SEO', 'poradnik-platform'),
            'analytics' => __('Analytics', 'poradnik-platform'),
            'settings'  => __('Settings', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('admin.php'));
            $class = $tab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($tab) {
            case 'users':
                self::renderUsers();
                break;
            case 'content':
                self::renderContent();
                break;
            case 'ads':
                self::renderAds();
                break;
            case 'seo':
                self::renderSeo();
                break;
            case 'analytics':
                self::renderAnalytics();
                break;
            case 'settings':
                self::renderSettings();
                break;
            default:
                self::renderOverview();
        }
    }

    private static function renderOverview(): void
    {
        $stats = AdminStats::overview();
        $traffic = AdminStats::trafficSummary();

        echo '<h2>' . esc_html__('Platform Overview', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:720px;">';
        echo '<tbody>';

        $labels = [
            'users_total'       => __('Total Users', 'poradnik-platform'),
            'posts_total'       => __('Total Posts', 'poradnik-platform'),
            'guides_total'      => __('Total Guides', 'poradnik-platform'),
            'rankings_total'    => __('Total Rankings', 'poradnik-platform'),
            'reviews_total'     => __('Total Reviews', 'poradnik-platform'),
            'ads_active'        => __('Active Ad Campaigns', 'poradnik-platform'),
            'sponsored_pending' => __('Sponsored Articles Pending', 'poradnik-platform'),
            'comments_pending'  => __('Comments Awaiting Moderation', 'poradnik-platform'),
        ];

        foreach ($labels as $key => $label) {
            $value = $stats[$key] ?? 0;
            echo '<tr><th>' . esc_html($label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }

        echo '</tbody></table>';

        echo '<h2 style="margin-top:24px;">' . esc_html__('Traffic & Monetisation', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<tbody>';

        $trafficLabels = [
            'ad_impressions'   => __('Ad Impressions', 'poradnik-platform'),
            'ad_clicks'        => __('Ad Clicks', 'poradnik-platform'),
            'ad_ctr'           => __('Ad CTR (%)', 'poradnik-platform'),
            'affiliate_clicks' => __('Affiliate Clicks', 'poradnik-platform'),
        ];

        foreach ($trafficLabels as $key => $label) {
            $value = $traffic[$key] ?? 0;
            echo '<tr><th>' . esc_html($label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderUsers(): void
    {
        $byRole = AdminStats::usersByRole();

        echo '<h2>' . esc_html__('Users by Role', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<thead><tr><th>' . esc_html__('Role', 'poradnik-platform') . '</th><th>' . esc_html__('Count', 'poradnik-platform') . '</th></tr></thead>';
        echo '<tbody>';

        if ($byRole === []) {
            echo '<tr><td colspan="2">' . esc_html__('No users found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($byRole as $role => $count) {
            echo '<tr><td>' . esc_html($role) . '</td><td>' . esc_html((string) $count) . '</td></tr>';
        }

        echo '</tbody></table>';

        $manageUrl = admin_url('users.php');
        echo '<p><a href="' . esc_url($manageUrl) . '" class="button">' . esc_html__('Manage Users', 'poradnik-platform') . '</a></p>';
    }

    private static function renderContent(): void
    {
        $items = AdminStats::recentContent(20);

        echo '<h2>' . esc_html__('Recent Content', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('ID', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Title', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Type', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Status', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Author', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Date', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        if ($items === []) {
            echo '<tr><td colspan="6">' . esc_html__('No content found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($items as $item) {
            $editUrl = get_edit_post_link((int) ($item['id'] ?? 0));
            echo '<tr>';
            echo '<td>' . esc_html((string) ($item['id'] ?? '')) . '</td>';
            echo '<td><a href="' . esc_url((string) $editUrl) . '">' . esc_html((string) ($item['title'] ?? '')) . '</a></td>';
            echo '<td>' . esc_html((string) ($item['type'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($item['status'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($item['author'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($item['date'] ?? '')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderAds(): void
    {
        $adsUrl = add_query_arg('page', 'poradnik-advertiser-dashboard', admin_url('tools.php'));
        $campaignsUrl = add_query_arg('page', 'poradnik-ads-campaigns', admin_url('tools.php'));

        echo '<h2>' . esc_html__('Ads Management', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('Manage ad campaigns, slots, and sponsored articles.', 'poradnik-platform') . '</p>';
        echo '<p>';
        echo '<a href="' . esc_url($adsUrl) . '" class="button button-primary" style="margin-right:8px;">' . esc_html__('Advertiser Dashboard', 'poradnik-platform') . '</a>';
        echo '<a href="' . esc_url($campaignsUrl) . '" class="button">' . esc_html__('Campaigns', 'poradnik-platform') . '</a>';
        echo '</p>';

        echo '<h3>' . esc_html__('Ad Slots', 'poradnik-platform') . '</h3>';
        echo '<ul>';
        foreach (['homepage banner', 'sidebar banner', 'inline article ad', 'sponsored article'] as $slot) {
            echo '<li>' . esc_html($slot) . '</li>';
        }
        echo '</ul>';
    }

    private static function renderSeo(): void
    {
        $seoUrl = add_query_arg('page', 'poradnik-programmatic-seo', admin_url('tools.php'));

        echo '<h2>' . esc_html__('SEO Engine', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('Manage keyword tracking, internal linking, schema, and meta tags.', 'poradnik-platform') . '</p>';
        echo '<p><a href="' . esc_url($seoUrl) . '" class="button button-primary">' . esc_html__('Programmatic SEO', 'poradnik-platform') . '</a></p>';
        echo '<h3>' . esc_html__('SEO Engine Features', 'poradnik-platform') . '</h3>';
        echo '<ul>';
        foreach ([
            __('Keyword tracking', 'poradnik-platform'),
            __('Internal linking', 'poradnik-platform'),
            __('Schema generator', 'poradnik-platform'),
            __('Meta generator', 'poradnik-platform'),
        ] as $feature) {
            echo '<li>' . esc_html($feature) . '</li>';
        }
        echo '</ul>';
    }

    private static function renderAnalytics(): void
    {
        $traffic = AdminStats::trafficSummary();

        echo '<h2>' . esc_html__('Analytics Engine', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<tbody>';

        $labels = [
            'ad_impressions'   => __('Ad Impressions (total)', 'poradnik-platform'),
            'ad_clicks'        => __('Ad Clicks (total)', 'poradnik-platform'),
            'ad_ctr'           => __('Ad CTR % (total)', 'poradnik-platform'),
            'affiliate_clicks' => __('Affiliate Clicks (total)', 'poradnik-platform'),
        ];

        foreach ($labels as $key => $label) {
            $value = $traffic[$key] ?? 0;
            echo '<tr><th>' . esc_html($label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderSettings(): void
    {
        $settingsUrl = admin_url('options-general.php');
        $moduleUrl = add_query_arg('page', 'poradnik-module-flags', admin_url('tools.php'));
        $stripeUrl = add_query_arg('page', 'poradnik-stripe-settings', admin_url('options-general.php'));

        echo '<h2>' . esc_html__('Platform Settings', 'poradnik-platform') . '</h2>';
        echo '<p>';
        echo '<a href="' . esc_url($moduleUrl) . '" class="button" style="margin-right:8px;">' . esc_html__('Module Flags', 'poradnik-platform') . '</a>';
        echo '<a href="' . esc_url($stripeUrl) . '" class="button" style="margin-right:8px;">' . esc_html__('Stripe Settings', 'poradnik-platform') . '</a>';
        echo '<a href="' . esc_url($settingsUrl) . '" class="button">' . esc_html__('WordPress Settings', 'poradnik-platform') . '</a>';
        echo '</p>';
    }
}
