<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Domain\Affiliate\ClickTracker;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class AffiliateClickController
{
    public static function registerRoutes(): void
    {
        register_rest_route(
            'poradnik/v1',
            '/affiliate/click',
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'handle'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'product_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'minimum'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'post_id' => [
                        'type'              => 'integer',
                        'minimum'           => 0,
                        'default'           => 0,
                        'sanitize_callback' => 'absint',
                    ],
                    'source' => [
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'referrer' => [
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'esc_url_raw',
                    ],
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function handle(WP_REST_Request $request)
    {
        $productId = absint($request->get_param('product_id'));
        $postId = absint($request->get_param('post_id'));
        $source = sanitize_text_field((string) $request->get_param('source'));
        $referrer = esc_url_raw((string) $request->get_param('referrer'));
        $userIp = self::resolveUserIp();

        $result = ClickTracker::track($productId, $postId, $source, $referrer, $userIp);

        if ($result instanceof WP_Error) {
            return $result;
        }

        return new WP_REST_Response(
            [
                'success' => true,
                'click_id' => $result,
            ],
            201
        );
    }

    private static function resolveUserIp(): string
    {
        if (! isset($_SERVER['REMOTE_ADDR'])) {
            return '';
        }

        $ip = (string) $_SERVER['REMOTE_ADDR'];

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '';
    }
}
