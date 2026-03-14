<?php

namespace Poradnik\Platform\Modules\AiImageGenerator;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class AiImageGeneratorService
{
    /** @var array<int, string> */
    private const SUPPORTED_POST_TYPES = ['guide', 'ranking', 'review', 'comparison', 'news'];

    public static function init(): void
    {
        add_action('save_post', [self::class, 'onSavePost'], 20, 3);
        add_action('poradnik_generate_image', [self::class, 'queuePostGeneration'], 10, 2);
    }

    public static function onSavePost(int $postId, \WP_Post $post, bool $update): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        if (! in_array($post->post_type, self::SUPPORTED_POST_TYPES, true)) {
            return;
        }

        do_action('poradnik_generate_image', $postId, false);
    }

    public static function queuePostGeneration(int $postId, bool $forceRegenerate = false): void
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post || ! in_array($post->post_type, self::SUPPORTED_POST_TYPES, true)) {
            return;
        }

        global $wpdb;
        $table = Migrator::tableName('image_generation_queue');

        $exists = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE post_id = %d AND status IN ('pending','processing') ORDER BY id DESC LIMIT 1",
                $postId
            )
        );

        if ($exists > 0 && ! $forceRegenerate) {
            return;
        }

        $wpdb->insert(
            $table,
            [
                'post_id' => $postId,
                'status' => 'pending',
                'attempts' => 0,
                'force_regenerate' => $forceRegenerate ? 1 : 0,
                'last_error' => '',
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%d', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function generateImmediately(int $postId, bool $forceRegenerate = false): array
    {
        $result = self::generateForPost($postId, $forceRegenerate);

        if (! empty($result['ok'])) {
            self::markQueueAsDone($postId);

            return $result;
        }

        self::queuePostGeneration($postId, $forceRegenerate);
        $result['queued'] = true;

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function generateForPost(int $postId, bool $forceRegenerate = false): array
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            return ['ok' => false, 'error' => 'post_not_found'];
        }

        $type = sanitize_key($post->post_type);
        if (! in_array($type, self::SUPPORTED_POST_TYPES, true)) {
            return ['ok' => false, 'error' => 'unsupported_post_type'];
        }

        if (! $forceRegenerate && self::hasGeneratedImages($postId)) {
            return ['ok' => true, 'cached' => true, 'items' => []];
        }

        $title = get_the_title($postId);
        $slug = sanitize_title($post->post_name !== '' ? $post->post_name : $title);
        $category = self::primaryCategory($postId, $type);

        $style = ImageTemplateEngine::styleForType($type);
        $color = ImageTemplateEngine::colorForType($type);
        $prompt = ImagePromptBuilder::build([
            'article_title' => (string) $title,
            'article_category' => $category,
            'article_type' => $type,
            'template_style' => $style,
            'color_scheme' => $color,
        ]);

        $items = [];
        foreach (ImageTemplateEngine::variants() as $variant => $config) {
            $image = self::generateImage((string) $prompt, (int) $config['width'], (int) $config['height'], $type, $color);

            $stored = ImageStorageService::saveToMediaLibrary($postId, $slug, $type, (string) $variant, $image);
            $attachmentId = (int) ($stored['id'] ?? 0);

            if ($attachmentId < 1) {
                return ['ok' => false, 'error' => (string) ($stored['error'] ?? 'storage_failed')];
            }

            ImageStorageService::assignToPost($postId, $attachmentId, (string) $variant);

            $items[$variant] = [
                'id' => $attachmentId,
                'url' => (string) ($stored['url'] ?? ''),
            ];
        }

        return ['ok' => true, 'cached' => false, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    public static function generateFromTitle(string $title, string $type = 'guide', bool $attachToPost = false, int $postId = 0): array
    {
        $type = sanitize_key($type);
        if (! in_array($type, self::SUPPORTED_POST_TYPES, true)) {
            $type = 'guide';
        }

        $title = trim(wp_strip_all_tags($title));
        if ($title === '') {
            return ['ok' => false, 'error' => 'empty_title'];
        }

        $slug = sanitize_title($title);
        $style = ImageTemplateEngine::styleForType($type);
        $color = ImageTemplateEngine::colorForType($type);
        $prompt = ImagePromptBuilder::build([
            'article_title' => $title,
            'article_category' => $type,
            'article_type' => $type,
            'template_style' => $style,
            'color_scheme' => $color,
        ]);

        $items = [];
        foreach (ImageTemplateEngine::variants() as $variant => $config) {
            $image = self::generateImage((string) $prompt, (int) $config['width'], (int) $config['height'], $type, $color);
            $targetPostId = $attachToPost ? $postId : 0;
            $stored = ImageStorageService::saveToMediaLibrary($targetPostId, $slug, $type, (string) $variant, $image);

            $attachmentId = (int) ($stored['id'] ?? 0);
            if ($attachmentId < 1) {
                return ['ok' => false, 'error' => (string) ($stored['error'] ?? 'storage_failed')];
            }

            if ($attachToPost && $postId > 0) {
                ImageStorageService::assignToPost($postId, $attachmentId, (string) $variant);
            }

            $items[$variant] = [
                'id' => $attachmentId,
                'url' => (string) ($stored['url'] ?? ''),
            ];
        }

        return ['ok' => true, 'cached' => false, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private static function generateImage(string $prompt, int $width, int $height, string $type, string $color): array
    {
        $provider = sanitize_key((string) get_option('poradnik_ai_image_provider', 'fallback'));

        $external = apply_filters('poradnik_ai_image_external_generate', null, [
            'provider' => $provider,
            'prompt' => $prompt,
            'width' => $width,
            'height' => $height,
            'type' => $type,
            'color' => $color,
        ]);

        if (is_array($external) && isset($external['content']) && is_string($external['content']) && $external['content'] !== '') {
            return [
                'content' => $external['content'],
                'mime' => (string) ($external['mime'] ?? 'image/png'),
            ];
        }

        return [
            'content' => self::fallbackSvg($prompt, $width, $height, $color),
            'mime' => 'image/svg+xml',
        ];
    }

    private static function fallbackSvg(string $prompt, int $width, int $height, string $accentColor): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags($prompt)));
        if (mb_strlen($text) > 90) {
            $text = mb_substr($text, 0, 87) . '...';
        }

        $safe = esc_html($text);
        $fontSize = (int) max(20, min(52, floor($width / 20)));

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
            . '<rect width="100%" height="100%" fill="#ffffff"/>'
            . '<rect x="0" y="0" width="100%" height="14" fill="' . esc_attr($accentColor) . '"/>'
            . '<rect x="0" y="' . ($height - 14) . '" width="100%" height="14" fill="' . esc_attr($accentColor) . '"/>'
            . '<text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-family="Arial, Helvetica, sans-serif" font-size="' . $fontSize . '" fill="#1f2937">' . $safe . '</text>'
            . '</svg>';
    }

    private static function hasGeneratedImages(int $postId): bool
    {
        return (int) get_post_meta($postId, 'featured_image', true) > 0
            && (int) get_post_meta($postId, 'og_image', true) > 0
            && (int) get_post_meta($postId, 'social_image', true) > 0;
    }

    private static function primaryCategory(int $postId, string $fallback): string
    {
        $terms = get_the_terms($postId, 'category');
        if (is_array($terms) && isset($terms[0]) && $terms[0] instanceof \WP_Term) {
            return sanitize_key($terms[0]->slug);
        }

        return $fallback;
    }

    private static function markQueueAsDone(int $postId): void
    {
        global $wpdb;

        $table = Migrator::tableName('image_generation_queue');
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = %s, last_error = %s, updated_at = %s WHERE post_id = %d AND status IN ('pending','processing')",
                'done',
                '',
                current_time('mysql', true),
                $postId
            )
        );
    }
}
