<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Dashboard\StatsService;

if (! defined('ABSPATH')) {
    exit;
}

final class AdvertiserDashboardPage
{
    private const PAGE_SLUG = 'poradnik-advertiser-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Advertiser Dashboard', 'poradnik-platform'),
            __('Advertiser Dashboard', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $advertiserId = isset($_GET['advertiser_id']) ? absint(wp_unslash($_GET['advertiser_id'])) : 0;
        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'overview';

        $overview = StatsService::overview($advertiserId);
        $campaigns = StatsService::campaigns($advertiserId);
        $statistics = StatsService::statistics($advertiserId);
        $payments = StatsService::payments($advertiserId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Advertiser Dashboard', 'poradnik-platform') . '</h1>';

        self::renderFilters($advertiserId, $tab);
        self::renderTabs($tab, $advertiserId, $overview, $campaigns, $statistics, $payments);

        echo '</div>';
    }

    private static function renderFilters(int $advertiserId, string $tab): void
    {
        echo '<form method="get" action="" style="margin: 16px 0;">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE_SLUG) . '" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />';
        echo '<label for="poradnik-dashboard-advertiser" style="margin-right: 8px;">Advertiser ID</label>';
        echo '<input id="poradnik-dashboard-advertiser" type="number" min="0" name="advertiser_id" value="' . esc_attr((string) $advertiserId) . '" />';
        submit_button(__('Apply', 'poradnik-platform'), 'secondary', 'submit', false, ['style' => 'margin-left:8px;']);
        echo '</form>';
    }

    /**
     * @param array<string, mixed> $overview
     * @param array<int, array<string, mixed>> $campaigns
     * @param array<string, mixed> $statistics
     * @param array<string, mixed> $payments
     */
    private static function renderTabs(string $tab, int $advertiserId, array $overview, array $campaigns, array $statistics, array $payments): void
    {
        $tabs = [
            'overview' => 'Overview',
            'campaigns' => 'Campaigns',
            'statistics' => 'Statistics',
            'payments' => 'Payments',
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key, 'advertiser_id' => $advertiserId], admin_url('tools.php'));
            $class = $tab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        if ($tab === 'campaigns') {
            self::renderCampaigns($campaigns);
            return;
        }

        if ($tab === 'statistics') {
            self::renderStatistics($statistics);
            return;
        }

        if ($tab === 'payments') {
            self::renderPayments($payments);
            return;
        }

        self::renderOverview($overview);
    }

    /**
     * @param array<string, mixed> $overview
     */
    private static function renderOverview(array $overview): void
    {
        echo '<table class="widefat striped" style="max-width:720px;">';
        echo '<tbody>';
        foreach ($overview as $key => $value) {
            echo '<tr><th>' . esc_html((string) $key) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    /**
     * @param array<int, array<string, mixed>> $campaigns
     */
    private static function renderCampaigns(array $campaigns): void
    {
        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Slot</th><th>Impressions</th><th>Clicks</th></tr></thead><tbody>';

        if ($campaigns === []) {
            echo '<tr><td colspan="6">No campaigns found.</td></tr>';
        }

        foreach ($campaigns as $row) {
            echo '<tr>';
            echo '<td>' . esc_html((string) ($row['id'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['name'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['status'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['slot_key'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['impressions'] ?? '0')) . '</td>';
            echo '<td>' . esc_html((string) ($row['clicks'] ?? '0')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * @param array<string, mixed> $statistics
     */
    private static function renderStatistics(array $statistics): void
    {
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<tbody>';
        foreach ($statistics as $key => $value) {
            echo '<tr><th>' . esc_html((string) $key) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    /**
     * @param array<string, mixed> $payments
     */
    private static function renderPayments(array $payments): void
    {
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<tbody>';
        foreach ($payments as $key => $value) {
            echo '<tr><th>' . esc_html((string) $key) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
