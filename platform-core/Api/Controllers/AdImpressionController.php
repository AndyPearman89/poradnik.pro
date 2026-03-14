<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Domain\Ads\Tracker;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class AdImpressionController
{
    public static function registerRoutes(): void
    {
        register_rest_route(
            'poradnik/v1',
            '/ads/impression',
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'handle'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'campaign_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'minimum'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'slot_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'minimum'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'source' => [
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
    public static function handle(WP_REST_Request $request)
    {
        $campaignId = absint($request->get_param('campaign_id'));
        $slotId = absint($request->get_param('slot_id'));
        $source = sanitize_text_field((string) $request->get_param('source'));
        $userIp = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';

        $result = Tracker::trackImpression($campaignId, $slotId, $source, $userIp);
        if ($result instanceof WP_Error) {
            return $result;
        }

        return new WP_REST_Response(['success' => true, 'impression_id' => $result], 201);
    }
}
