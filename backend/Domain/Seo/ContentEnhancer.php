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

        $headings = [];
        $usedIds = [];
        $contentWithAnchors = preg_replace_callback(
            '/<h2([^>]*)>(.*?)<\/h2>/i',
            static function (array $match) use (&$headings, &$usedIds): string {
                $attrs = (string) ($match[1] ?? '');
                $inner = (string) ($match[2] ?? '');
                $label = trim(wp_strip_all_tags($inner));

                if ($label === '') {
                    return (string) ($match[0] ?? '');
                }

                $id = '';
                if (preg_match('/\sid=("|\')(.*?)\1/i', $attrs, $idMatch)) {
                    $id = sanitize_title((string) ($idMatch[2] ?? ''));
                }

                if ($id === '') {
                    $id = sanitize_title($label);
                }

                if ($id === '') {
                    $id = 'sekcja';
                }

                $baseId = $id;
                $suffix = 2;
                while (isset($usedIds[$id])) {
                    $id = $baseId . '-' . $suffix;
                    $suffix++;
                }

                $usedIds[$id] = true;
                $headings[] = ['id' => $id, 'label' => $label];

                if (! preg_match('/\sid=("|\')(.*?)\1/i', $attrs)) {
                    $attrs .= ' id="' . esc_attr($id) . '"';
                }

                return '<h2' . $attrs . '>' . $inner . '</h2>';
            },
            $content
        );

        if (! is_string($contentWithAnchors)) {
            return $content;
        }

        if (count($headings) < 2) {
            return $content;
        }

        $list = '<div class="poradnik-seo-toc"><strong>Spis tresci</strong><ol>';
        foreach ($headings as $heading) {
            if (! is_array($heading)) {
                continue;
            }

            $label = trim((string) ($heading['label'] ?? ''));
            $id = trim((string) ($heading['id'] ?? ''));
            if ($label === '') {
                continue;
            }

            if ($id === '') {
                $list .= '<li>' . esc_html($label) . '</li>';
                continue;
            }

            $list .= '<li><a href="#' . esc_attr($id) . '">' . esc_html($label) . '</a></li>';
        }
        $list .= '</ol></div>';

        return $list . $contentWithAnchors;
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
