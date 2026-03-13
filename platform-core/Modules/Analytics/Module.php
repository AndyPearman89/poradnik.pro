<?php

namespace Poradnik\Platform\Modules\Analytics;

use Poradnik\Platform\Admin\AnalyticsDashboardPage;
use Poradnik\Platform\Domain\Analytics\AnalyticsService;
use Poradnik\Platform\Domain\Analytics\ReportGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            AnalyticsDashboardPage::init();
        }

        add_action('wp_head', [AnalyticsService::class, 'renderGa4Snippet'], 1);
        add_action('wp_head', [AnalyticsService::class, 'renderGa4EventScript'], 2);

        add_action('poradnik_weekly_report', [ReportGenerator::class, 'sendWeeklyEmail']);

        add_action('init', [self::class, 'scheduleWeeklyReport'], 5);
    }

    public static function scheduleWeeklyReport(): void
    {
        if (wp_next_scheduled('poradnik_weekly_report')) {
            return;
        }

        $nextMonday = strtotime('next monday 08:00:00');
        if ($nextMonday === false) {
            $nextMonday = time() + WEEK_IN_SECONDS;
        }

        wp_schedule_event($nextMonday, 'weekly', 'poradnik_weekly_report');
    }
}
