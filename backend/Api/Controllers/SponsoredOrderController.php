<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Domain\Sponsored\Workflow;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class SponsoredOrderController
{
    public static function registerRoutes(): void
    {
        register_rest_route(
            'poradnik/v1',
            '/sponsored/orders',
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'handleCreate'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'title' => [
                        'required'          => true,
                        'type'              => 'string',
                        'minLength'         => 3,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'advertiser_email' => [
                        'required'          => true,
                        'type'              => 'string',
                        'format'            => 'email',
                        'sanitize_callback' => 'sanitize_email',
                    ],
                    'package_key' => [
                        'type'    => 'string',
                        'enum'    => ['basic', 'featured', 'homepage'],
                        'default' => 'basic',
                    ],
                    'amount' => [
                        'type'    => 'number',
                        'minimum' => 0,
                        'default' => 0,
                    ],
                    'currency' => [
                        'type'              => 'string',
                        'default'           => 'PLN',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'content' => [
                        'type'    => 'string',
                        'default' => '',
                    ],
                    'desired_publish_at' => [
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function handleCreate(WP_REST_Request $request)
    {
        $payload = [
            'advertiser_id' => absint($request->get_param('advertiser_id')),
            'advertiser_email' => sanitize_email((string) $request->get_param('advertiser_email')),
            'title' => sanitize_text_field((string) $request->get_param('title')),
            'content' => wp_kses_post((string) $request->get_param('content')),
            'package_key' => sanitize_key((string) $request->get_param('package_key')),
            'amount' => $request->get_param('amount'),
            'currency' => sanitize_text_field((string) $request->get_param('currency')),
            'desired_publish_at' => sanitize_text_field((string) $request->get_param('desired_publish_at')),
        ];

        if ($payload['title'] === '' || $payload['advertiser_email'] === '') {
            return new WP_Error('poradnik_invalid_sponsored_payload', 'title and advertiser_email are required.', ['status' => 400]);
        }

        $orderId = Workflow::submit($payload);
        if ($orderId < 1) {
            return new WP_Error('poradnik_sponsored_order_create_failed', 'Could not create sponsored order.', ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true, 'order_id' => $orderId], 201);
    }
}
