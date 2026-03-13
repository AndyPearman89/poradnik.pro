<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Seo\ProgrammaticGenerator;
use WP_Error;
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
            'methods'             => 'POST',
            'callback'            => [self::class, 'build'],
            'permission_callback' => [self::class, 'canAccess'],
            'args'                => [
                'generation_mode' => [
                    'type'    => 'string',
                    'enum'    => ['single', 'batch', 'cluster', 'cluster-batch'],
                    'default' => 'single',
                ],
                'template' => [
                    'type'    => 'string',
                    'enum'    => ['jak-zrobic', 'jak-ustawic', 'jak-naprawic', 'jak-wymienic', 'jak-zainstalowac', 'jak-wyczyscic', 'jak-skonfigurowac', 'jak-dziala', 'best', 'ranking'],
                    'default' => 'jak-zrobic',
                ],
                'topic' => [
                    'type'              => 'string',
                    'minLength'         => 2,
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'count' => [
                    'type'    => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 1,
                ],
                'post_type' => [
                    'type'    => 'string',
                    'enum'    => ['guide', 'ranking', 'review', 'comparison', 'tool', 'news'],
                    'default' => 'guide',
                ],
                'hub' => [
                    'type'              => 'string',
                    'default'           => 'all',
                    'sanitize_callback' => 'sanitize_key',
                ],
                'max_topics' => [
                    'type'    => 'integer',
                    'minimum' => 1,
                    'maximum' => 250,
                    'default' => 25,
                ],
            ],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function build(WP_REST_Request $request)
    {
        $generationMode = sanitize_key((string) $request->get_param('generation_mode'));
        $template = sanitize_key((string) $request->get_param('template'));
        $topic = sanitize_text_field((string) $request->get_param('topic'));
        $count = absint($request->get_param('count'));
        $postType = sanitize_key((string) $request->get_param('post_type'));
        $hub = sanitize_key((string) $request->get_param('hub'));
        $maxTopics = absint($request->get_param('max_topics'));

        if (in_array($generationMode, ['single', 'cluster'], true) && $topic === '') {
            return new WP_Error(
                'poradnik_programmatic_topic_required',
                'Parameter topic is required for single and cluster generation modes.',
                ['status' => 400]
            );
        }

        if ($generationMode === 'cluster') {
            $result = ProgrammaticGenerator::buildCluster(
                $topic,
                $template === '' ? 'jak-zrobic' : $template,
                $count > 0 ? $count : 1
            );
        } elseif ($generationMode === 'cluster-batch') {
            $result = ProgrammaticGenerator::buildClusterBatch(
                $template === '' ? 'jak-zrobic' : $template,
                $hub === '' ? 'all' : $hub,
                $maxTopics > 0 ? $maxTopics : 25
            );
        } elseif ($generationMode === 'batch') {
            $result = ProgrammaticGenerator::buildBatch(
                $template === '' ? 'jak-zrobic' : $template,
                $count > 0 ? $count : 1,
                $postType === '' ? 'guide' : $postType,
                $hub === '' ? 'all' : $hub,
                $maxTopics > 0 ? $maxTopics : 25
            );
        } else {
            $result = ProgrammaticGenerator::build(
                $template === '' ? 'jak-zrobic' : $template,
                $topic,
                $count > 0 ? $count : 1,
                $postType === '' ? 'guide' : $postType
            );
        }

        return new WP_REST_Response($result, 200);
    }
}
