<?php

namespace Poradnik\Platform\Api\Controllers;

use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class HealthController
{
    public static function registerRoutes(): void
    {
        register_rest_route(
            'poradnik/v1',
            '/health',
            [
                'methods' => 'GET',
                'callback' => [self::class, 'handle'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public static function handle(WP_REST_Request $request): WP_REST_Response
    {
        unset($request);

        return new WP_REST_Response(
            [
                'status' => 'ok',
                'timestamp' => current_time('mysql', true),
            ],
            200
        );
    }
}
