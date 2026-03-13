<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Analytics\AnalyticsService;
use Poradnik\Platform\Domain\Dashboard\StatsService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class PearTreeController
{
    private const NAMESPACE = 'peartree/v1';

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/dashboard', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'dashboard'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route(self::NAMESPACE, '/articles', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'articles'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route(self::NAMESPACE, '/ads', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'ads'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route(self::NAMESPACE, '/analytics', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'analytics'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function dashboard(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        $overview = StatsService::overview($advertiserId);
        $traffic = AnalyticsService::traffic();
        $revenue = AnalyticsService::revenue();

        return new WP_REST_Response([
            'platform'    => 'dashboard.pro',
            'engine'      => 'PearTree Core',
            'version'     => '1.0.0',
            'overview'    => $overview,
            'traffic'     => $traffic,
            'revenue'     => $revenue,
            'modules'     => [
                'dashboard'    => true,
                'content'      => true,
                'ai'           => true,
                'ads'          => true,
                'seo'          => true,
                'analytics'    => true,
                'affiliate'    => true,
                'users'        => true,
            ],
        ], 200);
    }

    public static function articles(WP_REST_Request $request): WP_REST_Response
    {
        $postType = sanitize_key((string) ($request->get_param('type') ?? ''));
        $status = sanitize_key((string) ($request->get_param('status') ?? 'publish'));
        $perPage = absint($request->get_param('per_page') ?? 20);
        $page = absint($request->get_param('page') ?? 1);

        $allowedTypes = ['guide', 'ranking', 'review', 'news', 'tool', 'sponsored', 'comparison'];
        $allowedStatuses = ['publish', 'draft', 'pending', 'any'];

        if ($postType !== '' && ! in_array($postType, $allowedTypes, true)) {
            $postType = '';
        }

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'publish';
        }

        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);

        $query = new \WP_Query([
            'post_type'      => $postType !== '' ? $postType : $allowedTypes,
            'post_status'    => $status,
            'posts_per_page' => $perPage,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $items = [];
        foreach ($query->posts as $post) {
            $items[] = [
                'id'         => $post->ID,
                'title'      => $post->post_title,
                'type'       => $post->post_type,
                'status'     => $post->post_status,
                'date'       => $post->post_date,
                'author_id'  => $post->post_author,
                'permalink'  => get_permalink($post->ID),
            ];
        }

        return new WP_REST_Response([
            'items'       => $items,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $page,
            'per_page'    => $perPage,
        ], 200);
    }

    public static function ads(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        $campaigns = StatsService::campaigns($advertiserId);
        $statistics = StatsService::statistics($advertiserId);
        $overview = StatsService::overview($advertiserId);

        return new WP_REST_Response([
            'campaigns'  => $campaigns,
            'statistics' => $statistics,
            'overview'   => $overview,
        ], 200);
    }

    public static function analytics(WP_REST_Request $request): WP_REST_Response
    {
        $traffic = AnalyticsService::traffic();
        $revenue = AnalyticsService::revenue();
        $conversion = AnalyticsService::conversion();
        $topPages = AnalyticsService::topPages();
        $topAffiliates = AnalyticsService::topAffiliates();

        return new WP_REST_Response([
            'traffic'        => $traffic,
            'revenue'        => $revenue,
            'conversion'     => $conversion,
            'top_pages'      => $topPages,
            'top_affiliates' => $topAffiliates,
        ], 200);
    }
}
