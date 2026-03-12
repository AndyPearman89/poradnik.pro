<?php

namespace Poradnik\Platform\Domain\Ai;

if (! defined('ABSPATH')) {
    exit;
}

final class ImageGenerator
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function generateFromTitle(string $title, string $category = 'general'): array
    {
        $title = trim(wp_strip_all_tags($title));
        $category = sanitize_key($category);

        if ($title === '') {
            return [];
        }

        $label = self::headlineFromTitle($title);

        return [
            'og_1200x630' => self::createAndAttachSvg($label, 1200, 630, $category, 'og'),
            'hero_16x9' => self::createAndAttachSvg($label, 1600, 900, $category, 'hero'),
            'social_1x1' => self::createAndAttachSvg($label, 1080, 1080, $category, 'social'),
        ];
    }

    private static function headlineFromTitle(string $title): string
    {
        $text = strtoupper($title);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        if (mb_strlen($text) > 60) {
            $text = mb_substr($text, 0, 57) . '...';
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private static function createAndAttachSvg(string $text, int $width, int $height, string $category, string $variant): array
    {
        $svg = self::buildSvg($text, $width, $height, $category);
        $filename = 'poradnik-ai-' . $variant . '-' . gmdate('Ymd-His') . '-' . wp_generate_password(4, false, false) . '.svg';

        $upload = wp_upload_bits($filename, null, $svg);

        if (! empty($upload['error'])) {
            return ['id' => 0, 'url' => '', 'file' => '', 'error' => (string) $upload['error']];
        }

        $attachment = [
            'post_mime_type' => 'image/svg+xml',
            'post_title' => sanitize_text_field(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachmentId = wp_insert_attachment($attachment, $upload['file']);

        if (! is_int($attachmentId) || $attachmentId < 1) {
            return ['id' => 0, 'url' => '', 'file' => (string) $upload['file'], 'error' => 'attachment_insert_failed'];
        }

        update_post_meta($attachmentId, '_poradnik_ai_image_variant', $variant);
        update_post_meta($attachmentId, '_poradnik_ai_image_category', $category);

        return [
            'id' => $attachmentId,
            'url' => wp_get_attachment_url($attachmentId) ?: '',
            'file' => (string) $upload['file'],
            'error' => '',
        ];
    }

    private static function buildSvg(string $text, int $width, int $height, string $category): string
    {
        [$bg1, $bg2, $fg] = self::palette($category);

        $safeText = esc_html($text);
        $fontSize = (int) max(26, min(64, floor($width / 18)));

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">' .
            '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="' . $bg1 . '"/><stop offset="100%" stop-color="' . $bg2 . '"/></linearGradient></defs>' .
            '<rect width="100%" height="100%" fill="url(#g)"/>' .
            '<text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="' . $fontSize . '" fill="' . $fg . '">' .
            $safeText .
            '</text>' .
            '</svg>';
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private static function palette(string $category): array
    {
        return match ($category) {
            'hosting' => ['#111111', '#6b4eff', '#ffffff'],
            'seo' => ['#0f172a', '#1d4ed8', '#ffffff'],
            'finance' => ['#052e16', '#15803d', '#ffffff'],
            default => ['#111111', '#6b4eff', '#ffffff'],
        };
    }
}
