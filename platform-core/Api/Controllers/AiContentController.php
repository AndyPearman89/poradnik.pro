<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Ai\ContentGenerator;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class AiContentController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/ai/content/generate', [
            'methods' => 'POST',
            'callback' => [self::class, 'generate'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function generate(WP_REST_Request $request): WP_REST_Response
    {
        $tool = sanitize_key((string) $request->get_param('tool'));
        $input = sanitize_text_field((string) $request->get_param('input'));
        $items = $request->get_param('items');

        $result = ContentGenerator::generate($tool, $input, [
            'items' => is_array($items) ? $items : [],
        ]);

        return new WP_REST_Response($result, 200);
    }
}
