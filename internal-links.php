<?php
if (!defined('ABSPATH')) {
    exit;
}

function pse_stopwords_pl(): array
{
    return [
        'oraz','czyli','albo','jako','jest','sÄ…','dla','ten','ta','to','jak','ktĂłry','ktĂłra','ktĂłre',
        'przez','oraz','takĹĽe','wraz','pod','nad','bez','aby','czy','siÄ™','nie','na','do','od','za','po',
        'of','and','the','for','with','from','into','how','to','in','on','a','an','is','are',
    ];
}

function pse_extract_keywords_from_text(string $text, int $limit = 12): array
{
    $text = mb_strtolower(wp_strip_all_tags($text), 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text);
    $parts = preg_split('/\s+/u', (string) $text, -1, PREG_SPLIT_NO_EMPTY);

    $stopwords = array_flip(pse_stopwords_pl());
    $freq = [];

    foreach ((array) $parts as $word) {
        $word = trim($word);
        if (mb_strlen($word, 'UTF-8') < 3) {
            continue;
        }
        if (isset($stopwords[$word])) {
            continue;
        }
        $freq[$word] = ($freq[$word] ?? 0) + 1;
    }

    arsort($freq);

    return array_slice(array_keys($freq), 0, $limit);
}

function pse_extract_primary_keyword(int $post_id): string
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return '';
    }

    $headers = [];
    if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', (string) $post->post_content, $m)) {
        $headers = array_map('wp_strip_all_tags', $m[1]);
    }

    $text = implode("\n", [
        (string) $post->post_title,
        implode("\n", $headers),
        (string) $post->post_content,
    ]);

    $keywords = pse_extract_keywords_from_text($text, 10);

    return (string) ($keywords[0] ?? '');
}

function pse_extract_post_keywords(int $post_id): array
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return [];
    }

    $headers = [];
    if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', (string) $post->post_content, $m)) {
        $headers = array_map('wp_strip_all_tags', $m[1]);
    }

    $text = implode("\n", [
        (string) $post->post_title,
        implode("\n", $headers),
        (string) $post->post_content,
    ]);

    $keywords = pse_extract_keywords_from_text($text, 14);
    $primary = (string) ($keywords[0] ?? '');

    update_post_meta($post_id, '_pse_primary_keyword', $primary);
    update_post_meta($post_id, '_pse_keywords', $keywords);

    return $keywords;
}

function pse_find_internal_link_targets(int $post_id, int $limit = 5): array
{
    $settings = function_exists('pse_get_settings') ? pse_get_settings() : ['multilang_enabled' => 0];
    $lang = function_exists('pse_get_post_lang') ? pse_get_post_lang($post_id) : 'pl';

    $cache_key = 'pse_targets_' . $post_id . '_' . $lang . '_' . $limit;
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $keywords = (array) get_post_meta($post_id, '_pse_keywords', true);
    if (empty($keywords)) {
        $keywords = pse_extract_post_keywords($post_id);
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'post__not_in' => [$post_id],
        'posts_per_page' => 120,
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);

    $scores = [];
    foreach ((array) $query->posts as $candidate_id) {
        $candidate_id = (int) $candidate_id;

        if (!empty($settings['multilang_enabled']) && function_exists('pse_get_post_lang')) {
            if (pse_get_post_lang($candidate_id) !== $lang) {
                continue;
            }
        }

        $candidate_title = mb_strtolower((string) get_the_title($candidate_id), 'UTF-8');
        $candidate_kw = (array) get_post_meta($candidate_id, '_pse_keywords', true);
        if (empty($candidate_kw)) {
            $candidate_kw = pse_extract_post_keywords($candidate_id);
        }

        $score = 0;
        foreach ($keywords as $kw) {
            $kw = (string) $kw;
            if ($kw === '') {
                continue;
            }

            if (mb_strpos($candidate_title, $kw, 0, 'UTF-8') !== false) {
                $score += 3;
            }
            if (in_array($kw, $candidate_kw, true)) {
                $score += 2;
            }
        }

        if ($score > 0) {
            $scores[$candidate_id] = $score;
        }
    }

    arsort($scores);
    $ids = array_slice(array_keys($scores), 0, max(3, min(5, $limit)));

    set_transient($cache_key, $ids, 6 * HOUR_IN_SECONDS);

    return $ids;
}

