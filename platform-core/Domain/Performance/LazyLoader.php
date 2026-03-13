<?php

namespace Poradnik\Platform\Domain\Performance;

if (! defined('ABSPATH')) {
    exit;
}

final class LazyLoader
{
    public static function applyLazyLoadToContent(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            static function (array $matches): string {
                $tag = $matches[0];

                if (strpos($tag, 'loading=') !== false) {
                    return $tag;
                }

                return str_replace('<img', '<img loading="lazy"', $tag);
            },
            $content
        ) ?? $content;

        return $content;
    }

    public static function addLazyLoadToPostThumbnail(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        if (strpos($html, 'loading=') !== false) {
            return $html;
        }

        return str_replace('<img', '<img loading="lazy"', $html);
    }
}
