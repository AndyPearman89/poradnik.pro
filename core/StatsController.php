<?php

namespace PPAM\Core;

if (!defined('ABSPATH')) {
    exit;
}

class StatsController
{
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route('ppam/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [self::class, 'getStats'],
            'permission_callback' => [self::class, 'canManage'],
        ]);

        register_rest_route('ppam/v1', '/campaigns', [
            'methods' => 'GET',
            'callback' => [self::class, 'getCampaigns'],
            'permission_callback' => [self::class, 'canManage'],
            'args' => [
                'limit' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    public static function canManage(): bool
    {
        return current_user_can('manage_options');
    }

    public static function getStats(\WP_REST_Request $request): \WP_REST_Response
    {
        unset($request);

        return new \WP_REST_Response([
            'ok' => true,
            'data' => CampaignManager::getOverviewStats(),
        ], 200);
    }

    public static function getCampaigns(\WP_REST_Request $request): \WP_REST_Response
    {
        $limit = max(1, min(50, (int) $request->get_param('limit')));

        return new \WP_REST_Response([
            'ok' => true,
            'data' => CampaignManager::getRecentCampaigns($limit),
        ], 200);
    }
}