function pse_rebuild_internal_link_map(): array
{
    $settings = function_exists('pse_get_settings') ? pse_get_settings() : ['internal_links_count' => 5];
    $outgoing_target = max(3, min(5, (int) ($settings['internal_links_count'] ?? 5)));

    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    $map = [];

    foreach ((array) $posts as $post_id) {
        $post_id = (int) $post_id;
        $outgoing = pse_find_internal_link_targets($post_id, $outgoing_target);

        $map[$post_id] = [
            'outgoing' => array_slice(array_values(array_unique(array_map('intval', $outgoing))), 0, 5),
            'incoming' => [],
        ];
    }

    foreach ($map as $source_id => $data) {
        foreach ((array) $data['outgoing'] as $target_id) {
            if (!isset($map[$target_id])) {
                continue;
            }
            $map[$target_id]['incoming'][] = (int) $source_id;
        }
    }

    foreach ($map as $post_id => $data) {
        $incoming = array_values(array_unique(array_map('intval', (array) $data['incoming'])));
        $map[$post_id]['incoming'] = array_slice($incoming, 0, 5);
    }

    update_option(PSE_LINK_MAP_OPTION, $map, false);

    return $map;
}

function pse_build_link_health_report(int $limit = 60): array
{
    $map = get_option(PSE_LINK_MAP_OPTION, []);
    if (!is_array($map)) {
        $map = [];
    }

    $weak_out = [];
    $weak_in = [];

    foreach ($map as $post_id => $row) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_status($post_id) !== 'publish') {
            continue;
        }

        $out = count((array) ($row['outgoing'] ?? []));
        $in = count((array) ($row['incoming'] ?? []));

        $item = [
            'post_id' => $post_id,
            'title' => get_the_title($post_id),
            'lang' => function_exists('pse_get_post_lang') ? pse_get_post_lang($post_id) : 'pl',
            'outgoing' => $out,
            'incoming' => $in,
            'edit_url' => get_edit_post_link($post_id, 'raw') ?: '',
            'view_url' => get_permalink($post_id),
        ];

        if ($out < 3) {
            $weak_out[] = $item;
        }
        if ($in < 3) {
            $weak_in[] = $item;
        }
    }

    usort($weak_out, static fn($a, $b) => ($a['outgoing'] <=> $b['outgoing']));
    usort($weak_in, static fn($a, $b) => ($a['incoming'] <=> $b['incoming']));

    $report = [
        'generated_at' => current_time('mysql'),
        'totals' => [
            'posts_in_map' => count($map),
            'weak_out_count' => count($weak_out),
            'weak_in_count' => count($weak_in),
        ],
        'weak_out' => array_slice($weak_out, 0, $limit),
        'weak_in' => array_slice($weak_in, 0, $limit),
    ];

    update_option('pse_link_health_report', $report, false);

    return $report;
}

function pse_get_link_health_report(bool $force_refresh = false): array
{
    $report = get_option('pse_link_health_report', []);
    if ($force_refresh || !is_array($report) || empty($report['generated_at'])) {
        return pse_build_link_health_report();
    }

    return $report;
}

