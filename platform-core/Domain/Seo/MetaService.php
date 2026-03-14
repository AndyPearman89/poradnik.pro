<?php

namespace Poradnik\Platform\Domain\Seo;

if (! defined('ABSPATH')) {
    exit;
}

final class MetaService
{
    /**
     * @param array<string, mixed> $parts
     * @return array<string, mixed>
     */
    public static function documentTitleParts(array $parts): array
    {
        if (! is_singular()) {
            return $parts;
        }

        $post = get_queried_object();
        if (! $post instanceof \WP_Post) {
            return $parts;
        }

        $year = gmdate('Y');
        $baseTitle = get_the_title($post);

        if ($baseTitle !== '') {
            $parts['title'] = $baseTitle . ' | Przewodnik ' . $year;
        }

        return $parts;
    }

    public static function metaDescription(): string
    {
        if (! is_singular()) {
            return '';
        }

        $post = get_queried_object();
        if (! $post instanceof \WP_Post) {
            return '';
        }

        $introSummary = trim(wp_strip_all_tags((string) get_post_meta($post->ID, 'intro_summary', true)));
        if ($introSummary !== '') {
            return mb_substr($introSummary, 0, 155);
        }

        $excerpt = trim(wp_strip_all_tags((string) $post->post_excerpt));
        if ($excerpt === '') {
            $excerpt = trim(wp_strip_all_tags((string) $post->post_content));
        }

        if ($excerpt === '') {
            return '';
        }

        return mb_substr($excerpt, 0, 155);
    }
}
