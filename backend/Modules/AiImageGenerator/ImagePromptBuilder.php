<?php

namespace Poradnik\Platform\Modules\AiImageGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class ImagePromptBuilder
{
    /**
     * @param array<string, string> $context
     */
    public static function build(array $context): string
    {
        $articleTitle = trim((string) ($context['article_title'] ?? ''));
        $articleCategory = sanitize_key((string) ($context['article_category'] ?? 'general'));
        $articleType = sanitize_key((string) ($context['article_type'] ?? 'guide'));
        $templateStyle = trim((string) ($context['template_style'] ?? 'modern vector illustration'));
        $colorScheme = trim((string) ($context['color_scheme'] ?? '#6b4eff'));

        return trim(implode("\n", [
            'modern vector illustration showing',
            $articleTitle,
            'category: ' . $articleCategory,
            'type: ' . $articleType,
            $templateStyle,
            'clean flat design',
            'white background',
            'accent color: ' . $colorScheme,
        ]));
    }
}
