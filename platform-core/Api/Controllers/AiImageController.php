<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Ai\ImageGenerator;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class AiImageController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/ai/image/generate', [
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
        $title = sanitize_text_field((string) $request->get_param('title'));
        $category = sanitize_key((string) $request->get_param('category'));

        $result = ImageGenerator::generateFromTitle($title, $category === '' ? 'general' : $category);

        return new WP_REST_Response(['items' => $result], 200);
    }
}
