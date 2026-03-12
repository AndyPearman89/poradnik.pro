<?php

namespace Poradnik\Platform\Domain\Seo;

if (! defined('ABSPATH')) {
    exit;
}

final class ProgrammaticGenerator
{
    /**
     * @return array<string, mixed>
     */
    public static function build(string $template, string $topic, int $count = 1, string $postType = 'guide'): array
    {
        $template = sanitize_key($template);
        $topic = trim(wp_strip_all_tags($topic));
        $postType = sanitize_key($postType);

        if ($topic === '' || $count < 1) {
            return ['created' => 0, 'items' => []];
        }

        $count = min($count, 50);
        $items = [];

        for ($index = 1; $index <= $count; $index++) {
            $title = self::buildTitle($template, $topic, $index);
            $content = self::buildContent($template, $topic, $title);

            $postId = wp_insert_post([
                'post_type' => $postType,
                'post_status' => 'draft',
                'post_title' => $title,
                'post_content' => $content,
            ], true);

            if (is_wp_error($postId) || ! is_int($postId) || $postId < 1) {
                continue;
            }

            update_post_meta($postId, '_poradnik_programmatic_template', $template);
            update_post_meta($postId, '_poradnik_programmatic_topic', $topic);

            $items[] = [
                'post_id' => $postId,
                'title' => $title,
                'status' => 'draft',
            ];
        }

        return ['created' => count($items), 'items' => $items];
    }

    private static function buildTitle(string $template, string $topic, int $index): string
    {
        $year = gmdate('Y');

        return match ($template) {
            'how-to' => 'Jak wybrac ' . $topic . ' - poradnik ' . $index,
            'best' => 'Najlepszy ' . $topic . ' ' . $year . ' (' . $index . ')',
            'ranking' => 'Ranking ' . $topic . ' - zestawienie ' . $index,
            default => 'Przewodnik: ' . $topic . ' - ' . $index,
        };
    }

    private static function buildContent(string $template, string $topic, string $title): string
    {
        $outline = ContentEnhancer::maybeInjectToc(implode("\n", [
            '<h2>Wstep</h2>',
            '<p>Material programmatic dla tematu: ' . esc_html($topic) . '.</p>',
            '<h2>Kryteria wyboru</h2>',
            '<p>Przeanalizuj ceny, funkcje, wsparcie i skalowalnosc.</p>',
            '<h2>Najlepsze opcje</h2>',
            '<p>Porownanie najczesciej wybieranych rozwiazan.</p>',
            '<h2>Podsumowanie</h2>',
            '<p>Wybierz opcje dopasowana do celu biznesowego.</p>',
        ]));

        return '<h1>' . esc_html($title) . '</h1>' . $outline . '<p>Template: ' . esc_html($template) . '</p>';
    }
}
