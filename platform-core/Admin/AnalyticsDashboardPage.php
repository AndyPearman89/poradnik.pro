<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Analytics\AnalyticsService;
use Poradnik\Platform\Domain\Analytics\ReportGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class AnalyticsDashboardPage
{
    private const PAGE_SLUG = 'poradnik-analytics';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_poradnik_analytics_save', [self::class, 'handleSave']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Analytics Dashboard', 'poradnik-platform'),
            __('Analytics Dashboard', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleSave(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to save analytics settings.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_analytics_save');

        AnalyticsService::saveSettings([
            'ga4_measurement_id' => isset($_POST['ga4_measurement_id']) ? sanitize_text_field((string) wp_unslash($_POST['ga4_measurement_id'])) : '',
            'gsc_site_url' => isset($_POST['gsc_site_url']) ? esc_url_raw((string) wp_unslash($_POST['gsc_site_url'])) : '',
            'track_affiliate_clicks' => isset($_POST['track_affiliate_clicks']),
            'track_ad_clicks' => isset($_POST['track_ad_clicks']),
            'track_ad_impressions' => isset($_POST['track_ad_impressions']),
            'reports_email' => isset($_POST['reports_email']) ? sanitize_email((string) wp_unslash($_POST['reports_email'])) : '',
        ]);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'updated' => '1'], admin_url('tools.php')));
        exit;
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $settings = AnalyticsService::getSettings();
        $kpi = AnalyticsService::getKpiSummary();
        $weekly = ReportGenerator::weekly();
        $monthly = ReportGenerator::monthly();

        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'overview';

        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Analytics settings saved.', 'poradnik-platform') . '</p></div>';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Analytics Dashboard', 'poradnik-platform') . '</h1>';

        $baseUrl = add_query_arg(['page' => self::PAGE_SLUG], admin_url('tools.php'));
        echo '<nav class="nav-tab-wrapper">';
        foreach (['overview' => __('Overview', 'poradnik-platform'), 'weekly' => __('Weekly Report', 'poradnik-platform'), 'monthly' => __('Monthly Report', 'poradnik-platform'), 'settings' => __('Settings', 'poradnik-platform')] as $tabKey => $tabLabel) {
            $active = ($tab === $tabKey) ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url(add_query_arg('tab', $tabKey, $baseUrl)) . '" class="nav-tab' . $active . '">' . esc_html($tabLabel) . '</a>';
        }
        echo '</nav>';

        echo '<div style="margin-top: 24px;">';

        if ($tab === 'settings') {
            self::renderSettings($settings);
        } elseif ($tab === 'weekly') {
            self::renderReport($weekly);
        } elseif ($tab === 'monthly') {
            self::renderReport($monthly);
        } else {
            self::renderOverview($kpi, $settings);
        }

        echo '</div></div>';
    }

    /**
     * @param array<string, int|float> $kpi
     * @param array<string, mixed> $settings
     */
    private static function renderOverview(array $kpi, array $settings): void
    {
        $gaId = (string) ($settings['ga4_measurement_id'] ?? '');
        $gscUrl = (string) ($settings['gsc_site_url'] ?? '');

        echo '<h2>' . esc_html__('Platform KPI Summary', 'poradnik-platform') . '</h2>';

        if ($gaId === '') {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('Google Analytics 4 Measurement ID is not configured. Go to Settings tab.', 'poradnik-platform') . '</p></div>';
        }

        echo '<table class="widefat striped" style="max-width:720px;">';
        echo '<thead><tr><th>' . esc_html__('Metric', 'poradnik-platform') . '</th><th>' . esc_html__('Value', 'poradnik-platform') . '</th></tr></thead><tbody>';

        $labels = [
            'affiliate_clicks' => __('Total Affiliate Clicks', 'poradnik-platform'),
            'ad_clicks' => __('Total Ad Clicks', 'poradnik-platform'),
            'ad_impressions' => __('Total Ad Impressions', 'poradnik-platform'),
            'ad_ctr_percent' => __('Ad CTR (%)', 'poradnik-platform'),
            'active_campaigns' => __('Active Campaigns', 'poradnik-platform'),
            'total_revenue_pln' => __('Total Sponsored Revenue (PLN)', 'poradnik-platform'),
        ];

        foreach ($labels as $key => $label) {
            $value = $kpi[$key] ?? 0;
            $display = ($key === 'ad_ctr_percent') ? number_format((float) $value, 2) . '%' : number_format((float) $value, ($key === 'total_revenue_pln') ? 2 : 0);
            echo '<tr><th>' . esc_html($label) . '</th><td><strong>' . esc_html($display) . '</strong></td></tr>';
        }

        echo '</tbody></table>';

        if ($gaId !== '') {
            echo '<p style="margin-top:16px;"><strong>' . esc_html__('GA4 Measurement ID:', 'poradnik-platform') . '</strong> <code>' . esc_html($gaId) . '</code></p>';
        }

        if ($gscUrl !== '') {
            echo '<p><strong>' . esc_html__('Google Search Console URL:', 'poradnik-platform') . '</strong> <code>' . esc_html($gscUrl) . '</code></p>';
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function renderReport(array $report): void
    {
        $period = ($report['period'] ?? 'weekly') === 'monthly' ? __('Monthly', 'poradnik-platform') : __('Weekly', 'poradnik-platform');

        echo '<h2>' . esc_html($period) . ' ' . esc_html__('Report', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('Period from:', 'poradnik-platform') . ' <strong>' . esc_html((string) ($report['since'] ?? '')) . '</strong></p>';

        echo '<table class="widefat striped" style="max-width:600px;">';
        echo '<thead><tr><th>' . esc_html__('Metric', 'poradnik-platform') . '</th><th>' . esc_html__('Value', 'poradnik-platform') . '</th></tr></thead><tbody>';

        $labels = [
            'affiliate_clicks' => __('Affiliate Clicks', 'poradnik-platform'),
            'ad_clicks' => __('Ad Clicks', 'poradnik-platform'),
            'ad_impressions' => __('Ad Impressions', 'poradnik-platform'),
            'ad_ctr_percent' => __('Ad CTR (%)', 'poradnik-platform'),
            'sponsored_revenue_pln' => __('Sponsored Revenue (PLN)', 'poradnik-platform'),
        ];

        foreach ($labels as $key => $label) {
            $value = $report[$key] ?? 0;
            $display = ($key === 'ad_ctr_percent') ? number_format((float) $value, 2) . '%' : number_format((float) $value, ($key === 'sponsored_revenue_pln') ? 2 : 0);
            echo '<tr><th>' . esc_html($label) . '</th><td>' . esc_html($display) . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function renderSettings(array $settings): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="max-width:720px;">';
        wp_nonce_field('poradnik_analytics_save');
        echo '<input type="hidden" name="action" value="poradnik_analytics_save" />';

        echo '<table class="form-table" role="presentation">';

        echo '<tr><th scope="row"><label for="pa-ga4-id">' . esc_html__('GA4 Measurement ID', 'poradnik-platform') . '</label></th><td>';
        echo '<input type="text" id="pa-ga4-id" name="ga4_measurement_id" class="regular-text" value="' . esc_attr((string) ($settings['ga4_measurement_id'] ?? '')) . '" placeholder="G-XXXXXXXXXX" />';
        echo '<p class="description">' . esc_html__('Enter your Google Analytics 4 Measurement ID (e.g. G-XXXXXXXXXX).', 'poradnik-platform') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="pa-gsc-url">' . esc_html__('Google Search Console URL', 'poradnik-platform') . '</label></th><td>';
        echo '<input type="url" id="pa-gsc-url" name="gsc_site_url" class="regular-text" value="' . esc_attr((string) ($settings['gsc_site_url'] ?? '')) . '" placeholder="https://poradnik.pro" />';
        echo '<p class="description">' . esc_html__('Your verified site URL in Google Search Console.', 'poradnik-platform') . '</p></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Track Events', 'poradnik-platform') . '</th><td>';
        $checks = [
            'track_affiliate_clicks' => __('Affiliate clicks', 'poradnik-platform'),
            'track_ad_clicks' => __('Ad clicks', 'poradnik-platform'),
            'track_ad_impressions' => __('Ad impressions', 'poradnik-platform'),
        ];
        foreach ($checks as $key => $label) {
            $checked = (bool) ($settings[$key] ?? true) ? ' checked' : '';
            echo '<label style="display:block;margin-bottom:8px;"><input type="checkbox" name="' . esc_attr($key) . '" value="1"' . $checked . '> ' . esc_html($label) . '</label>';
        }
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="pa-email">' . esc_html__('Weekly Reports Email', 'poradnik-platform') . '</label></th><td>';
        echo '<input type="email" id="pa-email" name="reports_email" class="regular-text" value="' . esc_attr((string) ($settings['reports_email'] ?? '')) . '" placeholder="admin@poradnik.pro" />';
        echo '<p class="description">' . esc_html__('Email address for automated weekly performance reports.', 'poradnik-platform') . '</p></td></tr>';

        echo '</table>';
        submit_button(__('Save Settings', 'poradnik-platform'));
        echo '</form>';
    }
}
