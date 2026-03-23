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
        foreach (['poradnik/v1', 'peartree/v1'] as $namespace) {
            register_rest_route($namespace, '/ai/content/generate', [
                'methods'             => 'POST',
                'callback'            => [self::class, 'generate'],
                'permission_callback' => [self::class, 'canAccess'],
                'args'                => [
                    'tool' => [
                        'required' => true,
                        'type'     => 'string',
                        'enum'     => ContentGenerator::tools(),
                    ],
                    'input' => [
                        'required'          => true,
                        'type'              => 'string',
                        'minLength'         => 1,
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'items' => [
                        'type'    => 'array',
                        'default' => [],
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
        $tool = sanitize_key((string) $request->get_param('tool'));
        $input = sanitize_text_field((string) $request->get_param('input'));
        $items = $request->get_param('items');

        $result = ContentGenerator::generate($tool, $input, [
            'items' => is_array($items) ? $items : [],
        ]);

        return new WP_REST_Response($result, 200);
    }
}