function pse_autofix_weak_outgoing_links(int $minOutgoing = 3): array
{
    $minOutgoing = max(1, min(5, $minOutgoing));

    $map = get_option(PSE_LINK_MAP_OPTION, []);
    if (!is_array($map) || empty($map)) {
        $map = pse_rebuild_internal_link_map();
    }

    $fixed_posts = 0;
    $added_links = 0;

    foreach ($map as $post_id => $row) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_status($post_id) !== 'publish') {
            continue;
        }

        $outgoing = array_values(array_unique(array_map('intval', (array) ($row['outgoing'] ?? []))));
        $current = count($outgoing);

        if ($current >= $minOutgoing) {
            continue;
        }

        $need = $minOutgoing - $current;
        $candidates = pse_find_internal_link_targets($post_id, 5);

        if (empty($candidates)) {
            continue;
        }

        $before = count($outgoing);
        foreach ($candidates as $candidate_id) {
            $candidate_id = (int) $candidate_id;
            if ($candidate_id <= 0 || $candidate_id === $post_id) {
                continue;
            }
            if (!in_array($candidate_id, $outgoing, true)) {
                $outgoing[] = $candidate_id;
                if (count($outgoing) >= 5 || (count($outgoing) - $before) >= $need) {
                    break;
                }
            }
        }

        $outgoing = array_slice(array_values(array_unique($outgoing)), 0, 5);
        $added = count($outgoing) - $before;
        if ($added > 0) {
            $map[$post_id]['outgoing'] = $outgoing;
            $fixed_posts++;
            $added_links += $added;
        }
    }

    foreach ($map as $id => $row) {
        $map[$id]['incoming'] = [];
    }

    foreach ($map as $source_id => $row) {
        foreach ((array) ($row['outgoing'] ?? []) as $target_id) {
            $target_id = (int) $target_id;
            if (isset($map[$target_id])) {
                $map[$target_id]['incoming'][] = (int) $source_id;
            }
        }
    }

    foreach ($map as $id => $row) {
        $incoming = array_values(array_unique(array_map('intval', (array) ($row['incoming'] ?? []))));
        $map[$id]['incoming'] = array_slice($incoming, 0, 5);
    }

    update_option(PSE_LINK_MAP_OPTION, $map, false);
    pse_build_link_health_report();

    return [
        'fixed_posts' => $fixed_posts,
        'added_links' => $added_links,
    ];
}

function pse_autofix_weak_outgoing_links_preview(int $minOutgoing = 3, int $limit = 80): array
{
    $minOutgoing = max(1, min(5, $minOutgoing));
    $limit = max(1, min(200, $limit));

    $map = get_option(PSE_LINK_MAP_OPTION, []);
    if (!is_array($map) || empty($map)) {
        $map = pse_rebuild_internal_link_map();
    }

    $rows = [];
    $affected_posts = 0;
    $planned_links = 0;

    foreach ($map as $post_id => $row) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_status($post_id) !== 'publish') {
            continue;
        }

        $outgoing = array_values(array_unique(array_map('intval', (array) ($row['outgoing'] ?? []))));
        $current = count($outgoing);

        if ($current >= $minOutgoing) {
            continue;
        }

        $need = $minOutgoing - $current;
        $candidates = pse_find_internal_link_targets($post_id, 5);
        $to_add = [];

        foreach ($candidates as $candidate_id) {
            $candidate_id = (int) $candidate_id;
            if ($candidate_id <= 0 || $candidate_id === $post_id) {
                continue;
            }
            if (!in_array($candidate_id, $outgoing, true) && !in_array($candidate_id, $to_add, true)) {
                $to_add[] = $candidate_id;
                if (count($to_add) >= $need) {
                    break;
                }
            }
        }

        if (!empty($to_add)) {
            $affected_posts++;
            $planned_links += count($to_add);

            $rows[] = [
                'post_id' => $post_id,
                'title' => get_the_title($post_id),
                'lang' => function_exists('pse_get_post_lang') ? pse_get_post_lang($post_id) : 'pl',
                'current_outgoing' => $current,
                'planned_add_count' => count($to_add),
                'planned_targets' => $to_add,
                'edit_url' => get_edit_post_link($post_id, 'raw') ?: '',
            ];

            if (count($rows) >= $limit) {
                break;
            }
        }
    }

    return [
        'generated_at' => current_time('mysql'),
        'affected_posts' => $affected_posts,
        'planned_links' => $planned_links,
        'rows' => $rows,
    ];
}

add_action(PSE_LINK_MAP_EVENT, function (): void {
    pse_rebuild_internal_link_map();
    pse_build_link_health_report();
});

