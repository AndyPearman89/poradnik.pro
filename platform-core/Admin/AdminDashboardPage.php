<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Analytics\AnalyticsService;
use Poradnik\Platform\Domain\Dashboard\StatsService;

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

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'overview';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Dashboard.PRO – Admin Panel', 'poradnik-platform') . '</h1>';

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
        }
    }

    private static function renderOverviewTab(): void
    {
        $overview = StatsService::overview(0);
        $traffic = AnalyticsService::traffic();
        $revenue = AnalyticsService::revenue();

        $userCount = (int) (new \WP_User_Query(['count_total' => true, 'number' => 0]))->get_total();

        $stats = [
            __('Total Users', 'poradnik-platform')         => $userCount,
            __('Active Campaigns', 'poradnik-platform')     => $revenue['active_campaigns'],
            __('Total Campaigns', 'poradnik-platform')      => $overview['campaigns_total'],
            __('Total Impressions', 'poradnik-platform')    => $traffic['impressions'],
            __('Total Clicks', 'poradnik-platform')         => $traffic['clicks'],
            __('CTR (%)', 'poradnik-platform')              => $traffic['ctr'],
            __('Sponsored Revenue', 'poradnik-platform')    => $revenue['sponsored_revenue'] . ' ' . $revenue['currency'],
        ];

        echo '<h3>' . esc_html__('Platform Overview', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped" style="max-width:640px;">';
        echo '<tbody>';
        foreach ($stats as $label => $value) {
            echo '<tr><th>' . esc_html((string) $label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    private static function renderUsersTab(): void
    {
        $roles = [
            'administrator'       => __('Admin', 'poradnik-platform'),
            'poradnik_moderator'  => __('Moderator', 'poradnik-platform'),
            'poradnik_specialist' => __('Specialist', 'poradnik-platform'),
            'poradnik_advertiser' => __('Advertiser', 'poradnik-platform'),
            'subscriber'          => __('User', 'poradnik-platform'),
        ];

        echo '<h3>' . esc_html__('User Management', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped" style="max-width:480px;">';
        echo '<thead><tr><th>' . esc_html__('Role', 'poradnik-platform') . '</th><th>' . esc_html__('Count', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($roles as $roleKey => $label) {
            $query = new \WP_User_Query([
                'role'         => $roleKey,
                'count_total'  => true,
                'number'       => 0,
            ]);
            $count = $query->get_total();
            echo '<tr><td>' . esc_html($label) . '</td><td>' . esc_html((string) $count) . '</td></tr>';
        }

        echo '</tbody></table>';
        echo '<p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('users.php')) . '">' . esc_html__('Manage Users', 'poradnik-platform') . '</a></p>';
    }

    private static function renderContentTab(): void
    {
        $contentTypes = [
            'guide'      => __('Guides (Poradniki)', 'poradnik-platform'),
            'ranking'    => __('Rankings', 'poradnik-platform'),
            'review'     => __('Reviews', 'poradnik-platform'),
            'comparison' => __('Comparisons', 'poradnik-platform'),
            'news'       => __('News', 'poradnik-platform'),
            'tool'       => __('Tools', 'poradnik-platform'),
            'sponsored'  => __('Sponsored', 'poradnik-platform'),
        ];

        echo '<h3>' . esc_html__('Content Engine', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped" style="max-width:540px;">';
        echo '<thead><tr><th>' . esc_html__('Content Type', 'poradnik-platform') . '</th><th>' . esc_html__('Published', 'poradnik-platform') . '</th><th>' . esc_html__('Draft', 'poradnik-platform') . '</th><th></th></tr></thead><tbody>';

        foreach ($contentTypes as $cpt => $label) {
            $published = (int) wp_count_posts($cpt)->publish;
            $draft = (int) wp_count_posts($cpt)->draft;
            $editUrl = admin_url('edit.php?post_type=' . $cpt);
            $newUrl = admin_url('post-new.php?post_type=' . $cpt);

            echo '<tr>';
            echo '<td>' . esc_html($label) . '</td>';
            echo '<td>' . esc_html((string) $published) . '</td>';
            echo '<td>' . esc_html((string) $draft) . '</td>';
            echo '<td><a href="' . esc_url($editUrl) . '">' . esc_html__('View', 'poradnik-platform') . '</a> | <a href="' . esc_url($newUrl) . '">' . esc_html__('New', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderAdsTab(): void
    {
        $overview = StatsService::overview(0);

        echo '<h3>' . esc_html__('Ads Marketplace', 'poradnik-platform') . '</h3>';

        $stats = [
            __('Total Campaigns', 'poradnik-platform')  => $overview['campaigns_total'],
            __('Active Campaigns', 'poradnik-platform') => $overview['campaigns_active'],
            __('Impressions', 'poradnik-platform')       => $overview['impressions'],
            __('Clicks', 'poradnik-platform')            => $overview['clicks'],
            __('CTR (%)', 'poradnik-platform')           => $overview['ctr'],
        ];

        echo '<table class="widefat striped" style="max-width:480px;">';
        echo '<tbody>';
        foreach ($stats as $label => $value) {
            echo '<tr><th>' . esc_html((string) $label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('tools.php?page=poradnik-ads-campaigns')) . '">' . esc_html__('Manage Campaigns', 'poradnik-platform') . '</a></p>';
    }

    private static function renderSeoTab(): void
    {
        echo '<h3>' . esc_html__('SEO Engine', 'poradnik-platform') . '</h3>';

        $seoItems = [
            __('Keyword Tracking', 'poradnik-platform')  => __('Integrated via meta automation', 'poradnik-platform'),
            __('Internal Linking', 'poradnik-platform')  => __('Active – ContentEnhancer', 'poradnik-platform'),
            __('Schema Generator', 'poradnik-platform')  => __('Active – SchemaService', 'poradnik-platform'),
            __('Meta Generator', 'poradnik-platform')    => __('Active – MetaService', 'poradnik-platform'),
            __('Canonical Control', 'poradnik-platform') => __('Active – CanonicalService', 'poradnik-platform'),
            __('Breadcrumbs', 'poradnik-platform')       => __('Active – BreadcrumbService', 'poradnik-platform'),
            __('Programmatic SEO', 'poradnik-platform')  => __('Active – ProgrammaticGenerator', 'poradnik-platform'),
        ];

        echo '<table class="widefat striped" style="max-width:640px;">';
        echo '<tbody>';
        foreach ($seoItems as $feature => $status) {
            echo '<tr><th>' . esc_html((string) $feature) . '</th><td>' . esc_html((string) $status) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('tools.php?page=poradnik-programmatic-seo')) . '">' . esc_html__('Programmatic SEO', 'poradnik-platform') . '</a></p>';
    }

    private static function renderAnalyticsTab(): void
    {
        $traffic = AnalyticsService::traffic();
        $revenue = AnalyticsService::revenue();
        $conversion = AnalyticsService::conversion();
        $topPages = AnalyticsService::topPages();
        $topAffiliates = AnalyticsService::topAffiliates();

        echo '<h3>' . esc_html__('Analytics Engine', 'poradnik-platform') . '</h3>';

        $kpi = [
            __('Impressions', 'poradnik-platform')       => $traffic['impressions'],
            __('Clicks', 'poradnik-platform')            => $traffic['clicks'],
            __('CTR (%)', 'poradnik-platform')           => $traffic['ctr'],
            __('Sponsored Revenue', 'poradnik-platform') => $revenue['sponsored_revenue'] . ' ' . $revenue['currency'],
            __('Affiliate Clicks', 'poradnik-platform')  => $conversion['affiliate_clicks'],
            __('Affiliate Products', 'poradnik-platform')=> $conversion['affiliate_products'],
        ];

        echo '<table class="widefat striped" style="max-width:480px;">';
        echo '<tbody>';
        foreach ($kpi as $label => $value) {
            echo '<tr><th>' . esc_html((string) $label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h4 style="margin-top:20px;">' . esc_html__('Top Pages by Clicks', 'poradnik-platform') . '</h4>';
        if ($topPages === []) {
            echo '<p>' . esc_html__('No data available.', 'poradnik-platform') . '</p>';
        } else {
            echo '<table class="widefat striped" style="max-width:720px;">';
            echo '<thead><tr><th>' . esc_html__('Source URL', 'poradnik-platform') . '</th><th>' . esc_html__('Clicks', 'poradnik-platform') . '</th></tr></thead><tbody>';
            foreach ($topPages as $row) {
                echo '<tr><td>' . esc_html((string) ($row['source_url'] ?? '')) . '</td><td>' . esc_html((string) ($row['clicks'] ?? '0')) . '</td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '<h4 style="margin-top:20px;">' . esc_html__('Top Affiliates', 'poradnik-platform') . '</h4>';
        if ($topAffiliates === []) {
            echo '<p>' . esc_html__('No data available.', 'poradnik-platform') . '</p>';
        } else {
            echo '<table class="widefat striped" style="max-width:480px;">';
            echo '<thead><tr><th>' . esc_html__('Product', 'poradnik-platform') . '</th><th>' . esc_html__('Clicks', 'poradnik-platform') . '</th></tr></thead><tbody>';
            foreach ($topAffiliates as $row) {
                echo '<tr><td>' . esc_html((string) ($row['name'] ?? '')) . '</td><td>' . esc_html((string) ($row['clicks'] ?? '0')) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
    }

    private static function renderSettingsTab(): void
    {
        echo '<h3>' . esc_html__('Platform Settings', 'poradnik-platform') . '</h3>';

        $settingsLinks = [
            __('Module Flags', 'poradnik-platform')   => admin_url('tools.php?page=poradnik-platform-modules'),
            __('Stripe Settings', 'poradnik-platform') => admin_url('tools.php?page=poradnik-stripe-settings'),
        ];

        echo '<ul style="list-style:disc; padding-left:20px; max-width:480px;">';
        foreach ($settingsLinks as $label => $url) {
            echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        }
        echo '</ul>';

        echo '<h4 style="margin-top:20px;">' . esc_html__('SaaS Packages', 'poradnik-platform') . '</h4>';
        $packages = [
            'FREE'       => __('Basic access – free tier', 'poradnik-platform'),
            'PRO'        => __('Advanced features – paid tier', 'poradnik-platform'),
            'BUSINESS'   => __('Full feature set – business tier', 'poradnik-platform'),
            'ENTERPRISE' => __('Custom solutions – enterprise tier', 'poradnik-platform'),
        ];

        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<thead><tr><th>' . esc_html__('Package', 'poradnik-platform') . '</th><th>' . esc_html__('Description', 'poradnik-platform') . '</th></tr></thead><tbody>';
        foreach ($packages as $package => $description) {
            echo '<tr><td><strong>' . esc_html($package) . '</strong></td><td>' . esc_html($description) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
