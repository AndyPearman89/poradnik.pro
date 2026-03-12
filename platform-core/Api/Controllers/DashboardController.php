<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Dashboard\StatsService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class DashboardController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/dashboard/overview', [
            'methods' => 'GET',
            'callback' => [self::class, 'overview'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/dashboard/campaigns', [
            'methods' => 'GET',
            'callback' => [self::class, 'campaigns'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/dashboard/statistics', [
            'methods' => 'GET',
            'callback' => [self::class, 'statistics'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/dashboard/payments', [
            'methods' => 'GET',
            'callback' => [self::class, 'payments'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function overview(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        return new WP_REST_Response(StatsService::overview($advertiserId), 200);
    }

    public static function campaigns(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        return new WP_REST_Response(['items' => StatsService::campaigns($advertiserId)], 200);
    }

    public static function statistics(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        return new WP_REST_Response(StatsService::statistics($advertiserId), 200);
    }

    public static function payments(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = absint($request->get_param('advertiser_id'));

        return new WP_REST_Response(StatsService::payments($advertiserId), 200);
    }
}
