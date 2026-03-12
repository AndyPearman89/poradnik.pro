<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Seo\ProgrammaticGenerator;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class ProgrammaticBuildController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/seo/programmatic/build', [
            'methods' => 'POST',
            'callback' => [self::class, 'build'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function build(WP_REST_Request $request): WP_REST_Response
    {
        $template = sanitize_key((string) $request->get_param('template'));
        $topic = sanitize_text_field((string) $request->get_param('topic'));
        $count = absint($request->get_param('count'));
        $postType = sanitize_key((string) $request->get_param('post_type'));

        $result = ProgrammaticGenerator::build(
            $template === '' ? 'how-to' : $template,
            $topic,
            $count > 0 ? $count : 1,
            $postType === '' ? 'guide' : $postType
        );

        return new WP_REST_Response($result, 200);
    }
}
