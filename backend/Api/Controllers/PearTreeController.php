<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Dashboard\AdminStats;
use Poradnik\Platform\Domain\Dashboard\StatsService;
use Poradnik\Platform\Domain\SaasPlans\PlanRepository;
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

        register_rest_route(self::NAMESPACE, '/plans', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'plans'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/users/(?P<user_id>\d+)/plan', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'userPlan'],
            'permission_callback' => [self::class, 'canAccessOwnData'],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canAccessAsPlatformUser();
    }

    public static function canAccessOwnData(): bool
    {
        return is_user_logged_in();
    }

    public static function dashboard(WP_REST_Request $request): WP_REST_Response
    {
        if (Capabilities::canManagePlatform()) {
            return new WP_REST_Response([
                'overview'  => AdminStats::overview(),
                'traffic'   => AdminStats::trafficSummary(),
                'by_role'   => AdminStats::usersByRole(),
            ], 200);
        }

        $advertiserId = get_current_user_id();

        return new WP_REST_Response([
            'overview'   => StatsService::overview($advertiserId),
            'statistics' => StatsService::statistics($advertiserId),
        ], 200);
    }

    public static function articles(WP_REST_Request $request): WP_REST_Response
    {
        $postType = sanitize_key((string) $request->get_param('type'));
        $status = sanitize_key((string) $request->get_param('status'));
        $perPage = max(1, min(100, absint($request->get_param('per_page') ?: 20)));
        $page = max(1, absint($request->get_param('page') ?: 1));

        $allowedTypes = ['post', 'guide', 'ranking', 'review'];
        $allowedStatuses = ['publish', 'pending', 'draft', 'any'];

        $args = [
            'post_type'      => in_array($postType, $allowedTypes, true) ? $postType : $allowedTypes,
            'post_status'    => in_array($status, $allowedStatuses, true) ? $status : 'publish',
            'posts_per_page' => $perPage,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if (! Capabilities::canManagePlatform()) {
            $args['author'] = get_current_user_id();
        }

        $query = new \WP_Query($args);
        $items = [];

        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $items[] = [
                'id'     => $post->ID,
                'title'  => $post->post_title,
                'type'   => $post->post_type,
                'status' => $post->post_status,
                'author' => get_the_author_meta('display_name', (int) $post->post_author),
                'date'   => $post->post_date,
                'url'    => get_permalink($post->ID),
            ];
        }

        return new WP_REST_Response([
            'items' => $items,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'page'  => $page,
        ], 200);
    }

    public static function ads(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = Capabilities::canManagePlatform()
            ? absint($request->get_param('advertiser_id'))
            : get_current_user_id();

        return new WP_REST_Response([
            'overview'   => StatsService::overview($advertiserId),
            'campaigns'  => StatsService::campaigns($advertiserId),
            'statistics' => StatsService::statistics($advertiserId),
        ], 200);
    }

    public static function analytics(WP_REST_Request $request): WP_REST_Response
    {
        $period = sanitize_key((string) $request->get_param('period'));
        if ($period === '') {
            $period = 'daily';
        }

        $advertiserId = Capabilities::canManagePlatform()
            ? absint($request->get_param('advertiser_id'))
            : get_current_user_id();

        return new WP_REST_Response([
            'traffic'    => AdminStats::trafficSummary(),
            'metrics'    => StatsService::statistics($advertiserId),
            'series'     => StatsService::series($advertiserId, $period),
            'payments'   => StatsService::payments($advertiserId),
        ], 200);
    }

    public static function plans(WP_REST_Request $request): WP_REST_Response
    {
        $plans = PlanRepository::all();
        $output = [];

        foreach ($plans as $key => $plan) {
            if (! is_array($plan)) {
                continue;
            }

            $output[$key] = [
                'key'      => $key,
                'label'    => $plan['label'] ?? strtoupper($key),
                'price'    => $plan['price'] ?? 0.0,
                'currency' => $plan['currency'] ?? 'PLN',
                'features' => $plan['features'] ?? [],
            ];
        }

        return new WP_REST_Response(['plans' => $output], 200);
    }

    public static function userPlan(WP_REST_Request $request): WP_REST_Response
    {
        $userId = absint($request->get_param('user_id'));

        if (! Capabilities::canManagePlatform() && $userId !== get_current_user_id()) {
            return new WP_REST_Response(['code' => 'rest_forbidden', 'message' => 'Access denied.'], 403);
        }

        $plan = PlanRepository::getUserPlan($userId);
        $planData = PlanRepository::find($plan);

        return new WP_REST_Response([
            'user_id'  => $userId,
            'plan'     => $plan,
            'label'    => is_array($planData) ? ($planData['label'] ?? strtoupper($plan)) : strtoupper($plan),
            'features' => is_array($planData) ? ($planData['features'] ?? []) : [],
        ], 200);
    }
}
