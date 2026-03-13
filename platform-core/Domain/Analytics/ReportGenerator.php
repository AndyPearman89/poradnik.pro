<?php

namespace Poradnik\Platform\Domain\Analytics;

if (! defined('ABSPATH')) {
    exit;
}

final class ReportGenerator
{
    /**
     * @return array<string, mixed>
     */
    public static function weekly(): array
    {
        global $wpdb;

        $since = gmdate('Y-m-d H:i:s', strtotime('-7 days'));

        $affiliateClicks = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_affiliate_clicks WHERE created_at >= %s",
                $since
            )
        );

        $adClicks = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_ad_clicks WHERE created_at >= %s",
                $since
            )
        );

        $adImpressions = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_ad_impressions WHERE created_at >= %s",
                $since
            )
        );

        $sponsoredRevenue = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}poradnik_sponsored_articles WHERE payment_status = %s AND created_at >= %s",
                'paid',
                $since
            )
        );

        $ctr = $adImpressions > 0 ? round(($adClicks / $adImpressions) * 100, 2) : 0.0;

        return [
            'period' => 'weekly',
            'since' => $since,
            'affiliate_clicks' => $affiliateClicks,
            'ad_clicks' => $adClicks,
            'ad_impressions' => $adImpressions,
            'ad_ctr_percent' => $ctr,
            'sponsored_revenue_pln' => $sponsoredRevenue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function monthly(): array
    {
        global $wpdb;

        $since = gmdate('Y-m-d H:i:s', strtotime('-30 days'));

        $affiliateClicks = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_affiliate_clicks WHERE created_at >= %s",
                $since
            )
        );

        $adClicks = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_ad_clicks WHERE created_at >= %s",
                $since
            )
        );

        $adImpressions = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_ad_impressions WHERE created_at >= %s",
                $since
            )
        );

        $sponsoredRevenue = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}poradnik_sponsored_articles WHERE payment_status = %s AND created_at >= %s",
                'paid',
                $since
            )
        );

        $ctr = $adImpressions > 0 ? round(($adClicks / $adImpressions) * 100, 2) : 0.0;

        return [
            'period' => 'monthly',
            'since' => $since,
            'affiliate_clicks' => $affiliateClicks,
            'ad_clicks' => $adClicks,
            'ad_impressions' => $adImpressions,
            'ad_ctr_percent' => $ctr,
            'sponsored_revenue_pln' => $sponsoredRevenue,
        ];
    }

    public static function sendWeeklyEmail(): void
    {
        $settings = AnalyticsService::getSettings();
        $email = sanitize_email((string) ($settings['reports_email'] ?? ''));

        if ($email === '') {
            return;
        }

        $report = self::weekly();

        $subject = sprintf(
            '[Poradnik.PRO] Raport tygodniowy – %s',
            gmdate('d.m.Y')
        );

        $body = "Raport tygodniowy platformy Poradnik.PRO\n\n";
        $body .= "Okres: ostatnie 7 dni (od {$report['since']})\n\n";
        $body .= "Kliknięcia afiliacyjne: {$report['affiliate_clicks']}\n";
        $body .= "Kliknięcia reklam: {$report['ad_clicks']}\n";
        $body .= "Wyświetlenia reklam: {$report['ad_impressions']}\n";
        $body .= "CTR reklam: {$report['ad_ctr_percent']}%\n";
        $body .= "Przychód ze sponsorowanych: {$report['sponsored_revenue_pln']} PLN\n";

        $sent = wp_mail($email, $subject, $body);

        if (! $sent) {
            error_log(sprintf('Poradnik Platform: Weekly report email failed to send to %s', $email));
        }
    }
}
