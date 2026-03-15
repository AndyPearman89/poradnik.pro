<?php

namespace Poradnik\Platform\Modules\AiImageGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class ImageStorageService
{
    /**
     * @param array<string, mixed> $image
     * @return array<string, mixed>
     */
    public static function saveToMediaLibrary(int $postId, string $slug, string $type, string $variant, array $image): array
    {
        $mime = (string) ($image['mime'] ?? 'image/svg+xml');
        $content = (string) ($image['content'] ?? '');

        if ($content === '') {
            return ['id' => 0, 'url' => '', 'error' => 'empty_image_content'];
        }

        $extension = $mime === 'image/png' ? 'png' : 'svg';
        $filename = sanitize_file_name($slug . '-' . $type . '-' . $variant . '.' . $extension);

        $upload = wp_upload_bits($filename, null, $content);
        if (! empty($upload['error'])) {
            return ['id' => 0, 'url' => '', 'error' => (string) $upload['error']];
        }

        $attachment = [
            'post_mime_type' => $mime,
            'post_title' => sanitize_text_field(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachmentId = wp_insert_attachment($attachment, (string) $upload['file'], $postId);
        if (! is_int($attachmentId) || $attachmentId < 1) {
            return ['id' => 0, 'url' => '', 'error' => 'attachment_insert_failed'];
        }

        $postTitle = get_the_title($postId);
        $alt = trim((string) $postTitle) . ' illustration ' . $type;
        update_post_meta($attachmentId, '_wp_attachment_image_alt', sanitize_text_field($alt));
        update_post_meta($attachmentId, '_poradnik_ai_image_variant', sanitize_key($variant));
        update_post_meta($attachmentId, '_poradnik_ai_image_type', sanitize_key($type));
        update_post_meta($attachmentId, '_poradnik_ai_image_slug', sanitize_title($slug));
        update_post_meta($attachmentId, '_poradnik_ai_generated_at', gmdate('Y-m-d H:i:s'));
        if ($postId > 0) {
            update_post_meta($attachmentId, '_poradnik_ai_source_post_id', $postId);
        }

        return [
            'id' => $attachmentId,
            'url' => (string) wp_get_attachment_url($attachmentId),
            'error' => '',
        ];
    }

    public static function assignToPost(int $postId, int $attachmentId, string $variant): void
    {
        if ($postId < 1 || $attachmentId < 1) {
            return;
        }

        update_post_meta($postId, $variant . '_image', $attachmentId);

        if ($variant === 'featured') {
            set_post_thumbnail($postId, $attachmentId);
            update_post_meta($postId, 'featured_image', $attachmentId);
        }

        if ($variant === 'og') {
            update_post_meta($postId, 'og_image', $attachmentId);
            update_post_meta($postId, '_yoast_wpseo_opengraph-image-id', $attachmentId);
        }

        if ($variant === 'social') {
            update_post_meta($postId, 'social_image', $attachmentId);
            update_post_meta($postId, '_twitter_image_id', $attachmentId);
        }
    }
}
