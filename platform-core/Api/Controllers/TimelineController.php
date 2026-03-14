<?php

namespace Poradnik\Platform\Api\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class TimelineController
{
    public static function registerRoutes(): void
    {
        register_rest_route(
            'poradnik/v1',
            '/timeline/(?P<post_id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'show'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'post_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'minimum'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function show(WP_REST_Request $request)
    {
        $postId = absint($request->get_param('post_id'));
        if ($postId < 1) {
            return new WP_Error(
                'poradnik_timeline_invalid_post_id',
                'Invalid post_id parameter.',
                ['status' => 400]
            );
        }

        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            return new WP_Error(
                'poradnik_timeline_post_not_found',
                'Post not found.',
                ['status' => 404]
            );
        }

        $status = get_post_status($post);
        if ($status !== 'publish' && ! current_user_can('edit_post', $postId)) {
            return new WP_Error(
                'poradnik_timeline_forbidden',
                'You are not allowed to access this timeline.',
                ['status' => 403]
            );
        }

        $timeline = self::timelineData($postId);
        $response = [
            'post_id' => $postId,
            'post_type' => (string) get_post_type($postId),
            'timeline' => $timeline,
            'steps_count' => count($timeline['steps']),
        ];

        if (! self::isValidResponsePayload($response)) {
            return new WP_Error(
                'poradnik_timeline_invalid_response_contract',
                'Timeline response contract validation failed.',
                ['status' => 500]
            );
        }

        return new WP_REST_Response(
            $response,
            200
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function timelineData(int $postId): array
    {
        $postType = (string) get_post_type($postId);

        if (function_exists('poradnik_theme_get_timeline_data')) {
            $data = poradnik_theme_get_timeline_data($postId);
            if (is_array($data) && isset($data['steps']) && is_array($data['steps'])) {
                return self::normalizeTimeline($data);
            }
        }

        $defaultType = 'guide_steps';
        if ($postType === 'ranking') {
            $defaultType = 'ranking_history';
        } elseif ($postType === 'news') {
            $defaultType = 'news_events';
        }

        $type = self::metaValue($postId, 'timeline_type', $defaultType);
        $theme = self::metaValue($postId, 'timeline_theme', 'default');
        $layout = self::metaValue($postId, 'timeline_layout', 'vertical');
        $steps = self::timelineSteps($postId, $postType);

        return [
            'type' => $type,
            'theme' => $theme,
            'layout' => $layout,
            'steps' => $steps,
        ];
    }

    /**
     * @param mixed $raw
     * @return array<string, mixed>
     */
    private static function normalizeTimeline($raw): array
    {
        $steps = [];
        $rawSteps = isset($raw['steps']) && is_array($raw['steps']) ? $raw['steps'] : [];

        foreach ($rawSteps as $index => $step) {
            if (! is_array($step)) {
                continue;
            }

            $steps[] = self::normalizeStep($step, $index);
        }

        return [
            'type' => isset($raw['type']) && is_string($raw['type']) && $raw['type'] !== '' ? sanitize_key($raw['type']) : 'guide_steps',
            'theme' => isset($raw['theme']) && is_string($raw['theme']) && $raw['theme'] !== '' ? sanitize_key($raw['theme']) : 'default',
            'layout' => isset($raw['layout']) && is_string($raw['layout']) && $raw['layout'] !== '' ? sanitize_key($raw['layout']) : 'vertical',
            'steps' => $steps,
        ];
    }

    private static function metaValue(int $postId, string $key, string $fallback): string
    {
        $value = get_post_meta($postId, $key, true);
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        return sanitize_key($value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function timelineSteps(int $postId, string $postType = ''): array
    {
        if ($postType === '') {
            $postType = (string) get_post_type($postId);
        }

        $raw = get_post_meta($postId, 'timeline_steps', true);

        if (! is_array($raw) || $raw === []) {
            $raw = get_post_meta($postId, 'steps', true);
        }

        if (! is_array($raw) || $raw === []) {
            $raw = get_post_meta($postId, 'guide_steps', true);
        }

        if ((! is_array($raw) || $raw === []) && $postType === 'ranking') {
            $raw = get_post_meta($postId, 'ranking_versions', true);

            if (is_array($raw) && $raw !== []) {
                $steps = [];
                foreach ($raw as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $versionLabel = trim((string) ($row['version_title'] ?? $row['version'] ?? $row['title'] ?? ''));
                    $versionDate = trim((string) ($row['version_date'] ?? $row['date'] ?? $row['changed_at'] ?? ''));
                    $description = trim((string) ($row['summary'] ?? $row['description'] ?? $row['notes'] ?? ''));

                    $title = $versionLabel !== '' ? $versionLabel : 'Aktualizacja rankingu';
                    if ($versionDate !== '') {
                        $title .= ' (' . $versionDate . ')';
                    }

                    $steps[] = self::normalizeStep(
                        [
                            'title' => $title,
                            'description' => $description,
                            'link' => (string) ($row['link'] ?? $row['url'] ?? ''),
                            'image_id' => absint($row['image_id'] ?? $row['image'] ?? 0),
                            'order' => absint($row['order'] ?? ($index + 1)),
                        ],
                        $index
                    );
                }

                if ($steps !== []) {
                    usort(
                        $steps,
                        static function (array $left, array $right): int {
                            return ((int) ($left['order'] ?? 0)) <=> ((int) ($right['order'] ?? 0));
                        }
                    );

                    return $steps;
                }
            }

            $rankingProducts = get_post_meta($postId, 'ranking_products', true);
            if (is_array($rankingProducts) && $rankingProducts !== []) {
                $steps = [];
                foreach ($rankingProducts as $index => $product) {
                    if (! is_array($product)) {
                        continue;
                    }

                    $name = trim((string) ($product['product_name'] ?? $product['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $rating = (float) ($product['product_rating'] ?? $product['quality'] ?? 0);
                    $description = trim((string) ($product['product_description'] ?? $product['description'] ?? ''));
                    if ($rating > 0) {
                        $description = trim($description . ' Ocena: ' . number_format_i18n($rating, 1) . '/10.');
                    }

                    $steps[] = self::normalizeStep(
                        [
                            'title' => '#' . ($index + 1) . ' ' . $name,
                            'description' => $description,
                            'link' => (string) ($product['affiliate_link'] ?? ''),
                            'order' => $index + 1,
                        ],
                        $index
                    );

                    if (($index + 1) >= 10) {
                        break;
                    }
                }

                if ($steps !== []) {
                    return $steps;
                }
            }
        }

        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $steps = [];
        foreach ($raw as $index => $step) {
            if (! is_array($step)) {
                continue;
            }

            $normalized = self::normalizeStep($step, $index);
            if ($normalized['title'] === '' && $normalized['description'] === '') {
                continue;
            }

            $steps[] = $normalized;
        }

        usort(
            $steps,
            static function (array $left, array $right): int {
                return ((int) ($left['order'] ?? 0)) <=> ((int) ($right['order'] ?? 0));
            }
        );

        return $steps;
    }

    /**
     * @param array<string, mixed> $step
     * @return array<string, mixed>
     */
    private static function normalizeStep(array $step, int $index): array
    {
        $title = trim((string) ($step['step_title'] ?? $step['title'] ?? ''));
        $description = trim((string) ($step['step_description'] ?? $step['description'] ?? $step['content'] ?? ''));
        $imageId = absint($step['step_image'] ?? $step['image_id'] ?? $step['image'] ?? 0);
        $order = absint($step['step_order'] ?? $step['order'] ?? ($index + 1));

        return [
            'title' => $title,
            'description' => $description,
            'image_id' => $imageId,
            'image_url' => $imageId > 0 ? (string) wp_get_attachment_url($imageId) : '',
            'icon' => trim((string) ($step['step_icon'] ?? $step['icon'] ?? '')),
            'tip' => trim((string) ($step['step_tip'] ?? $step['tip'] ?? '')),
            'warning' => trim((string) ($step['step_warning'] ?? $step['warning'] ?? '')),
            'link' => esc_url_raw((string) ($step['step_link'] ?? $step['link'] ?? '')),
            'order' => $order > 0 ? $order : ($index + 1),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function isValidResponsePayload(array $payload): bool
    {
        if (! isset($payload['post_id']) || ! is_int($payload['post_id']) || $payload['post_id'] < 1) {
            return false;
        }

        if (! isset($payload['post_type']) || ! is_string($payload['post_type']) || $payload['post_type'] === '') {
            return false;
        }

        if (! isset($payload['steps_count']) || ! is_int($payload['steps_count']) || $payload['steps_count'] < 0) {
            return false;
        }

        if (! isset($payload['timeline']) || ! is_array($payload['timeline'])) {
            return false;
        }

        $timeline = $payload['timeline'];
        foreach (['type', 'theme', 'layout'] as $field) {
            if (! isset($timeline[$field]) || ! is_string($timeline[$field]) || $timeline[$field] === '') {
                return false;
            }
        }

        if (! isset($timeline['steps']) || ! is_array($timeline['steps'])) {
            return false;
        }

        if (count($timeline['steps']) !== $payload['steps_count']) {
            return false;
        }

        foreach ($timeline['steps'] as $step) {
            if (! is_array($step)) {
                return false;
            }

            if (! isset($step['title']) || ! is_string($step['title'])) {
                return false;
            }

            if (! isset($step['description']) || ! is_string($step['description'])) {
                return false;
            }

            if (! isset($step['image_id']) || ! is_int($step['image_id']) || $step['image_id'] < 0) {
                return false;
            }

            if (! isset($step['image_url']) || ! is_string($step['image_url'])) {
                return false;
            }

            if (! isset($step['icon']) || ! is_string($step['icon'])) {
                return false;
            }

            if (! isset($step['tip']) || ! is_string($step['tip'])) {
                return false;
            }

            if (! isset($step['warning']) || ! is_string($step['warning'])) {
                return false;
            }

            if (! isset($step['link']) || ! is_string($step['link'])) {
                return false;
            }

            if (! isset($step['order']) || ! is_int($step['order']) || $step['order'] < 1) {
                return false;
            }
        }

        return true;
    }
}
