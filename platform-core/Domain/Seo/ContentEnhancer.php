<?php

namespace Poradnik\Platform\Domain\Seo;

if (! defined('ABSPATH')) {
    exit;
}

final class ContentEnhancer
{
    public static function maybeInjectToc(string $content): string
    {
        if (! is_singular()) {
            return $content;
        }

        if (! is_string($content) || $content === '') {
            return $content;
        }

        if (strpos($content, '<h2') === false) {
            return $content;
        }

        $tocEnabled = get_post_meta((int) get_the_ID(), 'toc_enabled', true);
        if ($tocEnabled === '0' || $tocEnabled === 0) {
            return $content;
        }

        if (strpos($content, 'poradnik-seo-toc') !== false) {
            return $content;
        }

        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches);
        $headings = $matches[1] ?? [];

        if (! is_array($headings) || count($headings) < 2) {
            return $content;
        }

        $list = '<div class="poradnik-seo-toc"><strong>Spis tresci</strong><ol>';
        foreach ($headings as $heading) {
            $label = trim(wp_strip_all_tags((string) $heading));
            if ($label === '') {
                continue;
            }
            $list .= '<li>' . esc_html($label) . '</li>';
        }
        $list .= '</ol></div>';

        return $list . $content;
    }

    public static function appendInternalLinks(string $content): string
    {
        if (! is_singular()) {
            return $content;
        }

        $postId = (int) get_the_ID();
        if ($postId < 1) {
            return $content;
        }

        $relatedIds = get_post_meta($postId, 'related_articles', true);
        if (! is_array($relatedIds) || $relatedIds === []) {
            return $content;
        }

        $relatedLinks = [];
        foreach ($relatedIds as $id) {
            $id = absint($id);
            if ($id < 1) {
                continue;
            }

            $url = get_permalink($id);
            $title = get_the_title($id);
            if (! is_string($url) || $url === '' || ! is_string($title) || $title === '') {
                continue;
            }

            $relatedLinks[] = '<li><a href="' . esc_url($url) . '">' . esc_html($title) . '</a></li>';
        }

        if ($relatedLinks === []) {
            return $content;
        }

        $block = '<div class="poradnik-related-links"><h3>Powiazane artykuly</h3><ul>' . implode('', $relatedLinks) . '</ul></div>';

        if (strpos($content, 'poradnik-related-links') !== false) {
            return $content;
        }

        return $content . $block;
    }
}
