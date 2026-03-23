<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Core\ContentTypeMapper;
use Poradnik\Platform\Modules\AiImageGenerator\AiImageGeneratorService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class AiImageController
{
    public static function registerRoutes(): void
    {
        foreach (['poradnik/v1', 'peartree/v1'] as $namespace) {
            register_rest_route($namespace, '/ai/image/generate', [
                'methods'             => 'POST',
                'callback'            => [self::class, 'generate'],
                'permission_callback' => [self::class, 'canAccess'],
                'args'                => [
                    'title' => [
                        'required'          => true,
                        'type'              => 'string',
                        'minLength'         => 3,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'category' => [
                        'type'    => 'string',
                        'enum'    => ContentTypeMapper::apiAllowedAliases(),
                        'default' => 'poradnik',
                    ],
                ],
            ]);
        }
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function generate(WP_REST_Request $request): WP_REST_Response
    {
        $title = sanitize_text_field((string) $request->get_param('title'));
        $category = ContentTypeMapper::normalizePostType((string) $request->get_param('category'), 'guide');

        $result = AiImageGeneratorService::generateFromTitle($title, $category, false, 0);
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];

        return new WP_REST_Response(['items' => $items], 200);
    }
}
