<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Review\ReviewService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class ReviewController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/review/summary', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'summary'],
            'permission_callback' => [self::class, 'canAccess'],
            'args'                => [
                'post_id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'minimum'           => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function summary(WP_REST_Request $request): WP_REST_Response
    {
        $postId = absint($request->get_param('post_id'));
        $data = ReviewService::fromPost($postId);

        return new WP_REST_Response($data, 200);
    }
}