add_action('save_post_post', function (int $post_id): void {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    pse_extract_post_keywords($post_id);

    delete_transient('pse_targets_' . $post_id . '_pl_3');
    delete_transient('pse_targets_' . $post_id . '_pl_4');
    delete_transient('pse_targets_' . $post_id . '_pl_5');

    pse_rebuild_internal_link_map();
    pse_build_link_health_report();
}, 30);

function pse_get_internal_links_for_post(int $post_id): array
{
    $settings = function_exists('pse_get_settings') ? pse_get_settings() : ['internal_links_count' => 5];
    $limit = max(3, min(5, (int) ($settings['internal_links_count'] ?? 5)));

    $map = get_option(PSE_LINK_MAP_OPTION, []);
    if (!is_array($map) || empty($map[$post_id]['outgoing'])) {
        $targets = pse_find_internal_link_targets($post_id, $limit);
    } else {
        $targets = array_slice((array) $map[$post_id]['outgoing'], 0, $limit);
    }

    $links = [];
    foreach ($targets as $target_id) {
        $target_id = (int) $target_id;
        if ($target_id <= 0 || get_post_status($target_id) !== 'publish') {
            continue;
        }
        $links[] = [
            'id' => $target_id,
            'title' => get_the_title($target_id),
            'url' => get_permalink($target_id),
        ];
    }

    return $links;
}

function pse_clear_internal_links_cache(): void
{
    global $wpdb;

    $like = $wpdb->esc_like('_transient_pse_targets_') . '%';
    $like_timeout = $wpdb->esc_like('_transient_timeout_pse_targets_') . '%';

    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $like,
        $like_timeout
    ));
}

function pse_reindex_all_keywords(): int
{
    $post_ids = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    $count = 0;
    foreach ((array) $post_ids as $post_id) {
        pse_extract_post_keywords((int) $post_id);
        $count++;
    }

    pse_clear_internal_links_cache();

    return $count;
}

function pse_get_link_map_stats(): array
{
    $map = get_option(PSE_LINK_MAP_OPTION, []);
    if (!is_array($map)) {
        $map = [];
    }

    $posts = count($map);
    $out_total = 0;
    $in_total = 0;
    $weak_out = 0;
    $weak_in = 0;

    foreach ($map as $row) {
        $out = count((array) ($row['outgoing'] ?? []));
        $in = count((array) ($row['incoming'] ?? []));

        $out_total += $out;
        $in_total += $in;

        if ($out < 3) {
            $weak_out++;
        }
        if ($in < 3) {
            $weak_in++;
        }
    }

    return [
        'posts' => $posts,
        'out_avg' => $posts > 0 ? round($out_total / $posts, 2) : 0,
        'in_avg' => $posts > 0 ? round($in_total / $posts, 2) : 0,
        'weak_out' => $weak_out,
        'weak_in' => $weak_in,
        'updated' => current_time('mysql'),
    ];
}

add_filter('the_content', function (string $content): string {
    if (!is_single() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $settings = function_exists('pse_get_settings') ? pse_get_settings() : ['internal_linking_enabled' => 1];
    if (empty($settings['internal_linking_enabled'])) {
        return $content;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return $content;
    }

    $links = pse_get_internal_links_for_post($post_id);
    if (empty($links)) {
        return $content;
    }

    $items = [];
    foreach ($links as $link) {
        $items[] = '<li><a href="' . esc_url((string) $link['url']) . '">' . esc_html((string) $link['title']) . '</a></li>';
    }

    $html = '<section class="pse-internal-links" aria-label="Internal links" style="margin:1.25rem 0;padding:1rem;border:1px solid #e5e7eb;border-radius:8px">'
        . '<h2 style="margin:0 0 .6rem">' . esc_html__('Przydatne linki wewnÄ™trzne', 'peartree-pro-seo-engine') . '</h2>'
        . '<ul style="margin:0;padding-left:1.2rem">' . implode('', $items) . '</ul>'
        . '</section>';

    return $content . $html;
}, 25);

