<?php

namespace Poradnik\Platform\Modules\Timeline;

use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_POST_TYPES = ['guide', 'ranking', 'news', 'comparison'];

    public static function init(): void
    {
        add_action('init', [self::class, 'register'], 20);
    }

    public static function register(): void
    {
        foreach (self::SUPPORTED_POST_TYPES as $postType) {
            self::registerTypeMeta($postType);
        }

        EventLogger::dispatch(
            'poradnik_platform_timeline_registered',
            [
                'post_types' => self::SUPPORTED_POST_TYPES,
                'meta_keys' => ['timeline_type', 'timeline_theme', 'timeline_layout', 'timeline_steps'],
            ]
        );
    }

    private static function registerTypeMeta(string $postType): void
    {
        register_post_meta(
            $postType,
            'timeline_type',
            [
                'type' => 'string',
                'single' => true,
                'default' => 'guide_steps',
                'show_in_rest' => true,
                'sanitize_callback' => static fn($value): string => sanitize_key((string) $value),
                'auth_callback' => [self::class, 'canEditPosts'],
            ]
        );

        register_post_meta(
            $postType,
            'timeline_theme',
            [
                'type' => 'string',
                'single' => true,
                'default' => 'default',
                'show_in_rest' => true,
                'sanitize_callback' => static fn($value): string => sanitize_key((string) $value),
                'auth_callback' => [self::class, 'canEditPosts'],
            ]
        );

        register_post_meta(
            $postType,
            'timeline_layout',
            [
                'type' => 'string',
                'single' => true,
                'default' => 'vertical',
                'show_in_rest' => true,
                'sanitize_callback' => static fn($value): string => sanitize_key((string) $value),
                'auth_callback' => [self::class, 'canEditPosts'],
            ]
        );

        register_post_meta(
            $postType,
            'timeline_steps',
            [
                'type' => 'array',
                'single' => true,
                'default' => [],
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                        ],
                    ],
                ],
                'sanitize_callback' => [self::class, 'sanitizeSteps'],
                'auth_callback' => [self::class, 'canEditPosts'],
            ]
        );
    }

    /**
     * @param mixed $steps
     * @return array<int, array<string, mixed>>
     */
    public static function sanitizeSteps($steps): array
    {
        if (! is_array($steps)) {
            return [];
        }

        $normalized = [];

        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                continue;
            }

            $title = sanitize_text_field((string) ($step['title'] ?? $step['step_title'] ?? ''));
            $description = wp_kses_post((string) ($step['description'] ?? $step['step_description'] ?? ''));

            if ($title === '' && $description === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'description' => $description,
                'icon' => sanitize_text_field((string) ($step['icon'] ?? $step['step_icon'] ?? '')),
                'tip' => sanitize_text_field((string) ($step['tip'] ?? $step['step_tip'] ?? '')),
                'warning' => sanitize_text_field((string) ($step['warning'] ?? $step['step_warning'] ?? '')),
                'link' => esc_url_raw((string) ($step['link'] ?? $step['step_link'] ?? '')),
                'image_id' => absint($step['image_id'] ?? $step['step_image'] ?? 0),
                'order' => absint($step['order'] ?? $step['step_order'] ?? ($index + 1)),
            ];
        }

        usort(
            $normalized,
            static function (array $left, array $right): int {
                return ((int) ($left['order'] ?? 0)) <=> ((int) ($right['order'] ?? 0));
            }
        );

        return $normalized;
    }

    public static function canEditPosts(): bool
    {
        return current_user_can('edit_posts');
    }
}
