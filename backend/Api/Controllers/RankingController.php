<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Ranking\ScoringService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class RankingController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/ranking/score', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'score'],
            'permission_callback' => [self::class, 'canAccess'],
            'args'                => [
                'quality'  => ['type' => 'number', 'minimum' => 0, 'maximum' => 10, 'default' => 5],
                'price'    => ['type' => 'number', 'minimum' => 0, 'maximum' => 10, 'default' => 5],
                'features' => ['type' => 'number', 'minimum' => 0, 'maximum' => 10, 'default' => 5],
                'support'  => ['type' => 'number', 'minimum' => 0, 'maximum' => 10, 'default' => 5],
            ],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function score(WP_REST_Request $request): WP_REST_Response
    {
        $metrics = [
            'quality' => (float) $request->get_param('quality'),
            'price' => (float) $request->get_param('price'),
            'features' => (float) $request->get_param('features'),
            'support' => (float) $request->get_param('support'),
        ];

        $score = ScoringService::score($metrics);

        return new WP_REST_Response(['score' => $score], 200);
    }
}
