<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Dashboard\StatsService;
use Poradnik\Platform\Domain\Saas\PackageService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * PearTree REST API – Dashboard.PRO endpoints.
 *
 * Namespace: peartree/v1
 *
 * Routes:
 *  GET /peartree/v1/dashboard
 *  GET /peartree/v1/articles
 *  GET /peartree/v1/ads
 *  GET /peartree/v1/analytics
 */
final class PearTreeDashboardController
{
    private const NAMESPACE = 'peartree/v1';

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/dashboard', [
            'methods' => 'GET',
            'callback' => [self::class, 'dashboard'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route(self::NAMESPACE, '/articles', [
            'methods' => 'GET',
            'callback' => [self::class, 'articles'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route(self::NAMESPACE, '/ads', [
            'methods' => 'GET',
            'callback' => [self::class, 'ads'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route(self::NAMESPACE, '/analytics', [
            'methods' => 'GET',
            'callback' => [self::class, 'analytics'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    /**
     * GET /peartree/v1/dashboard
     *
     * Returns aggregated dashboard overview for the current platform.
     */
    public static function dashboard(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));
        $userId = get_current_user_id();

        $overview = StatsService::overview($advertiserId);
        $userCount = count_users();
        $package = PackageService::getUserPackage($userId);

        $data = array_merge($overview, [
            'total_users' => $userCount['total_users'] ?? 0,
            'user_package' => $package,
            'platform' => 'Dashboard.PRO / PearTree Core',
        ]);

        return new WP_REST_Response($data, 200);
    }

    /**
     * GET /peartree/v1/articles
     *
     * Returns a summary of published content by type.
     */
    public static function articles(WP_REST_Request $request): WP_REST_Response
    {
        $cptTypes = ['guide', 'ranking', 'review', 'comparison', 'news', 'tool', 'sponsored'];
        $summary = [];

        foreach ($cptTypes as $cpt) {
            $counts = wp_count_posts($cpt);
            $summary[$cpt] = [
                'publish' => (int) ($counts->publish ?? 0),
                'draft' => (int) ($counts->draft ?? 0),
                'pending' => (int) ($counts->pending ?? 0),
            ];
        }

        return new WP_REST_Response(['articles' => $summary], 200);
    }

    /**
     * GET /peartree/v1/ads
     *
     * Returns aggregated ads marketplace data.
     */
    public static function ads(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        $campaigns = StatsService::campaigns($advertiserId);
        $statistics = StatsService::statistics($advertiserId);

        return new WP_REST_Response([
            'campaigns_total' => count($campaigns),
            'campaigns_active' => count(array_filter($campaigns, static fn (array $row): bool => (string) ($row['status'] ?? '') === 'active')),
            'impressions' => $statistics['impressions'],
            'clicks' => $statistics['clicks'],
            'ctr' => $statistics['ctr'],
        ], 200);
    }

    /**
     * GET /peartree/v1/analytics
     *
     * Returns platform analytics aggregates.
     */
    public static function analytics(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        $statistics = StatsService::statistics($advertiserId);
        $payments = StatsService::payments($advertiserId);

        $cptTypes = ['guide', 'ranking', 'review', 'comparison', 'news', 'tool'];
        $totalPublished = 0;
        foreach ($cptTypes as $cpt) {
            $counts = wp_count_posts($cpt);
            $totalPublished += (int) ($counts->publish ?? 0);
        }

        return new WP_REST_Response([
            'impressions' => $statistics['impressions'],
            'clicks' => $statistics['clicks'],
            'ctr' => $statistics['ctr'],
            'revenue_total' => $payments['total_amount'],
            'revenue_paid' => $payments['paid_total'],
            'currency' => $payments['currency'],
            'total_published_articles' => $totalPublished,
        ], 200);
    }
}
