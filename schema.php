<?php
if (!defined('ABSPATH')) {
    exit;
}

function pse_parse_lines_meta(int $post_id, string $key): array
{
    $raw = (string) get_post_meta($post_id, $key, true);
    if ($raw === '') {
        return [];
    }

    $lines = preg_split('/\r\n|\n|\r/', $raw);
    $lines = array_map(static fn($line) => trim((string) $line), (array) $lines);

    return array_values(array_filter($lines, static fn($line) => $line !== ''));
}

function pse_schema_article(int $post_id): array
{
    $author = get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id));
    $image = get_the_post_thumbnail_url($post_id, 'full');

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title($post_id),
        'description' => pse_generate_meta_description($post_id),
        'author' => [
            '@type' => 'Person',
            'name' => $author ?: get_bloginfo('name'),
        ],
        'datePublished' => get_post_time(DATE_W3C, false, $post_id),
        'dateModified' => get_post_modified_time(DATE_W3C, false, $post_id),
        'mainEntityOfPage' => get_permalink($post_id),
        'image' => $image ?: null,
    ];
}

function pse_schema_howto(int $post_id): ?array
{
    $steps = pse_parse_lines_meta($post_id, '_poradnik_steps');
    if (count($steps) < 2) {
        return null;
    }

    $howto_steps = [];
    foreach ($steps as $idx => $step) {
        $howto_steps[] = [
            '@type' => 'HowToStep',
            'position' => $idx + 1,
            'name' => mb_substr($step, 0, 80),
            'text' => $step,
        ];
    }

    $tools = pse_parse_lines_meta($post_id, '_poradnik_tools');
    $tool_data = [];
    foreach ($tools as $tool) {
        $tool_data[] = ['@type' => 'HowToTool', 'name' => $tool];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => get_the_title($post_id),
        'description' => pse_generate_meta_description($post_id),
        'step' => $howto_steps,
        'tool' => $tool_data,
        'totalTime' => 'PT30M',
    ];
}

function pse_schema_faq(int $post_id): ?array
{
    $faq_lines = pse_parse_lines_meta($post_id, '_poradnik_faq');
    if (empty($faq_lines)) {
        return null;
    }

    $entities = [];
    foreach ($faq_lines as $line) {
        if (strpos($line, '|') === false) {
            continue;
        }
        [$q, $a] = array_map('trim', explode('|', $line, 2));
        if ($q === '' || $a === '') {
            continue;
        }

        $entities[] = [
            '@type' => 'Question',
            'name' => $q,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $a,
            ],
        ];
    }

    if (empty($entities)) {
        return null;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $entities,
    ];
}

function pse_schema_breadcrumb(int $post_id): ?array
{
    if (!function_exists('pse_get_breadcrumb_items')) {
        return null;
    }

    $items = pse_get_breadcrumb_items($post_id);
    if (count($items) < 2) {
        return null;
    }

    $list = [];
    foreach ($items as $idx => $item) {
        $list[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'name' => (string) $item['name'],
            'item' => (string) $item['url'],
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}

function pse_schema_group_rating(int $post_id): ?array
{
    if (function_exists('pse_is_theme_ratings_active') && pse_is_theme_ratings_active()) {
        return null;
    }

    if (!function_exists('poradnik_pro_rating_stats')) {
        return null;
    }

    $all_ids = [$post_id];

    if (function_exists('pse_get_translation_group') && function_exists('pse_get_group_posts')) {
        $group = pse_get_translation_group($post_id);
        $group_ids = pse_get_group_posts($group);
        if (!empty($group_ids)) {
            $all_ids = array_values(array_unique(array_map('intval', $group_ids)));
        }
    }

    $sum = 0.0;
    $count = 0;

    foreach ($all_ids as $id) {
        $stats = poradnik_pro_rating_stats((int) $id);
        $c = (int) ($stats['count'] ?? 0);
        $a = (float) ($stats['avg'] ?? 0);

        if ($c > 0) {
            $sum += $a * $c;
            $count += $c;
        }
    }

    if ($count < 1) {
        return null;
    }

    return [
        '@type' => 'AggregateRating',
        'ratingValue' => round($sum / $count, 2),
        'reviewCount' => $count,
        'bestRating' => 5,
        'worstRating' => 1,
    ];
}

add_action('wp_head', function (): void {
    if (function_exists('pse_is_theme_schema_active') && pse_is_theme_schema_active()) {
        return;
    }

    if (!is_single()) {
        return;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return;
    }

    $settings = function_exists('pse_get_settings') ? pse_get_settings() : [];

    $graphs = [];

    if (!empty($settings['schema_article'])) {
        $article = pse_schema_article($post_id);

        $group_rating = pse_schema_group_rating($post_id);
        if (is_array($group_rating)) {
            $article['aggregateRating'] = $group_rating;
        }

        $graphs[] = $article;
    }

    if (!empty($settings['schema_howto'])) {
        $howto = pse_schema_howto($post_id);
        if (is_array($howto)) {
            $graphs[] = $howto;
        }
    }

    if (!empty($settings['schema_faq'])) {
        $faq = pse_schema_faq($post_id);
        if (is_array($faq)) {
            $graphs[] = $faq;
        }
    }

    if (!empty($settings['schema_breadcrumb'])) {
        $crumb = pse_schema_breadcrumb($post_id);
        if (is_array($crumb)) {
            $graphs[] = $crumb;
        }
    }

    foreach ($graphs as $graph) {
        echo '<script type="application/ld+json">'
            . wp_json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>' . "\n";
    }
}, 12);
