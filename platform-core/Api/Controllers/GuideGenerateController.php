<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Guide\GuideGenerator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class GuideGenerateController
{
    private const RATE_LIMIT_PER_MINUTE = 20;

    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/guide/generate', [
            'methods' => 'POST',
            'callback' => [self::class, 'generate'],
            'permission_callback' => [self::class, 'canAccess'],
            'args' => [
                'topic' => [
                    'required' => true,
                    'type' => 'string',
                    'minLength' => 2,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'guide_type' => [
                    'type' => 'string',
                    'enum' => GuideGenerator::supportedGuideTypes(),
                    'default' => 'jak_zrobic',
                ],
                'difficulty' => [
                    'type' => 'string',
                    'default' => 'medium',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'estimated_time' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 720,
                    'default' => 30,
                ],
                'tools' => [
                    'type' => 'array',
                    'default' => [],
                ],
                'steps' => [
                    'type' => 'array',
                    'default' => [],
                ],
                'faq' => [
                    'type' => 'array',
                    'default' => [],
                ],
                'create_post' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
            ],
        ]);
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform();
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function generate(WP_REST_Request $request)
    {
        $rateLimit = self::consumeRateLimit(get_current_user_id());
        if (is_wp_error($rateLimit)) {
            return $rateLimit;
        }

        $draft = GuideGenerator::buildDraft([
            'topic' => (string) $request->get_param('topic'),
            'guide_type' => (string) $request->get_param('guide_type'),
            'difficulty' => (string) $request->get_param('difficulty'),
            'estimated_time' => (int) $request->get_param('estimated_time'),
            'tools' => $request->get_param('tools'),
            'steps' => $request->get_param('steps'),
            'faq' => $request->get_param('faq'),
        ]);

        $createPost = (bool) $request->get_param('create_post');

        $postId = 0;
        $status = 'generated';
        if ($createPost) {
            $created = GuideGenerator::saveDraft($draft, get_current_user_id());

            if (is_wp_error($created)) {
                return $created;
            }

            $postId = (int) $created;
            $status = 'draft_saved';
        }

        $response = [
            'status' => $status,
            'post_id' => $postId,
            'output' => $draft,
        ];

        if (! self::isValidOutputContract($response)) {
            return new WP_Error(
                'poradnik_guide_generate_invalid_output',
                'Guide generator output contract validation failed.',
                ['status' => 500]
            );
        }

        return new WP_REST_Response($response, 200);
    }

    /**
     * @return true|WP_Error
     */
    private static function consumeRateLimit(int $userId)
    {
        $key = 'poradnik_guide_generate_rl_' . ($userId > 0 ? $userId : 0);
        $current = get_transient($key);
        $count = is_numeric($current) ? (int) $current : 0;

        if ($count >= self::RATE_LIMIT_PER_MINUTE) {
            return new WP_Error(
                'poradnik_guide_generate_rate_limited',
                'Rate limit exceeded. Try again in a minute.',
                ['status' => 429]
            );
        }

        set_transient($key, $count + 1, MINUTE_IN_SECONDS);

        return true;
    }

    /**
     * @param array<string, mixed> $response
     */
    private static function isValidOutputContract(array $response): bool
    {
        if (! isset($response['status']) || ! is_string($response['status'])) {
            return false;
        }

        if (! array_key_exists('post_id', $response) || ! is_int($response['post_id'])) {
            return false;
        }

        $output = $response['output'] ?? null;
        if (! is_array($output)) {
            return false;
        }

        $requiredScalar = ['title', 'intro', 'guide_type', 'difficulty', 'meta_description'];
        foreach ($requiredScalar as $key) {
            if (! isset($output[$key]) || ! is_string($output[$key]) || trim($output[$key]) === '') {
                return false;
            }
        }

        if (! isset($output['estimated_time']) || ! is_int($output['estimated_time']) || $output['estimated_time'] < 1) {
            return false;
        }

        if (! isset($output['steps']) || ! is_array($output['steps']) || $output['steps'] === []) {
            return false;
        }

        foreach ($output['steps'] as $step) {
            if (! is_array($step)) {
                return false;
            }

            if (! isset($step['title'], $step['description']) || ! is_string($step['title']) || ! is_string($step['description'])) {
                return false;
            }
        }

        if (! isset($output['tools']) || ! is_array($output['tools'])) {
            return false;
        }

        if (! isset($output['faq']) || ! is_array($output['faq'])) {
            return false;
        }

        if (isset($output['workflow']) && ! is_array($output['workflow'])) {
            return false;
        }

        return true;
    }
}
