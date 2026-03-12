<?php
/**
 * Plugin Name: peartree.pro SEO Engine
 * Description: Zintegrowany silnik SEO i generator treĹ›ci (poradniki, evergreen, newsy) dla peartree.pro.
 * Version: 1.1.0
 * Author: peartree.pro
 * Text Domain: peartree-pro-seo-engine
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PSE_VERSION', '1.1.0');
define('PSE_PATH', plugin_dir_path(__FILE__));
define('PSE_URL', plugin_dir_url(__FILE__));
define('PSE_BRAND_NAME', 'peartree.pro');

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('peartree-pro-seo-engine', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 5);

define('PSE_OPTION_KEY', 'pse_settings');
define('PSE_LINK_MAP_OPTION', 'pse_internal_link_map');
define('PSE_TRANSLATION_GROUP_META', '_pse_translation_group');
define('PSE_SOURCE_POST_META', '_pse_source_post');
define('PSE_LANG_META', '_pse_lang');
define('PSE_TOPIC_META', '_pse_topic');

define('PSE_LINK_MAP_EVENT', 'pse_rebuild_link_map_event');
define('PSE_CONTENT_GENERATOR_EVENT', 'pse_content_generator_event');
define('PSE_CONTENT_GENERATOR_PORADNIK_EVENT', 'pse_content_generator_poradnik_event');
define('PSE_CONTENT_GENERATOR_EVERGREEN_EVENT', 'pse_content_generator_evergreen_event');
define('PSE_CONTENT_GENERATOR_NEWS_EVENT', 'pse_content_generator_news_event');
define('PSE_REWRITE_VERSION', '2026-03-11-1');

function pse_languages(): array
{
    return [
        'pl' => 'Polski',
        'en' => 'English',
        'de' => 'Deutsch',
        'es' => 'EspaĂ±ol',
        'fr' => 'FranĂ§ais',
    ];
}

function pse_default_language(): string
{
    return 'pl';
}

function pse_format_pln(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' PLN';
}

function pse_get_post_topic(int $post_id): string
{
    $topic = (string) get_post_meta($post_id, PSE_TOPIC_META, true);
    if ($topic !== '') {
        return $topic;
    }

    return (string) get_post_meta($post_id, '_poradnik_master_topic', true);
}

add_filter('cron_schedules', function (array $schedules): array {
    if (!isset($schedules['pse_every_4_hours'])) {
        $schedules['pse_every_4_hours'] = [
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => 'Every 4 hours (peartree.pro SEO Engine)',
        ];
    }

    if (!isset($schedules['pse_weekly'])) {
        $schedules['pse_weekly'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display' => 'Weekly (peartree.pro SEO Engine)',
        ];
    }

    return $schedules;
});

function pse_ensure_content_generator_schedules(): void
{
    if (!wp_next_scheduled(PSE_CONTENT_GENERATOR_PORADNIK_EVENT)) {
        wp_schedule_event(time() + (2 * HOUR_IN_SECONDS), 'daily', PSE_CONTENT_GENERATOR_PORADNIK_EVENT);
    }

    if (!wp_next_scheduled(PSE_CONTENT_GENERATOR_EVERGREEN_EVENT)) {
        wp_schedule_event(time() + (3 * HOUR_IN_SECONDS), 'pse_weekly', PSE_CONTENT_GENERATOR_EVERGREEN_EVENT);
    }

    if (!wp_next_scheduled(PSE_CONTENT_GENERATOR_NEWS_EVENT)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'pse_every_4_hours', PSE_CONTENT_GENERATOR_NEWS_EVENT);
    }
}

function pse_clear_content_generator_schedules(): void
{
    $events = [
        PSE_CONTENT_GENERATOR_EVENT,
        PSE_CONTENT_GENERATOR_PORADNIK_EVENT,
        PSE_CONTENT_GENERATOR_EVERGREEN_EVENT,
        PSE_CONTENT_GENERATOR_NEWS_EVENT,
    ];

    foreach ($events as $event) {
        $timestamp = wp_next_scheduled($event);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $event);
        }
    }
}

function pse_content_schedule_sla_states(): array
{
    $now = time();

    $evaluate = static function (int $timestamp, int $grace) use ($now): string {
        if ($timestamp <= 0) {
            return 'missing';
        }

        if ($now > ($timestamp + $grace)) {
            return 'delayed';
        }

        if ($now > $timestamp) {
            return 'running';
        }

        return 'ok';
    };

    return [
        'poradnik' => $evaluate((int) wp_next_scheduled(PSE_CONTENT_GENERATOR_PORADNIK_EVENT), 12 * HOUR_IN_SECONDS),
        'evergreen' => $evaluate((int) wp_next_scheduled(PSE_CONTENT_GENERATOR_EVERGREEN_EVENT), 2 * DAY_IN_SECONDS),
        'news' => $evaluate((int) wp_next_scheduled(PSE_CONTENT_GENERATOR_NEWS_EVENT), 2 * HOUR_IN_SECONDS),
    ];
}

function pse_is_quiet_hours_now(array $settings): bool
{
    if (empty($settings['sla_quiet_hours_enabled'])) {
        return false;
    }

    $start = max(0, min(23, (int) ($settings['sla_quiet_hours_start'] ?? 22)));
    $end = max(0, min(23, (int) ($settings['sla_quiet_hours_end'] ?? 7)));
    $hour = (int) wp_date('G');

    if ($start === $end) {
        return true;
    }

    if ($start < $end) {
        return $hour >= $start && $hour < $end;
    }

    return $hour >= $start || $hour < $end;
}

add_action('admin_notices', function (): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = pse_get_settings();
    if (pse_is_quiet_hours_now($settings)) {
        return;
    }

    $states = pse_content_schedule_sla_states();
    $delayed = [];

    foreach ($states as $mode => $state) {
        if ($state === 'delayed' || $state === 'missing') {
            $delayed[] = strtoupper($mode);
        }
    }

    if (empty($delayed)) {
        return;
    }

    $url = admin_url('admin.php?page=peartree-pro-seo-engine');
    echo '<div class="notice notice-warning is-dismissible"><p>'
        . esc_html('peartree.pro SEO Engine: opĂłĹşnione harmonogramy generatora: ' . implode(', ', $delayed) . '.')
        . ' <a href="' . esc_url($url) . '">' . esc_html__('OtwĂłrz dashboard', 'peartree-pro-seo-engine') . '</a>'
        . '</p></div>';
});

function pse_is_theme_schema_active(): bool
{
    return function_exists('poradnik_pro_build_schema_graph');
}

function pse_is_theme_ratings_active(): bool
{
    return function_exists('poradnik_pro_rating_stats') || function_exists('poradnik_pro_ratings_html');
}

function pse_should_prepend_hero_image(int $post_id, string $content, string $image_url): bool
{
    if ($post_id <= 0 || $image_url === '') {
        return false;
    }

    if (strpos($content, 'class="pse-generated-hero"') !== false) {
        return false;
    }

    if (strpos($content, $image_url) !== false) {
        return false;
    }

    $head = substr(ltrim($content), 0, 900);
    if (preg_match('/<img\\b[^>]*>/i', $head) === 1) {
        return false;
    }

    return true;
}

function pse_default_settings(): array
{
    return [
        'internal_linking_enabled' => 1,
        'internal_links_count' => 5,
        'related_enabled' => 1,
        'breadcrumbs_enabled' => 1,
        'schema_article' => 1,
        'schema_howto' => 1,
        'schema_faq' => 1,
        'schema_breadcrumb' => 1,
        'multilang_enabled' => 1,
        'auto_translate_enabled' => 1,
        'content_generator_enabled' => 1,
        'content_generation_mode' => 'mixed',
        'content_daily_count' => 2,
        'content_poradnik_per_run' => 2,
        'content_evergreen_per_run' => 2,
        'content_news_per_run' => 1,
        'content_retry_attempts' => 2,
        'content_recent_topics_window' => 25,
        'content_hourly_limit' => 10,
        'sla_quiet_hours_enabled' => 1,
        'sla_quiet_hours_start' => 22,
        'sla_quiet_hours_end' => 7,
        'content_post_status' => 'draft',
        'content_preferred_category_id' => 0,
        'image_generator_enabled' => 1,
        'image_step_inject_enabled' => 1,
        'image_step_count' => 3,
        'image_model' => 'gpt-image-1',
        'translator_api_key' => '',
        'translator_model' => 'gpt-5-mini',
        'content_poradnik_custom_topics' => '',
        'cron_use_custom_topics_first'    => 1,
    ];
}

function pse_get_settings(): array
{
    $settings = get_option(PSE_OPTION_KEY, []);

    return wp_parse_args(is_array($settings) ? $settings : [], pse_default_settings());
}

function pse_sanitize_settings(array $input): array
{
    $current = pse_get_settings();

    $api_key = isset($input['translator_api_key']) ? trim((string) $input['translator_api_key']) : '';
    if ($api_key === '') {
        $api_key = (string) ($current['translator_api_key'] ?? '');
    }

    $model = sanitize_text_field((string) ($input['translator_model'] ?? 'gpt-5-mini'));
    if ($model === '') {
        $model = 'gpt-5-mini';
    }

    $preferred_category_id = (int) ($input['content_preferred_category_id'] ?? 0);
    if ($preferred_category_id > 0) {
        $preferred_term = get_term($preferred_category_id, 'category');
        if (!$preferred_term instanceof WP_Term) {
            $preferred_category_id = 0;
        }
    }

    return [
        'internal_linking_enabled' => empty($input['internal_linking_enabled']) ? 0 : 1,
        'internal_links_count' => max(3, min(5, (int) ($input['internal_links_count'] ?? 5))),
        'related_enabled' => empty($input['related_enabled']) ? 0 : 1,
        'breadcrumbs_enabled' => empty($input['breadcrumbs_enabled']) ? 0 : 1,
        'schema_article' => empty($input['schema_article']) ? 0 : 1,
        'schema_howto' => empty($input['schema_howto']) ? 0 : 1,
        'schema_faq' => empty($input['schema_faq']) ? 0 : 1,
        'schema_breadcrumb' => empty($input['schema_breadcrumb']) ? 0 : 1,
        'multilang_enabled' => empty($input['multilang_enabled']) ? 0 : 1,
        'auto_translate_enabled' => empty($input['auto_translate_enabled']) ? 0 : 1,
        'content_generator_enabled' => empty($input['content_generator_enabled']) ? 0 : 1,
        'content_generation_mode' => in_array((string) ($input['content_generation_mode'] ?? 'mixed'), ['poradnik', 'evergreen', 'news', 'mixed'], true)
            ? (string) $input['content_generation_mode']
            : 'mixed',
        'content_daily_count' => max(1, min(12, (int) ($input['content_daily_count'] ?? 2))),
        'content_poradnik_per_run' => max(1, min(6, (int) ($input['content_poradnik_per_run'] ?? 2))),
        'content_evergreen_per_run' => max(1, min(6, (int) ($input['content_evergreen_per_run'] ?? 2))),
        'content_news_per_run' => max(1, min(6, (int) ($input['content_news_per_run'] ?? 1))),
        'content_retry_attempts' => max(1, min(5, (int) ($input['content_retry_attempts'] ?? 2))),
        'content_recent_topics_window' => max(10, min(200, (int) ($input['content_recent_topics_window'] ?? 25))),
        'content_hourly_limit' => max(1, min(100, (int) ($input['content_hourly_limit'] ?? 10))),
        'sla_quiet_hours_enabled' => empty($input['sla_quiet_hours_enabled']) ? 0 : 1,
        'sla_quiet_hours_start' => max(0, min(23, (int) ($input['sla_quiet_hours_start'] ?? 22))),
        'sla_quiet_hours_end' => max(0, min(23, (int) ($input['sla_quiet_hours_end'] ?? 7))),
        'content_post_status' => in_array((string) ($input['content_post_status'] ?? 'draft'), ['draft', 'publish'], true)
            ? (string) $input['content_post_status']
            : 'draft',
        'content_preferred_category_id' => $preferred_category_id,
        'image_generator_enabled' => empty($input['image_generator_enabled']) ? 0 : 1,
        'image_step_inject_enabled' => empty($input['image_step_inject_enabled']) ? 0 : 1,
        'image_step_count' => max(1, min(6, (int) ($input['image_step_count'] ?? 3))),
        'image_model' => sanitize_text_field((string) ($input['image_model'] ?? 'gpt-image-1')),
        'translator_api_key' => sanitize_text_field($api_key),
        'translator_model' => $model,
        'content_poradnik_custom_topics' => sanitize_textarea_field((string) ($input['content_poradnik_custom_topics'] ?? '')),
        'cron_use_custom_topics_first'    => empty($input['cron_use_custom_topics_first']) ? 0 : 1,
    ];
}

require_once PSE_PATH . 'internal-links.php';
require_once PSE_PATH . 'schema.php';
require_once PSE_PATH . 'meta.php';
require_once PSE_PATH . 'breadcrumbs.php';
require_once PSE_PATH . 'related.php';

register_activation_hook(__FILE__, function (): void {
    if (get_option(PSE_OPTION_KEY, null) === null) {
        add_option(PSE_OPTION_KEY, pse_default_settings());
    }

    if (!wp_next_scheduled(PSE_LINK_MAP_EVENT)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', PSE_LINK_MAP_EVENT);
    }

    pse_ensure_content_generator_schedules();

    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function (): void {
    $timestamp = wp_next_scheduled(PSE_LINK_MAP_EVENT);
    if ($timestamp) {
        wp_unschedule_event($timestamp, PSE_LINK_MAP_EVENT);
    }

    pse_clear_content_generator_schedules();

    flush_rewrite_rules();
});

add_action('init', function (): void {
    add_rewrite_tag('%pse_lang%', '(pl|en|de|es|fr)');
    add_rewrite_rule('^(pl|en|de|es|fr)/?$', 'index.php?post_type=post&pse_lang=$matches[1]', 'top');
    add_rewrite_rule('^(pl|en|de|es|fr)/page/([0-9]{1,})/?$', 'index.php?post_type=post&pse_lang=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^(pl|en|de|es|fr)/([^/]+)/?$', 'index.php?post_type=post&name=$matches[2]&pse_lang=$matches[1]', 'top');

    $rewrite_version = (string) get_option('pse_rewrite_version', '');
    if ($rewrite_version !== PSE_REWRITE_VERSION) {
        flush_rewrite_rules(false);
        update_option('pse_rewrite_version', PSE_REWRITE_VERSION, false);
    }

    if (!wp_next_scheduled(PSE_LINK_MAP_EVENT)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', PSE_LINK_MAP_EVENT);
    }

    pse_ensure_content_generator_schedules();
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pse_lang';
    return $vars;
});

add_action('pre_get_posts', function (WP_Query $query): void {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    $lang = (string) $query->get('pse_lang');
    if ($lang === '' || !array_key_exists($lang, pse_languages())) {
        return;
    }

    $meta_query = (array) $query->get('meta_query');

    if ($lang === pse_default_language()) {
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => PSE_LANG_META,
                'value' => $lang,
                'compare' => '=',
            ],
            [
                'key' => PSE_LANG_META,
                'compare' => 'NOT EXISTS',
            ],
        ];
    } else {
        $meta_query[] = [
            'key' => PSE_LANG_META,
            'value' => $lang,
            'compare' => '=',
        ];
    }

    $query->set('meta_query', $meta_query);
});

function pse_get_post_lang(int $post_id): string
{
    $lang = (string) get_post_meta($post_id, PSE_LANG_META, true);
    if ($lang === '' || !array_key_exists($lang, pse_languages())) {
        return pse_default_language();
    }

    return $lang;
}

function pse_set_post_lang(int $post_id, string $lang): void
{
    if (!array_key_exists($lang, pse_languages())) {
        $lang = pse_default_language();
    }

    update_post_meta($post_id, PSE_LANG_META, $lang);
}

function pse_get_translation_group(int $post_id): string
{
    $group = (string) get_post_meta($post_id, PSE_TRANSLATION_GROUP_META, true);
    if ($group === '') {
        $group = 'grp_' . $post_id;
        update_post_meta($post_id, PSE_TRANSLATION_GROUP_META, $group);
    }

    return $group;
}

function pse_get_group_posts(string $group): array
{
    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => ['publish', 'draft', 'future', 'pending', 'private'],
        'fields' => 'ids',
        'posts_per_page' => 50,
        'meta_key' => PSE_TRANSLATION_GROUP_META,
        'meta_value' => $group,
        'no_found_rows' => true,
    ]);

    return array_map('intval', (array) $query->posts);
}

function pse_get_translation_for_lang(string $group, string $lang): int
{
    foreach (pse_get_group_posts($group) as $post_id) {
        if (pse_get_post_lang($post_id) === $lang) {
            return $post_id;
        }
    }

    return 0;
}

function pse_translate_text(string $text, string $from, string $to): string
{
    $text = trim($text);
    if ($text === '' || $from === $to) {
        return $text;
    }

    $settings = pse_get_settings();
    $api_key = (string) ($settings['translator_api_key'] ?? '');

    if ($api_key === '') {
        return '[' . strtoupper($to) . '] ' . $text;
    }

    $model = (string) ($settings['translator_model'] ?? 'gpt-5-mini');

    $request = [
        'model' => $model,
        'input' => [
            [
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => 'JesteĹ› tĹ‚umaczem SEO. TĹ‚umacz treĹ›Ä‡ naturalnie, bez dopiskĂłw i bez markdown.',
                    ],
                ],
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => sprintf('PrzetĹ‚umacz z %s na %s:\n\n%s', strtoupper($from), strtoupper($to), $text),
                    ],
                ],
            ],
        ],
        'max_output_tokens' => 1200,
    ];

    $response = wp_remote_post('https://api.openai.com/v1/responses', [
        'timeout' => 60,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($request),
    ]);

    if (is_wp_error($response)) {
        return '[' . strtoupper($to) . '] ' . $text;
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    if ($status < 200 || $status >= 300) {
        return '[' . strtoupper($to) . '] ' . $text;
    }

    $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($decoded)) {
        return '[' . strtoupper($to) . '] ' . $text;
    }

    $output = '';
    if (!empty($decoded['output_text']) && is_string($decoded['output_text'])) {
        $output = $decoded['output_text'];
    } elseif (!empty($decoded['output']) && is_array($decoded['output'])) {
        foreach ($decoded['output'] as $entry) {
            if (empty($entry['content']) || !is_array($entry['content'])) {
                continue;
            }
            foreach ($entry['content'] as $piece) {
                if (!empty($piece['text']) && is_string($piece['text'])) {
                    $output .= $piece['text'] . "\n";
                }
            }
        }
    }

    $output = trim(wp_strip_all_tags($output, true));

    return $output !== '' ? $output : '[' . strtoupper($to) . '] ' . $text;
}

function pse_content_mode_labels(): array
{
    return [
        'poradnik' => 'Poradniki',
        'evergreen' => 'Evergreen',
        'news' => 'Newsy',
        'mixed' => 'Mix',
    ];
}

function pse_pick_generation_mode(string $selected_mode): string
{
    if ($selected_mode !== 'mixed') {
        return $selected_mode;
    }

    $pool = ['poradnik', 'evergreen', 'news'];
    return $pool[array_rand($pool)];
}

function pse_topic_pool(string $mode): array
{
    $month = (string) wp_date('F Y');

    if ($mode === 'news') {
        return [
            'Aktualizacja narzÄ™dzi AI SEO na ' . $month,
            'Zmiany w wyszukiwarce i ich wpĹ‚yw na treĹ›ci poradnikowe',
            'Nowe trendy content marketingu dla serwisĂłw poradnikowych',
            'Szybki raport: co dziaĹ‚a w SEO w tym tygodniu',
            'NajwaĹĽniejsze nowoĹ›ci WordPress dla wydawcĂłw treĹ›ci',
        ];
    }

    if ($mode === 'evergreen') {
        return [
            'Kompletny przewodnik po planowaniu budĹĽetu domowego',
            'Jak skutecznie organizowaÄ‡ dzieĹ„ pracy krok po kroku',
            'Najlepsze praktyki bezpieczeĹ„stwa kont online',
            'Jak dbaÄ‡ o sprzÄ™t domowy, aby dziaĹ‚aĹ‚ dĹ‚uĹĽej',
            'Nawyki, ktĂłre uĹ‚atwiajÄ… codzienne zarzÄ…dzanie czasem',
        ];
    }

    $builtin_poradnik = [
        'Jak rozwiÄ…zaÄ‡ typowy problem domowy krok po kroku',
        'Jak zoptymalizowaÄ‡ telefon i przyspieszyÄ‡ dziaĹ‚anie aplikacji',
        'Jak samodzielnie naprawiÄ‡ podstawowe usterki w domu',
        'Jak skonfigurowaÄ‡ bezpiecznÄ… sieÄ‡ Wiâ€‘Fi w mieszkaniu',
        'Jak przygotowaÄ‡ checklistÄ™ dziaĹ‚aĹ„ przed waĹĽnym zakupem',
    ];

    $pse_custom_pool = pse_parse_custom_topic_list((string) (pse_get_settings()['content_poradnik_custom_topics'] ?? ''));
    $pse_use_custom   = !empty($pse_custom_pool) && (int) (pse_get_settings()['cron_use_custom_topics_first'] ?? 1) === 1;
    if ($pse_use_custom) {
        return array_values(array_unique(array_merge($pse_custom_pool, $builtin_poradnik)));
    }

    return $builtin_poradnik;
}

function pse_pick_topic(string $mode, bool $persist = true): string
{
    $topics = pse_topic_pool($mode);
    if (empty($topics)) {
        return 'Praktyczny poradnik dnia';
    }

    $recent = get_option('pse_recent_topics', []);
    if (!is_array($recent)) {
        $recent = [];
    }

    $available = array_values(array_filter($topics, static function (string $topic) use ($recent): bool {
        return !in_array($topic, $recent, true);
    }));

    $picked = !empty($available)
        ? $available[array_rand($available)]
        : $topics[array_rand($topics)];

    if ($persist) {
        $settings = pse_get_settings();
        $window = max(10, min(200, (int) ($settings['content_recent_topics_window'] ?? 25)));

        $recent[] = $picked;
        $recent = array_slice(array_values(array_unique($recent)), -$window);
        update_option('pse_recent_topics', $recent, false);
    }

    return $picked;
}

function pse_parse_custom_topic_list(string $raw): array
{
    if (trim($raw) === '') {
        return [];
    }

    $lines = preg_split('/\r?\n/', $raw) ?: [];
    $topics = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $topics[] = $line;
        }
    }

    return array_values(array_unique($topics));
}

function pse_preview_generation_topics(?int $limit = null, ?string $forced_mode = null): array
{
    $settings = pse_get_settings();
    $count = $limit !== null ? $limit : (int) ($settings['content_daily_count'] ?? 2);
    $count = max(1, min(20, $count));

    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $mode = pse_pick_generation_mode((string) ($forced_mode ?: ($settings['content_generation_mode'] ?? 'mixed')));
        $rows[] = [
            'mode' => $mode,
            'topic' => pse_pick_topic($mode, false),
        ];
    }

    return $rows;
}

function pse_generate_from_topic_list(array $topics, ?array $resolvedSettings = null): array
{
    $settings = is_array($resolvedSettings) ? $resolvedSettings : pse_get_settings();
    $hourly_limit = max(1, min(100, (int) ($settings['content_hourly_limit'] ?? 10)));
    $retry_attempts = max(1, min(5, (int) ($settings['content_retry_attempts'] ?? 2)));
    $results = [
        'requested' => count($topics),
        'created'   => 0,
        'failed'    => 0,
        'failures'  => [],
        'retry_attempts' => $retry_attempts,
    ];

    foreach ($topics as $raw_topic) {
        $topic = trim((string) $raw_topic);
        if ($topic === '') {
            $results['requested']--;
            continue;
        }

        if (pse_count_generated_posts_last_hour() >= $hourly_limit) {
            $results['failed']++;
            $results['failures'][] = ['mode' => 'poradnik', 'message' => 'Limit godzinowy (' . $hourly_limit . '). PominiÄ™ty temat: ' . $topic];
            continue;
        }

        $ok = false;
        for ($attempt = 1; $attempt <= $retry_attempts; $attempt++) {
            $item = pse_generate_single_content_item('poradnik', $settings, $topic);
            if (!empty($item['ok'])) {
                $results['created']++;
                $ok = true;
                break;
            }
            if ($attempt < $retry_attempts) {
                sleep(1);
            }
        }

        if (!$ok) {
            $results['failed']++;
            $results['failures'][] = ['mode' => 'poradnik', 'message' => 'Temat: ' . $topic . ' — wyczerpano próby.'];
        }
    }

    pse_append_content_audit_log([
        'time'      => current_time('mysql'),
        'mode'      => 'poradnik',
        'trigger'   => 'topic_list',
        'requested' => $results['requested'],
        'created'   => $results['created'],
        'failed'    => $results['failed'],
    ]);

    return $results;
}

function pse_get_unused_custom_topics(): array
{
    $settings = pse_get_settings();
    $custom = pse_parse_custom_topic_list((string) ($settings['content_poradnik_custom_topics'] ?? ''));
    if (empty($custom)) {
        return [];
    }

    $recent = get_option('pse_recent_topics', []);
    if (!is_array($recent)) {
        $recent = [];
    }

    return array_values(array_filter($custom, static function (string $t) use ($recent): bool {
        return !in_array($t, $recent, true);
    }));
}

function pse_count_generated_posts_last_hour(): int
{
    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => ['draft', 'publish'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'meta_query' => [
            [
                'key' => '_pse_content_mode',
                'value' => ['poradnik', 'evergreen', 'news'],
                'compare' => 'IN',
            ],
        ],
        'date_query' => [
            [
                'after' => '1 hour ago',
                'inclusive' => true,
            ],
        ],
    ]);

    return (int) $query->found_posts;
}

function pse_extract_json_from_text(string $text): ?array
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }

    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches) !== 1) {
        return null;
    }

    $decoded = json_decode((string) $matches[0], true);
    return is_array($decoded) ? $decoded : null;
}

function pse_build_local_content_payload(string $mode, string $topic): array
{
    $mode_label = (string) (pse_content_mode_labels()[$mode] ?? 'Poradniki');
    $title_prefix = $mode === 'news' ? 'News: ' : ($mode === 'evergreen' ? 'Evergreen: ' : 'Poradnik: ');

    $steps = [
        'Zdefiniuj cel i zakres dziaĹ‚ania.',
        'Przygotuj narzÄ™dzia i materiaĹ‚y potrzebne do realizacji.',
        'Wykonaj kroki w kolejnoĹ›ci i sprawdĹş rezultat.',
    ];

    $tools = ['Notatnik', 'Smartfon lub komputer', 'Lista kontrolna'];
    $tips = ['DziaĹ‚aj etapami.', 'Zapisuj wyniki.', 'Aktualizuj procedurÄ™ po testach.'];
    $faq = [
        ['question' => 'Ile czasu to zajmuje?', 'answer' => 'W wiÄ™kszoĹ›ci przypadkĂłw od 15 do 60 minut.'],
        ['question' => 'Czy potrzebujÄ™ specjalistycznego sprzÄ™tu?', 'answer' => 'Nie, wystarczÄ… podstawowe narzÄ™dzia i checklista.'],
    ];

    $content = '<p>' . esc_html('MateriaĹ‚ typu ' . $mode_label . ' przygotowany dla tematu: ' . $topic . '.') . '</p>'
        . '<h2>Instrukcja krok po kroku</h2>'
        . '<ol><li>' . esc_html($steps[0]) . '</li><li>' . esc_html($steps[1]) . '</li><li>' . esc_html($steps[2]) . '</li></ol>'
        . '<h2>NajwaĹĽniejsze wskazĂłwki</h2>'
        . '<ul><li>' . esc_html($tips[0]) . '</li><li>' . esc_html($tips[1]) . '</li><li>' . esc_html($tips[2]) . '</li></ul>';

    return [
        'title' => $title_prefix . $topic,
        'intro' => 'Praktyczny materiaĹ‚, ktĂłry pomaga szybko rozwiÄ…zaÄ‡ konkretny problem.',
        'steps' => $steps,
        'tools' => $tools,
        'tips' => $tips,
        'faq' => $faq,
        'content' => $content,
    ];
}

function pse_openai_generate_content_payload(string $mode, string $topic): ?array
{
    $settings = pse_get_settings();
    $api_key = (string) ($settings['translator_api_key'] ?? '');
    if ($api_key === '') {
        return null;
    }

    $model = (string) ($settings['translator_model'] ?? 'gpt-5-mini');
    $mode_label = (string) (pse_content_mode_labels()[$mode] ?? 'Poradniki');

    $request = [
        'model' => $model,
        'input' => [
            [
                'role' => 'system',
                'content' => [[
                    'type' => 'input_text',
                    'text' => 'JesteĹ› redaktorem SEO. Zwracasz wyĹ‚Ä…cznie poprawny JSON, bez markdown i bez komentarzy.',
                ]],
            ],
            [
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => 'Przygotuj treĹ›Ä‡ typu: ' . $mode_label . '. Temat: ' . $topic
                        . '. ZwrĂłÄ‡ JSON z kluczami: title (string), intro (string), steps (array max 5), tools (array), tips (array), faq (array obiektĂłw {question,answer}), content (HTML artykuĹ‚u z nagĹ‚Ăłwkami h2).',
                ]],
            ],
        ],
        'max_output_tokens' => 2200,
    ];

    $response = wp_remote_post('https://api.openai.com/v1/responses', [
        'timeout' => 90,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($request),
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    if ($status < 200 || $status >= 300) {
        return null;
    }

    $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($decoded)) {
        return null;
    }

    $text = '';
    if (!empty($decoded['output_text']) && is_string($decoded['output_text'])) {
        $text = $decoded['output_text'];
    } elseif (!empty($decoded['output']) && is_array($decoded['output'])) {
        foreach ($decoded['output'] as $entry) {
            if (empty($entry['content']) || !is_array($entry['content'])) {
                continue;
            }
            foreach ($entry['content'] as $piece) {
                if (!empty($piece['text']) && is_string($piece['text'])) {
                    $text .= $piece['text'] . "\n";
                }
            }
        }
    }

    $payload = pse_extract_json_from_text($text);
    return is_array($payload) ? $payload : null;
}

function pse_ensure_content_category(string $mode): int
{
    $name = $mode === 'news' ? 'Newsy' : ($mode === 'evergreen' ? 'Evergreen' : 'Poradniki');
    $slug = sanitize_title($name);

    $term = get_term_by('slug', $slug, 'category');
    if ($term instanceof WP_Term) {
        return (int) $term->term_id;
    }

    $created = wp_insert_term($name, 'category', ['slug' => $slug]);
    if (is_wp_error($created) || empty($created['term_id'])) {
        return 0;
    }

    return (int) $created['term_id'];
}

function pse_generate_default_categories(): array
{
    $modes = ['poradnik', 'evergreen', 'news'];
    $result = [
        'created' => 0,
        'existing' => 0,
        'errors' => 0,
    ];

    foreach ($modes as $mode) {
        $name = $mode === 'news' ? 'Newsy' : ($mode === 'evergreen' ? 'Evergreen' : 'Poradniki');
        $slug = sanitize_title($name);

        $existing = get_term_by('slug', $slug, 'category');
        if ($existing instanceof WP_Term) {
            $result['existing']++;
            continue;
        }

        $created = wp_insert_term($name, 'category', ['slug' => $slug]);
        if (is_wp_error($created) || empty($created['term_id'])) {
            $result['errors']++;
            continue;
        }

        $result['created']++;
    }

    return $result;
}

function pse_generate_categories_from_input(string $raw_input): array
{
    $parts = preg_split('/[\r\n,;]+/', $raw_input) ?: [];
    $names = [];

    foreach ($parts as $part) {
        $name = sanitize_text_field(trim((string) $part));
        if ($name === '') {
            continue;
        }

        $slug = sanitize_title($name);
        if ($slug === '') {
            continue;
        }

        $names[$slug] = $name;
    }

    $result = [
        'created' => 0,
        'existing' => 0,
        'errors' => 0,
        'processed' => count($names),
    ];

    foreach ($names as $slug => $name) {
        $existing = get_term_by('slug', $slug, 'category');
        if ($existing instanceof WP_Term) {
            $result['existing']++;
            continue;
        }

        $created = wp_insert_term($name, 'category', ['slug' => $slug]);
        if (is_wp_error($created) || empty($created['term_id'])) {
            $result['errors']++;
            continue;
        }

        $result['created']++;
    }

    return $result;
}

function pse_resolve_generation_category_id(string $mode, array $settings = []): int
{
    if (empty($settings)) {
        $settings = pse_get_settings();
    }

    $preferred_category_id = (int) ($settings['content_preferred_category_id'] ?? 0);
    if ($preferred_category_id > 0) {
        $preferred_term = get_term($preferred_category_id, 'category');
        if ($preferred_term instanceof WP_Term) {
            return (int) $preferred_term->term_id;
        }
    }

    return pse_ensure_content_category($mode);
}

function pse_generate_single_content_item(?string $forced_mode = null, ?array $resolvedSettings = null, ?string $forced_topic = null): array
{
    $settings = is_array($resolvedSettings) ? $resolvedSettings : pse_get_settings();
    $mode = pse_pick_generation_mode((string) ($forced_mode ?: ($settings['content_generation_mode'] ?? 'mixed')));
    $topic = ($forced_topic !== null && $forced_topic !== '') ? $forced_topic : pse_pick_topic($mode);

    $payload = pse_openai_generate_content_payload($mode, $topic);
    if (!is_array($payload)) {
        $payload = pse_build_local_content_payload($mode, $topic);
    }

    $title = sanitize_text_field((string) ($payload['title'] ?? ('Poradnik: ' . $topic)));
    if ($title === '') {
        $title = 'Poradnik: ' . $topic;
    }

    $existing = get_page_by_title($title, OBJECT, 'post');
    if ($existing instanceof WP_Post) {
        return ['ok' => false, 'post_id' => (int) $existing->ID, 'mode' => $mode, 'message' => 'Wpis o tym tytule juĹĽ istnieje.'];
    }

    $content = (string) ($payload['content'] ?? '');
    if ($content === '') {
        $content = '<p>' . esc_html('MateriaĹ‚: ' . $topic) . '</p>';
    }

    $status = (string) ($settings['content_post_status'] ?? 'draft');
    if (!in_array($status, ['draft', 'publish'], true)) {
        $status = 'draft';
    }

    $post_id = wp_insert_post([
        'post_type' => 'post',
        'post_status' => $status,
        'post_title' => $title,
        'post_content' => wp_kses_post($content),
        'post_excerpt' => sanitize_text_field((string) ($payload['intro'] ?? '')),
        'post_name' => sanitize_title($title),
    ], true);

    if (is_wp_error($post_id) || (int) $post_id <= 0) {
        return ['ok' => false, 'post_id' => 0, 'mode' => $mode, 'message' => 'Nie udaĹ‚o siÄ™ zapisaÄ‡ wpisu.'];
    }

    $post_id = (int) $post_id;
    $category_id = pse_resolve_generation_category_id($mode, $settings);
    if ($category_id > 0) {
        wp_set_post_categories($post_id, [$category_id], false);
    }

    $steps = is_array($payload['steps'] ?? null) ? array_values(array_filter(array_map('sanitize_text_field', $payload['steps']))) : [];
    $tools = is_array($payload['tools'] ?? null) ? array_values(array_filter(array_map('sanitize_text_field', $payload['tools']))) : [];
    $tips = is_array($payload['tips'] ?? null) ? array_values(array_filter(array_map('sanitize_text_field', $payload['tips']))) : [];

    $faq_rows = [];
    if (!empty($payload['faq']) && is_array($payload['faq'])) {
        foreach ($payload['faq'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $question = sanitize_text_field((string) ($row['question'] ?? ''));
            $answer = sanitize_text_field((string) ($row['answer'] ?? ''));
            if ($question !== '' && $answer !== '') {
                $faq_rows[] = $question . '|' . $answer;
            }
        }
    }

    update_post_meta($post_id, '_poradnik_intro', sanitize_text_field((string) ($payload['intro'] ?? '')));
    update_post_meta($post_id, '_poradnik_steps', implode("\n", $steps));
    update_post_meta($post_id, '_poradnik_tools', implode("\n", $tools));
    update_post_meta($post_id, '_poradnik_tips', implode("\n", $tips));
    update_post_meta($post_id, '_poradnik_faq', implode("\n", $faq_rows));
    update_post_meta($post_id, PSE_TOPIC_META, sanitize_text_field($topic));
    update_post_meta($post_id, '_pse_content_mode', $mode);

    pse_set_post_lang($post_id, 'pl');
    update_post_meta($post_id, PSE_TRANSLATION_GROUP_META, 'grp_' . $post_id);

    if (!empty($settings['image_generator_enabled'])) {
        pse_generate_post_image($post_id, false);
        pse_generate_step_images($post_id, false);
    }

    if ($status === 'publish') {
        if (function_exists('pse_extract_post_keywords')) {
            pse_extract_post_keywords($post_id);
        }

        if (function_exists('pse_rebuild_internal_link_map')) {
            pse_rebuild_internal_link_map();
        }
    }

    return ['ok' => true, 'post_id' => $post_id, 'mode' => $mode, 'message' => 'Wygenerowano wpis.'];
}

function pse_append_content_audit_log(array $entry): void
{
    $log = get_option('pse_content_audit_log', []);
    if (!is_array($log)) {
        $log = [];
    }

    $log[] = [
        'time' => (string) ($entry['time'] ?? current_time('mysql')),
        'mode' => (string) ($entry['mode'] ?? 'mixed'),
        'requested' => (int) ($entry['requested'] ?? 0),
        'created' => (int) ($entry['created'] ?? 0),
        'failed' => (int) ($entry['failed'] ?? 0),
        'trigger' => (string) ($entry['trigger'] ?? 'manual'),
    ];

    if (count($log) > 50) {
        $log = array_slice($log, -50);
    }

    update_option('pse_content_audit_log', array_values($log), false);
}

function pse_get_generator_kpis(int $days = 7): array
{
    $days = max(1, min(30, $days));
    $log = get_option('pse_content_audit_log', []);
    if (!is_array($log)) {
        $log = [];
    }

    $cutoff = time() - ($days * DAY_IN_SECONDS);
    $runs = 0;
    $requested = 0;
    $created = 0;
    $failed = 0;

    foreach ($log as $entry) {
        $timestamp = strtotime((string) ($entry['time'] ?? ''));
        if ($timestamp === false || $timestamp < $cutoff) {
            continue;
        }

        $runs++;
        $requested += (int) ($entry['requested'] ?? 0);
        $created += (int) ($entry['created'] ?? 0);
        $failed += (int) ($entry['failed'] ?? 0);
    }

    $success_rate = $requested > 0 ? round(($created / $requested) * 100, 1) : 0.0;

    return [
        'days' => $days,
        'runs' => $runs,
        'requested' => $requested,
        'created' => $created,
        'failed' => $failed,
        'success_rate' => $success_rate,
    ];
}

function pse_rest_permissions(): bool
{
    return current_user_can('manage_options');
}

function pse_rest_get_status(\WP_REST_Request $request): \WP_REST_Response
{
    unset($request);

    $settings = pse_get_settings();
    $status = [
        'ok' => true,
        'time' => current_time('mysql'),
        'version' => (string) PSE_VERSION,
        'generator_enabled' => !empty($settings['content_generator_enabled']),
        'schedules' => pse_content_schedule_sla_states(),
        'theme_schema_active' => pse_is_theme_schema_active(),
        'theme_ratings_active' => pse_is_theme_ratings_active(),
        'platform_available' => defined('PPP_OPTION_KEY') || function_exists('ppp_render_dashboard'),
    ];

    return new \WP_REST_Response($status, 200);
}

function pse_rest_get_kpis(\WP_REST_Request $request): \WP_REST_Response
{
    $days = max(1, min(30, (int) $request->get_param('days')));

    return new \WP_REST_Response([
        'ok' => true,
        'time' => current_time('mysql'),
        'kpis' => pse_get_generator_kpis($days),
        'last_run' => get_option('pse_content_last_run', []),
    ], 200);
}

function pse_rest_get_topic_pool(\WP_REST_Request $request): \WP_REST_Response
{
    unset($request);

    $settings = pse_get_settings();
    $all      = pse_parse_custom_topic_list((string) ($settings['content_poradnik_custom_topics'] ?? ''));
    $recent   = get_option('pse_recent_topics', []);
    if (!is_array($recent)) {
        $recent = [];
    }
    $topics = array_map(static function (string $t) use ($recent): array {
        return ['text' => $t, 'used' => in_array($t, $recent, true)];
    }, $all);

    return new \WP_REST_Response([
        'ok'        => true,
        'time'      => current_time('mysql'),
        'total'     => count($all),
        'available' => count(array_diff($all, $recent)),
        'used'      => count(array_intersect($all, $recent)),
        'topics'    => $topics,
    ], 200);
}

function pse_register_rest_routes(): void
{
    register_rest_route('pse/v1', '/status', [
        'methods' => 'GET',
        'callback' => 'pse_rest_get_status',
        'permission_callback' => 'pse_rest_permissions',
    ]);

    register_rest_route('pse/v1', '/kpis', [
        'methods' => 'GET',
        'callback' => 'pse_rest_get_kpis',
        'permission_callback' => 'pse_rest_permissions',
        'args' => [
            'days' => [
                'type' => 'integer',
                'required' => false,
                'default' => 7,
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);

    register_rest_route('pse/v1', '/topic-pool', [
        'methods' => 'GET',
        'callback' => 'pse_rest_get_topic_pool',
        'permission_callback' => 'pse_rest_permissions',
    ]);
}
add_action('rest_api_init', 'pse_register_rest_routes');

add_action('wp_ajax_pse_export_topic_pool', function (): void {
    if (!current_user_can('manage_options')) {
        wp_die('Brak uprawnień.', 403);
    }

    check_admin_referer('pse_export_topic_pool');

    $settings = pse_get_settings();
    $topics   = pse_parse_custom_topic_list((string) ($settings['content_poradnik_custom_topics'] ?? ''));
    $content  = implode("\r\n", $topics);
    $filename = 'pse-topic-pool-' . gmdate('Y-m-d') . '.txt';

    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text file download
    exit;
});

function pse_run_content_generation_batch(?int $limit = null, ?string $forced_mode = null, string $trigger = 'manual'): array
{
    $settings = pse_get_settings();
    $count = $limit !== null ? $limit : (int) ($settings['content_daily_count'] ?? 2);
    $count = max(1, min(12, $count));
    $retryAttempts = max(1, min(5, (int) ($settings['content_retry_attempts'] ?? 2)));
    $hourlyLimit = max(1, min(100, (int) ($settings['content_hourly_limit'] ?? 10)));
    $generatedLastHour = pse_count_generated_posts_last_hour();

    if ($generatedLastHour >= $hourlyLimit) {
        $payload = [
            'requested' => $count,
            'created' => 0,
            'failed' => $count,
            'mode' => $forced_mode ?: (string) ($settings['content_generation_mode'] ?? 'mixed'),
            'ids' => [],
            'retry_attempts' => $retryAttempts,
            'failures' => [[
                'mode' => $forced_mode ?: (string) ($settings['content_generation_mode'] ?? 'mixed'),
                'message' => 'Zatrzymano przez hourly rate limit.',
            ]],
            'rate_limited' => true,
            'hourly_limit' => $hourlyLimit,
            'generated_last_hour' => $generatedLastHour,
        ];

        update_option('pse_content_last_run', [
            'time' => current_time('mysql'),
            'requested' => $count,
            'created' => 0,
            'failed' => $count,
            'mode' => $payload['mode'],
            'trigger' => $trigger,
            'retry_attempts' => $retryAttempts,
            'rate_limited' => true,
            'hourly_limit' => $hourlyLimit,
            'generated_last_hour' => $generatedLastHour,
        ], false);

        pse_append_content_audit_log([
            'time' => current_time('mysql'),
            'mode' => $payload['mode'],
            'requested' => $count,
            'created' => 0,
            'failed' => $count,
            'trigger' => $trigger . '_rate_limited',
        ]);

        return $payload;
    }

    $created = [];
    $failed = 0;
    $failures = [];

    for ($i = 0; $i < $count; $i++) {
        $result = null;
        $itemCreated = false;

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            $result = pse_generate_single_content_item($forced_mode, $settings);
            if (!empty($result['ok']) && !empty($result['post_id'])) {
                $created[] = (int) $result['post_id'];
                $itemCreated = true;
                break;
            }
        }

        if (!$itemCreated) {
            $failed++;
            $failures[] = [
                'mode' => (string) ($result['mode'] ?? ($forced_mode ?: ($settings['content_generation_mode'] ?? 'mixed'))),
                'message' => (string) ($result['message'] ?? 'Nieznany błąd generatora.'),
            ];
        }
    }

    $last_run = [
        'time' => current_time('mysql'),
        'requested' => $count,
        'created' => count($created),
        'failed' => $failed,
        'mode' => $forced_mode ?: (string) ($settings['content_generation_mode'] ?? 'mixed'),
        'trigger' => $trigger,
        'retry_attempts' => $retryAttempts,
    ];

    update_option('pse_content_last_run', $last_run, false);
    pse_append_content_audit_log($last_run);

    return [
        'requested' => $count,
        'created' => count($created),
        'failed' => $failed,
        'mode' => $forced_mode ?: (string) ($settings['content_generation_mode'] ?? 'mixed'),
        'ids' => $created,
        'retry_attempts' => $retryAttempts,
        'failures' => $failures,
        'rate_limited' => false,
        'hourly_limit' => $hourlyLimit,
        'generated_last_hour' => $generatedLastHour,
    ];
}

add_action(PSE_CONTENT_GENERATOR_EVENT, function (): void {
    $settings = pse_get_settings();
    if (empty($settings['content_generator_enabled'])) {
        return;
    }

    pse_run_content_generation_batch(null, null, 'cron_mixed');
});

add_action(PSE_CONTENT_GENERATOR_PORADNIK_EVENT, function (): void {
    $settings = pse_get_settings();
    if (empty($settings['content_generator_enabled'])) {
        return;
    }

    pse_run_content_generation_batch((int) ($settings['content_poradnik_per_run'] ?? 2), 'poradnik', 'cron_poradnik');
});

add_action(PSE_CONTENT_GENERATOR_EVERGREEN_EVENT, function (): void {
    $settings = pse_get_settings();
    if (empty($settings['content_generator_enabled'])) {
        return;
    }

    pse_run_content_generation_batch((int) ($settings['content_evergreen_per_run'] ?? 2), 'evergreen', 'cron_evergreen');
});

add_action(PSE_CONTENT_GENERATOR_NEWS_EVENT, function (): void {
    $settings = pse_get_settings();
    if (empty($settings['content_generator_enabled'])) {
        return;
    }

    pse_run_content_generation_batch((int) ($settings['content_news_per_run'] ?? 1), 'news', 'cron_news');
});

function pse_build_image_prompt(int $post_id, string $kind = 'hero', int $step = 0): string
{
    $title = (string) get_the_title($post_id);
    $intro = (string) get_post_meta($post_id, '_poradnik_intro', true);
    $topic = pse_get_post_topic($post_id);
    $lang = function_exists('pse_get_post_lang') ? pse_get_post_lang($post_id) : 'pl';

    $context = trim(implode(' | ', array_filter([$title, $topic, $intro])));

    if ($kind === 'step' && $step > 0) {
        return 'Create a clean tutorial step illustration without logos or text overlays. '
            . 'Step number: ' . $step . '. '
            . 'Theme: ' . ($context !== '' ? $context : $title) . '. '
            . 'Language context: ' . strtoupper($lang) . '. '
            . 'Style: modern infographic, realistic details, soft lighting, 16:9, high clarity.';
    }

    return 'Create a clean tutorial hero image without logos or text overlays. '
        . 'Theme: ' . ($context !== '' ? $context : $title) . '. '
        . 'Language context: ' . strtoupper($lang) . '. '
        . 'Style: modern infographic, realistic details, soft lighting, 16:9, high clarity.';
}

function pse_openai_generate_image_url(string $api_key, string $model, string $prompt, string $size = '1536x1024'): string
{
    $request = [
        'model' => $model,
        'prompt' => $prompt,
        'size' => $size,
    ];

    $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
        'timeout' => 90,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($request),
    ]);

    if (is_wp_error($response)) {
        return '';
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
    if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['data'][0])) {
        return '';
    }

    $row = $decoded['data'][0];
    if (!empty($row['url']) && is_string($row['url'])) {
        return esc_url_raw($row['url']);
    }

    if (!empty($row['b64_json']) && is_string($row['b64_json'])) {
        return 'data:image/png;base64,' . $row['b64_json'];
    }

    return '';
}

function pse_generate_image_fallback(string $title): string
{
    $safe = esc_html($title !== '' ? $title : 'peartree.pro');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="720" viewBox="0 0 1280 720">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="#1D4ED8"/><stop offset="100%" stop-color="#0F172A"/></linearGradient></defs>'
        . '<rect width="1280" height="720" fill="url(#g)"/>'
        . '<text x="80" y="220" font-size="46" fill="#FFFFFF" font-family="Arial, sans-serif">peartree.pro SEO Engine</text>'
        . '<text x="80" y="320" font-size="34" fill="#E5E7EB" font-family="Arial, sans-serif">' . $safe . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function pse_generate_post_image(int $post_id, bool $force = false): array
{
    $settings = pse_get_settings();
    if (empty($settings['image_generator_enabled'])) {
        return ['ok' => false, 'url' => '', 'message' => 'Generator obrazĂłw jest wyĹ‚Ä…czony.'];
    }

    $existing = (string) get_post_meta($post_id, '_pse_generated_hero_image', true);
    if (!$force && $existing !== '') {
        return ['ok' => true, 'url' => $existing, 'message' => 'Obraz juĹĽ istnieje.'];
    }

    $api_key = (string) ($settings['translator_api_key'] ?? '');
    $model = (string) ($settings['image_model'] ?? 'gpt-image-1');
    $title = (string) get_the_title($post_id);

    if ($api_key === '') {
        $fallback = pse_generate_image_fallback($title);
        update_post_meta($post_id, '_pse_generated_hero_image', $fallback);
        update_post_meta($post_id, '_poradnik_generated_hero_image', $fallback);
        return ['ok' => true, 'url' => $fallback, 'message' => 'Brak API key â€” uĹĽyto obrazu fallback.'];
    }

    $image_url = pse_openai_generate_image_url($api_key, $model, pse_build_image_prompt($post_id, 'hero'), '1536x1024');

    if ($image_url === '') {
        $image_url = pse_generate_image_fallback($title);
    }

    update_post_meta($post_id, '_pse_generated_hero_image', $image_url);
    update_post_meta($post_id, '_poradnik_generated_hero_image', $image_url);

    return ['ok' => true, 'url' => $image_url, 'message' => 'Obraz wygenerowany.'];
}

function pse_generate_step_images(int $post_id, bool $force = false): array
{
    $settings = pse_get_settings();
    if (empty($settings['image_generator_enabled']) || empty($settings['image_step_inject_enabled'])) {
        return [];
    }

    $step_count = max(1, min(6, (int) ($settings['image_step_count'] ?? 3)));
    $existing = get_post_meta($post_id, '_pse_generated_step_images', true);
    if (!$force && is_array($existing) && count($existing) >= $step_count) {
        return $existing;
    }

    $api_key = (string) ($settings['translator_api_key'] ?? '');
    $model = (string) ($settings['image_model'] ?? 'gpt-image-1');
    $title = (string) get_the_title($post_id);
    $images = [];

    for ($step = 1; $step <= $step_count; $step++) {
        $url = '';
        if ($api_key !== '') {
            $url = pse_openai_generate_image_url(
                $api_key,
                $model,
                pse_build_image_prompt($post_id, 'step', $step),
                '1536x1024'
            );
        }

        if ($url === '') {
            $url = pse_generate_image_fallback($title . ' - Krok ' . $step);
        }

        $images[] = $url;
    }

    update_post_meta($post_id, '_pse_generated_step_images', $images);
    update_post_meta($post_id, '_poradnik_generated_step_images', $images);

    return $images;
}

add_action('save_post_post', function (int $post_id, WP_Post $post): void {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (($post->post_status ?? '') !== 'publish') {
        return;
    }

    $settings = pse_get_settings();
    if (empty($settings['image_generator_enabled'])) {
        return;
    }

    $existing = (string) get_post_meta($post_id, '_pse_generated_hero_image', true);
    if ($existing === '') {
        pse_generate_post_image($post_id, false);
    }

    $step_images = get_post_meta($post_id, '_pse_generated_step_images', true);
    if (!is_array($step_images) || empty($step_images)) {
        pse_generate_step_images($post_id, false);
    }
}, 40, 2);

add_filter('the_content', function (string $content): string {
    if (!is_single() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return $content;
    }

    $image_url = (string) get_post_meta($post_id, '_pse_generated_hero_image', true);

    $steps_html = '';
    $settings = pse_get_settings();
    $step_images = get_post_meta($post_id, '_pse_generated_step_images', true);
    if (!empty($settings['image_step_inject_enabled']) && is_array($step_images) && !empty($step_images)) {
        $items = '';
        foreach ($step_images as $index => $step_url) {
            if (!is_string($step_url) || $step_url === '') {
                continue;
            }

            $items .= '<figure style="margin:0">'
                . '<img src="' . esc_attr($step_url) . '" alt="' . esc_attr(sprintf('%s - krok %d', get_the_title($post_id), (int) $index + 1)) . '" loading="lazy" decoding="async" style="width:100%;height:auto;border-radius:8px">'
                . '<figcaption style="font-size:12px;color:#646970;margin-top:4px">' . esc_html(sprintf('Krok %d', (int) $index + 1)) . '</figcaption>'
                . '</figure>';
        }

        if ($items !== '') {
            $steps_html = '<section class="pse-generated-steps" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:0 0 1rem">'
                . $items
                . '</section>';
        }
    }

    $prepend = '';

    if ($image_url !== '' && pse_should_prepend_hero_image($post_id, $content, $image_url)) {
        $prepend .= '<figure class="pse-generated-hero" style="margin:0 0 1rem">'
            . '<img src="' . esc_attr($image_url) . '" alt="' . esc_attr(get_the_title($post_id)) . '" loading="lazy" decoding="async" style="width:100%;height:auto;border-radius:10px">'
            . '</figure>';
    }

    if ($steps_html !== '' && strpos($content, 'class="pse-generated-steps"') === false) {
        $prepend .= $steps_html;
    }

    if ($prepend === '') {
        return $content;
    }

    return $prepend . $content;
}, 9);

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'pse_image_generator_box',
        __('peartree.pro: Generator zdjÄ™cia', 'peartree-pro-seo-engine'),
        'pse_render_image_generator_meta_box',
        'post',
        'side',
        'default'
    );
});

function pse_render_image_generator_meta_box(WP_Post $post): void
{
    $image_url = (string) get_post_meta($post->ID, '_pse_generated_hero_image', true);
    $nonce = wp_create_nonce('pse_generate_image_' . $post->ID);

    if ($image_url !== '') {
        echo '<p><img src="' . esc_attr($image_url) . '" alt="" style="width:100%;height:auto;border-radius:8px"></p>';
    }

    echo '<button type="button" class="button button-secondary" id="pse-generate-image-btn" data-post-id="' . esc_attr((string) $post->ID) . '" data-nonce="' . esc_attr($nonce) . '">'
        . esc_html__('Generuj / odĹ›wieĹĽ obraz', 'peartree-pro-seo-engine')
        . '</button>';
    echo '<p id="pse-generate-image-msg" style="margin-top:8px"></p>';
    ?>
    <script>
    (function () {
        var btn = document.getElementById('pse-generate-image-btn');
        if (!btn) { return; }

        btn.addEventListener('click', function () {
            var fd = new FormData();
            fd.append('action', 'pse_generate_post_image');
            fd.append('post_id', btn.getAttribute('data-post-id'));
            fd.append('nonce', btn.getAttribute('data-nonce'));

            btn.disabled = true;
            var msg = document.getElementById('pse-generate-image-msg');
            if (msg) { msg.textContent = 'Generowanie...'; }

            fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    btn.disabled = false;
                    if (msg) {
                        msg.textContent = (data && data.data && data.data.message) ? data.data.message : 'Gotowe.';
                    }
                    if (data && data.success) {
                        window.location.reload();
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    if (msg) { msg.textContent = 'BĹ‚Ä…d poĹ‚Ä…czenia.'; }
                });
        });
    }());
    </script>
    <?php
}

add_action('wp_ajax_pse_generate_post_image', function (): void {
    $post_id = isset($_POST['post_id']) ? (int) wp_unslash($_POST['post_id']) : 0;
    $nonce = isset($_POST['nonce']) ? (string) wp_unslash($_POST['nonce']) : '';

    if ($post_id <= 0 || !wp_verify_nonce($nonce, 'pse_generate_image_' . $post_id)) {
        wp_send_json_error(['message' => 'BĹ‚Ä…d bezpieczeĹ„stwa.']);
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Brak uprawnieĹ„.']);
        return;
    }

    $result = pse_generate_post_image($post_id, true);
    pse_generate_step_images($post_id, true);

    if (empty($result['ok'])) {
        wp_send_json_error(['message' => (string) ($result['message'] ?? 'Nie udaĹ‚o siÄ™ wygenerowaÄ‡ obrazu.')]);
        return;
    }

    wp_send_json_success([
        'message' => (string) ($result['message'] ?? 'Obraz wygenerowany.'),
        'url' => (string) ($result['url'] ?? ''),
    ]);
});

add_action('save_post_post', function (int $post_id, WP_Post $post, bool $update): void {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (($post->post_status ?? '') === 'auto-draft') {
        return;
    }

    if ((string) get_post_meta($post_id, PSE_LANG_META, true) === '') {
        pse_set_post_lang($post_id, pse_default_language());
    }

    $group = (string) get_post_meta($post_id, PSE_TRANSLATION_GROUP_META, true);
    if ($group === '') {
        update_post_meta($post_id, PSE_TRANSLATION_GROUP_META, 'grp_' . $post_id);
    }
}, 10, 3);

add_action('save_post_post', function (int $post_id, WP_Post $post, bool $update): void {
    static $sync_in_progress = false;

    if ($sync_in_progress) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $settings = pse_get_settings();
    if (empty($settings['multilang_enabled']) || empty($settings['auto_translate_enabled'])) {
        return;
    }

    if (($post->post_status ?? '') !== 'publish') {
        return;
    }

    $source_lang = pse_get_post_lang($post_id);
    if ($source_lang !== 'pl') {
        return;
    }

    $group = pse_get_translation_group($post_id);
    $target_langs = array_diff(array_keys(pse_languages()), ['pl']);

    $sync_in_progress = true;

    foreach ($target_langs as $target_lang) {
        $target_post_id = pse_get_translation_for_lang($group, $target_lang);

        $translated_title = pse_translate_text($post->post_title, 'pl', $target_lang);
        $translated_excerpt = pse_translate_text((string) $post->post_excerpt, 'pl', $target_lang);
        $translated_content = pse_translate_text((string) $post->post_content, 'pl', $target_lang);

        $postarr = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_title' => $translated_title,
            'post_excerpt' => $translated_excerpt,
            'post_content' => wp_kses_post($translated_content),
            'post_name' => sanitize_title($translated_title . '-' . $target_lang),
        ];

        if ($target_post_id > 0) {
            $postarr['ID'] = $target_post_id;
            wp_update_post($postarr);
        } else {
            $target_post_id = (int) wp_insert_post($postarr, true);
            if ($target_post_id <= 0) {
                continue;
            }

            $source_categories = wp_get_post_categories($post_id);
            if (!empty($source_categories)) {
                wp_set_post_categories($target_post_id, $source_categories, false);
            }
        }

        update_post_meta($target_post_id, PSE_TRANSLATION_GROUP_META, $group);
        update_post_meta($target_post_id, PSE_SOURCE_POST_META, $post_id);
        pse_set_post_lang($target_post_id, $target_lang);

        $seo_meta_fields = [
            '_poradnik_intro',
            '_poradnik_steps',
            '_poradnik_tools',
            '_poradnik_tips',
            '_poradnik_faq',
            PSE_TOPIC_META,
        ];

        foreach ($seo_meta_fields as $meta_key) {
            $source_meta = (string) get_post_meta($post_id, $meta_key, true);
            if ($source_meta === '') {
                continue;
            }

            update_post_meta($target_post_id, $meta_key, pse_translate_text($source_meta, 'pl', $target_lang));
        }

        $copy_meta_fields = [
            '_poradnik_generated_hero_image',
        ];

        foreach ($copy_meta_fields as $meta_key) {
            $source_meta = get_post_meta($post_id, $meta_key, true);
            if ($source_meta !== '') {
                update_post_meta($target_post_id, $meta_key, $source_meta);
            }
        }
    }

    $sync_in_progress = false;
}, 20, 3);

add_filter('pre_post_link', function (string $permalink, WP_Post $post, bool $leavename): string {
    $settings = pse_get_settings();
    if (empty($settings['multilang_enabled']) || $post->post_type !== 'post') {
        return $permalink;
    }

    $lang = pse_get_post_lang((int) $post->ID);

    return '/' . $lang . '/%postname%/';
}, 10, 3);

add_filter('post_link', function (string $post_link, WP_Post $post): string {
    $settings = pse_get_settings();
    if (empty($settings['multilang_enabled']) || $post->post_type !== 'post') {
        return $post_link;
    }

    $lang = pse_get_post_lang((int) $post->ID);
    $path = trim((string) wp_parse_url($post_link, PHP_URL_PATH), '/');

    if ($path !== '' && preg_match('#^(pl|en|de|es|fr)/#', $path)) {
        return $post_link;
    }

    return home_url('/' . $lang . '/' . $post->post_name . '/');
}, 10, 2);

add_action('template_redirect', function (): void {
    if (!is_single()) {
        return;
    }

    $settings = pse_get_settings();
    if (empty($settings['multilang_enabled'])) {
        return;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post || $post->post_type !== 'post') {
        return;
    }

    $request_lang = (string) get_query_var('pse_lang');
    if ($request_lang === '') {
        return;
    }

    $post_lang = pse_get_post_lang((int) $post->ID);
    if ($request_lang !== $post_lang) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
});

function pse_get_translation_urls(int $post_id): array
{
    $group = (string) get_post_meta($post_id, PSE_TRANSLATION_GROUP_META, true);
    if ($group === '') {
        return [pse_get_post_lang($post_id) => get_permalink($post_id)];
    }

    $urls = [];
    foreach (pse_get_group_posts($group) as $group_post_id) {
        if (get_post_status($group_post_id) !== 'publish') {
            continue;
        }
        $lang = pse_get_post_lang($group_post_id);
        $urls[$lang] = get_permalink($group_post_id);
    }

    if (empty($urls)) {
        $urls[pse_get_post_lang($post_id)] = get_permalink($post_id);
    }

    return $urls;
}

function pse_render_language_switcher(): string
{
    if (!is_single()) {
        return '';
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return '';
    }

    $settings = pse_get_settings();
    if (empty($settings['multilang_enabled'])) {
        return '';
    }

    $urls = pse_get_translation_urls($post_id);
    $labels = pse_languages();
    $current_lang = pse_get_post_lang($post_id);

    $items = [];
    foreach ($labels as $lang => $name) {
        if (empty($urls[$lang])) {
            continue;
        }

        if ($lang === $current_lang) {
            $items[] = '<span style="font-weight:700">' . esc_html($name) . '</span>';
        } else {
            $items[] = '<a href="' . esc_url($urls[$lang]) . '">' . esc_html($name) . '</a>';
        }
    }

    if (empty($items)) {
        return '';
    }

    return '<nav class="pse-language-switcher" aria-label="Language switcher" style="margin:12px 0;padding:8px 12px;border:1px solid #ddd;border-radius:6px">'
        . '<strong>' . esc_html__('JÄ™zyk:', 'peartree-pro-seo-engine') . '</strong> '
        . implode(' | ', $items)
        . '</nav>';
}

add_action('wp_body_open', function (): void {
    echo pse_render_language_switcher();
});

add_action('wp_footer', function (): void {
    if (did_action('wp_body_open') > 0) {
        return;
    }

    echo pse_render_language_switcher();
});

add_shortcode('pse_language_switcher', function (): string {
    return pse_render_language_switcher();
});

add_action('admin_menu', function (): void {
    $platform_available = defined('PPP_OPTION_KEY') || function_exists('ppp_render_dashboard');

    if (!$platform_available) {
        add_menu_page(
            __('peartree.pro', 'peartree-pro-seo-engine'),
            __('peartree.pro', 'peartree-pro-seo-engine'),
            'manage_options',
            'peartree-pro-seo-engine',
            'pse_render_settings_page',
            'dashicons-chart-area',
            58
        );
    }
}, 30);

add_action('admin_init', function (): void {
    register_setting('pse_settings_group', PSE_OPTION_KEY, [
        'type' => 'array',
        'sanitize_callback' => 'pse_sanitize_settings',
        'default' => pse_default_settings(),
    ]);
});

function pse_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['pse_rebuild_map_now']) && check_admin_referer('pse_rebuild_map_action')) {
        if (function_exists('pse_clear_internal_links_cache')) {
            pse_clear_internal_links_cache();
        }

        $map = function_exists('pse_rebuild_internal_link_map') ? pse_rebuild_internal_link_map() : [];
        $count = is_array($map) ? count($map) : 0;

        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf('Mapa linkĂłw zostaĹ‚a przebudowana. Przetworzono wpisĂłw: %d.', $count))
            . '</p></div>';
    }

    if (isset($_POST['pse_reindex_keywords']) && check_admin_referer('pse_reindex_keywords_action')) {
        $count = function_exists('pse_reindex_all_keywords') ? pse_reindex_all_keywords() : 0;
        if (function_exists('pse_rebuild_internal_link_map')) {
            pse_rebuild_internal_link_map();
        }
        if (function_exists('pse_build_link_health_report')) {
            pse_build_link_health_report();
        }

        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf('Reindex sĹ‚Ăłw kluczowych zakoĹ„czony. Przetworzono wpisĂłw: %d.', $count))
            . '</p></div>';
    }

    if (isset($_POST['pse_run_health_check']) && check_admin_referer('pse_run_health_check_action')) {
        $report = function_exists('pse_build_link_health_report') ? pse_build_link_health_report(80) : [];
        $weak_out = (int) ($report['totals']['weak_out_count'] ?? 0);
        $weak_in = (int) ($report['totals']['weak_in_count'] ?? 0);

        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf('Health-check wykonany. WychodzÄ…ce < 3: %d, PrzychodzÄ…ce < 3: %d.', $weak_out, $weak_in))
            . '</p></div>';
    }

    $autofix_preview = [];

    if (isset($_POST['pse_autofix_preview']) && check_admin_referer('pse_autofix_preview_action')) {
        $autofix_preview = function_exists('pse_autofix_weak_outgoing_links_preview')
            ? pse_autofix_weak_outgoing_links_preview(3, 80)
            : [];

        echo '<div class="notice notice-info"><p>'
            . esc_html(sprintf(
                'Dry-run gotowy. Wpisy do poprawy: %d, planowane linki do dodania: %d.',
                (int) ($autofix_preview['affected_posts'] ?? 0),
                (int) ($autofix_preview['planned_links'] ?? 0)
            ))
            . '</p></div>';
    }

    if (isset($_POST['pse_autofix_weak_out']) && check_admin_referer('pse_autofix_weak_out_action')) {
        $result = function_exists('pse_autofix_weak_outgoing_links')
            ? pse_autofix_weak_outgoing_links(3)
            : ['fixed_posts' => 0, 'added_links' => 0];

        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf(
                'Auto-fix zakoĹ„czony. Naprawione wpisy: %d, dodane linki: %d.',
                (int) ($result['fixed_posts'] ?? 0),
                (int) ($result['added_links'] ?? 0)
            ))
            . '</p></div>';
    }

    if (isset($_POST['pse_generate_categories_now']) && check_admin_referer('pse_generate_categories_now_action')) {
        $categories = pse_generate_default_categories();

        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf(
                'Generator kategorii zakoĹ„czony. Utworzono: %d, juĹĽ istniaĹ‚o: %d, bĹ‚Ä™dy: %d.',
                (int) ($categories['created'] ?? 0),
                (int) ($categories['existing'] ?? 0),
                (int) ($categories['errors'] ?? 0)
            ))
            . '</p></div>';
    }

    if (isset($_POST['pse_generate_custom_categories_now']) && check_admin_referer('pse_generate_custom_categories_now_action')) {
        $raw_categories = isset($_POST['pse_custom_categories']) ? wp_unslash((string) $_POST['pse_custom_categories']) : '';
        $categories = pse_generate_categories_from_input($raw_categories);

        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf(
                'Generator wĹ‚asnych kategorii zakoĹ„czony. Przetworzono: %d, utworzono: %d, juĹĽ istniaĹ‚o: %d, bĹ‚Ä™dy: %d.',
                (int) ($categories['processed'] ?? 0),
                (int) ($categories['created'] ?? 0),
                (int) ($categories['existing'] ?? 0),
                (int) ($categories['errors'] ?? 0)
            ))
            . '</p></div>';
    }

    if (isset($_POST['pse_generate_content_now']) && check_admin_referer('pse_generate_content_now_action')) {
        $batch = pse_run_content_generation_batch(null, null, 'manual_mixed');
        if (!empty($batch['rate_limited'])) {
            echo '<div class="notice notice-warning"><p>'
                . esc_html(sprintf(
                    'Generator wstrzymany (hourly limit). Limit: %d/h, już wygenerowane w ostatniej godzinie: %d.',
                    (int) ($batch['hourly_limit'] ?? 0),
                    (int) ($batch['generated_last_hour'] ?? 0)
                ))
                . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>'
                . esc_html(sprintf(
                    'Generator treści zakończony. Żądane: %d, utworzone: %d, pominięte: %d.',
                    (int) ($batch['requested'] ?? 0),
                    (int) ($batch['created'] ?? 0),
                    (int) ($batch['failed'] ?? 0)
                ))
                . '</p></div>';
        }
    }

    if (isset($_POST['pse_generate_content_poradnik_now']) && check_admin_referer('pse_generate_content_poradnik_now_action')) {
        $batch = pse_run_content_generation_batch((int) (pse_get_settings()['content_poradnik_per_run'] ?? 2), 'poradnik', 'manual_poradnik');
        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf('Poradniki: utworzone %d z %d.', (int) ($batch['created'] ?? 0), (int) ($batch['requested'] ?? 0)))
            . '</p></div>';
    }

    if (isset($_POST['pse_generate_content_evergreen_now']) && check_admin_referer('pse_generate_content_evergreen_now_action')) {
        $batch = pse_run_content_generation_batch((int) (pse_get_settings()['content_evergreen_per_run'] ?? 2), 'evergreen', 'manual_evergreen');
        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf('Evergreen: utworzone %d z %d.', (int) ($batch['created'] ?? 0), (int) ($batch['requested'] ?? 0)))
            . '</p></div>';
    }

    if (isset($_POST['pse_generate_content_news_now']) && check_admin_referer('pse_generate_content_news_now_action')) {
        $batch = pse_run_content_generation_batch((int) (pse_get_settings()['content_news_per_run'] ?? 1), 'news', 'manual_news');
        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf('Newsy: utworzone %d z %d.', (int) ($batch['created'] ?? 0), (int) ($batch['requested'] ?? 0)))
            . '</p></div>';
    }

    $content_plan_preview = [];

    if (isset($_POST['pse_generate_content_custom_now']) && check_admin_referer('pse_generate_content_custom_now_action')) {
        $customMode = isset($_POST['pse_custom_mode']) ? sanitize_key((string) wp_unslash($_POST['pse_custom_mode'])) : 'mixed';
        if (!in_array($customMode, ['poradnik', 'evergreen', 'news', 'mixed'], true)) {
            $customMode = 'mixed';
        }

        $customCount = isset($_POST['pse_custom_count']) ? (int) wp_unslash($_POST['pse_custom_count']) : 3;
        $customCount = max(1, min(20, $customCount));

        $batch = pse_run_content_generation_batch($customCount, $customMode === 'mixed' ? null : $customMode, 'manual_custom');
        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf(
                'Custom batch zakończony. Żądane: %d, utworzone: %d, błędy: %d, retry/item: %d.',
                (int) ($batch['requested'] ?? 0),
                (int) ($batch['created'] ?? 0),
                (int) ($batch['failed'] ?? 0),
                (int) ($batch['retry_attempts'] ?? 1)
            ))
            . '</p></div>';

        if (!empty($batch['failures']) && is_array($batch['failures'])) {
            echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Szczegóły błędów:', 'peartree-pro-seo-engine') . '</strong></p><ul style="margin-left:18px">';
            foreach ($batch['failures'] as $failure) {
                echo '<li>' . esc_html((string) ($failure['mode'] ?? 'mixed') . ': ' . (string) ($failure['message'] ?? 'Błąd')) . '</li>';
            }
            echo '</ul></div>';
        }
    }

    if (isset($_POST['pse_preview_content_plan']) && check_admin_referer('pse_preview_content_plan_action')) {
        $previewMode = isset($_POST['pse_preview_mode']) ? sanitize_key((string) wp_unslash($_POST['pse_preview_mode'])) : 'mixed';
        if (!in_array($previewMode, ['poradnik', 'evergreen', 'news', 'mixed'], true)) {
            $previewMode = 'mixed';
        }

        $previewCount = isset($_POST['pse_preview_count']) ? (int) wp_unslash($_POST['pse_preview_count']) : 5;
        $previewCount = max(1, min(20, $previewCount));

        $content_plan_preview = pse_preview_generation_topics($previewCount, $previewMode === 'mixed' ? null : $previewMode);

        echo '<div class="notice notice-info"><p>'
            . esc_html(sprintf('Dry-run gotowy. Planowanych pozycji: %d (tryb: %s).', count($content_plan_preview), $previewMode))
            . '</p></div>';
    }

    $pse_topic_list_result = null;
    $pse_topic_list_preview = null;

    if (isset($_POST['pse_generate_from_topic_list']) && check_admin_referer('pse_generate_from_topic_list_action')) {
        $raw_topic_list = isset($_POST['pse_topic_list_input']) ? sanitize_textarea_field((string) wp_unslash($_POST['pse_topic_list_input'])) : '';
        $topic_list_items = pse_parse_custom_topic_list($raw_topic_list);

        if (empty($topic_list_items)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Brak tematów do wygenerowania — wpisz co najmniej jeden temat.', 'peartree-pro-seo-engine') . '</p></div>';
        } else {
            $pse_topic_list_result = pse_generate_from_topic_list($topic_list_items);
            echo '<div class="notice notice-success"><p>'
                . esc_html(sprintf(
                    'Generator z listy: żądanych %d, utworzonych %d, błędów %d.',
                    (int) ($pse_topic_list_result['requested'] ?? 0),
                    (int) ($pse_topic_list_result['created'] ?? 0),
                    (int) ($pse_topic_list_result['failed'] ?? 0)
                ))
                . '</p></div>';

            if (!empty($pse_topic_list_result['failures']) && is_array($pse_topic_list_result['failures'])) {
                echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Szczegóły błędów:', 'peartree-pro-seo-engine') . '</strong></p><ul style="margin-left:18px">';
                foreach ($pse_topic_list_result['failures'] as $failure) {
                    echo '<li>' . esc_html((string) ($failure['message'] ?? 'Błąd')) . '</li>';
                }
                echo '</ul></div>';
            }
        }
    }

    if (isset($_POST['pse_import_topic_list_txt']) && check_admin_referer('pse_import_topic_list_txt_action')) {
        $import_raw = isset($_POST['pse_import_txt_input']) ? sanitize_textarea_field((string) wp_unslash($_POST['pse_import_txt_input'])) : '';
        $import_topics = pse_parse_custom_topic_list($import_raw);

        if (empty($import_topics)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Brak tematów do zaimportowania.', 'peartree-pro-seo-engine') . '</p></div>';
        } else {
            $import_mode = isset($_POST['pse_import_mode']) && (string) wp_unslash($_POST['pse_import_mode']) === 'replace' ? 'replace' : 'merge';
            $import_current = pse_parse_custom_topic_list((string) ($settings['content_poradnik_custom_topics'] ?? ''));
            $import_merged  = $import_mode === 'replace' ? $import_topics : array_values(array_unique(array_merge($import_current, $import_topics)));
            $import_settings = pse_get_settings();
            $import_settings['content_poradnik_custom_topics'] = implode("\n", $import_merged);
            update_option(PSE_OPTION_KEY, $import_settings);
            // Refresh settings for rest of page render
            $settings = pse_get_settings();
            echo '<div class="notice notice-success"><p>'
                . esc_html(sprintf(
                    'Import zakończony (%s). Tematów w puli: %d.',
                    $import_mode === 'replace' ? 'zastąpienie' : 'scalenie',
                    count($import_merged)
                ))
                . '</p></div>';
        }
    }

    if (isset($_POST['pse_clear_recent_topics']) && check_admin_referer('pse_clear_recent_topics_action')) {
        delete_option('pse_recent_topics');
        echo '<div class="notice notice-success"><p>' . esc_html__('Historia tematów wyczyszczona. Wszystkie tematy z puli są znowu dostępne.', 'peartree-pro-seo-engine') . '</p></div>';
    }

    if (isset($_POST['pse_quick_add_topics']) && check_admin_referer('pse_quick_add_topics_action')) {
        $new_raw = isset($_POST['pse_quick_add_input']) ? sanitize_textarea_field((string) wp_unslash($_POST['pse_quick_add_input'])) : '';
        $new_topics = pse_parse_custom_topic_list($new_raw);

        if (empty($new_topics)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nie wpisano żadnych tematów.', 'peartree-pro-seo-engine') . '</p></div>';
        } else {
            $current_settings = pse_get_settings();
            $existing = pse_parse_custom_topic_list((string) ($current_settings['content_poradnik_custom_topics'] ?? ''));
            $merged   = array_values(array_unique(array_merge($existing, $new_topics)));
            $current_settings['content_poradnik_custom_topics'] = implode("\n", $merged);
            update_option(PSE_OPTION_KEY, $current_settings);
            echo '<div class="notice notice-success"><p>'
                . esc_html(sprintf('Dodano %d nowych tematów do puli. Łącznie: %d.', count($new_topics), count($merged)))
                . '</p></div>';
        }
    }

    if (isset($_POST['pse_preview_topic_list']) && check_admin_referer('pse_preview_topic_list_action')) {
        $raw_preview_list = isset($_POST['pse_topic_list_input_preview']) ? sanitize_textarea_field((string) wp_unslash($_POST['pse_topic_list_input_preview'])) : '';
        $pse_topic_list_preview = pse_parse_custom_topic_list($raw_preview_list);

        if (empty($pse_topic_list_preview)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Brak tematów do podglądu.', 'peartree-pro-seo-engine') . '</p></div>';
            $pse_topic_list_preview = null;
        } else {
            echo '<div class="notice notice-info"><p>'
                . esc_html(sprintf('Podgląd (dry-run): %d tematów w kolejce.', count($pse_topic_list_preview)))
                . '</p></div>';
        }
    }

    if (isset($_POST['pse_generate_from_unused_pool']) && check_admin_referer('pse_generate_from_unused_pool_action')) {
        $pse_unused_now = pse_get_unused_custom_topics();
        if (empty($pse_unused_now)) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Brak dostępnych tematów w puli — wszystkie zostały użyte lub pula jest pusta. Wyczyść historię lub dodaj nowe tematy.', 'peartree-pro-seo-engine') . '</p></div>';
        } else {
            $pse_pool_limit = isset($_POST['pse_pool_batch_count']) ? max(1, min(20, (int) wp_unslash($_POST['pse_pool_batch_count']))) : count($pse_unused_now);
            $pse_pool_run   = array_slice($pse_unused_now, 0, $pse_pool_limit);
            $pse_topic_list_result = pse_generate_from_topic_list($pse_pool_run);
            echo '<div class="notice notice-success"><p>'
                . esc_html(sprintf(
                    'Generator z puli: %d z %d tematów wygenerowanych.',
                    (int) ($pse_topic_list_result['created'] ?? 0),
                    (int) ($pse_topic_list_result['requested'] ?? 0)
                ))
                . '</p></div>';
            if (!empty($pse_topic_list_result['failures'])) {
                echo '<div class="notice notice-warning"><ul style="margin-left:18px">';
                foreach ($pse_topic_list_result['failures'] as $pse_f) {
                    echo '<li>' . esc_html((string) ($pse_f['message'] ?? '')) . '</li>';
                }
                echo '</ul></div>';
            }
        }
    }

    if (isset($_POST['pse_sync_daily_to_platform']) && check_admin_referer('pse_sync_daily_to_platform_action')) {
        $seo_settings = pse_get_settings();
        $platform_settings = get_option('ppp_settings', []);
        if (!is_array($platform_settings)) {
            $platform_settings = [];
        }

        $platform_settings['daily_tutorials'] = max(1, min(20, (int) ($seo_settings['content_daily_count'] ?? 2)));
        update_option('ppp_settings', $platform_settings);

        echo '<div class="notice notice-success"><p>'
            . esc_html(sprintf('Zsynchronizowano limit dzienny generatora do platformy: %d.', (int) $platform_settings['daily_tutorials']))
            . '</p></div>';
    }

    $settings = pse_get_settings();
    if (defined('PPP_OPTION_KEY') && function_exists('ppp_get_settings')) {
        $platform_settings = ppp_get_settings();
        $platform_daily = max(1, min(20, (int) ($platform_settings['daily_tutorials'] ?? ($settings['content_daily_count'] ?? 2))));
        $settings['content_daily_count'] = $platform_daily;
    }
    $active_tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'overview';
    $tabs = [
        'overview' => __('PrzeglÄ…d', 'peartree-pro-seo-engine'),
        'settings' => __('Ustawienia', 'peartree-pro-seo-engine'),
        'tools' => __('Generator i narzÄ™dzia', 'peartree-pro-seo-engine'),
        'health' => __('Kondycja linkĂłw', 'peartree-pro-seo-engine'),
        'audit' => __('Audyt', 'peartree-pro-seo-engine'),
        'integration' => __('Integracja', 'peartree-pro-seo-engine'),
    ];
    if (!isset($tabs[$active_tab])) {
        $active_tab = 'overview';
    }

    $content_last_run = get_option('pse_content_last_run', []);
    $content_audit_log = get_option('pse_content_audit_log', []);
    if (!is_array($content_audit_log)) {
        $content_audit_log = [];
    }
    $generator_kpis = pse_get_generator_kpis(7);
    $generator_kpis_14 = pse_get_generator_kpis(14);
    $prev7_requested = max(0, (int) ($generator_kpis_14['requested'] ?? 0) - (int) ($generator_kpis['requested'] ?? 0));
    $prev7_created = max(0, (int) ($generator_kpis_14['created'] ?? 0) - (int) ($generator_kpis['created'] ?? 0));
    $prev7_success_rate = $prev7_requested > 0 ? round(($prev7_created / $prev7_requested) * 100, 1) : 0.0;
    $generator_trend_pp = round((float) ($generator_kpis['success_rate'] ?? 0) - $prev7_success_rate, 1);
    $generator_trend_label = ($generator_trend_pp >= 0 ? '+' : '') . number_format($generator_trend_pp, 1, ',', '') . ' pp';
    $hourly_limit = max(1, min(100, (int) ($settings['content_hourly_limit'] ?? 10)));
    $generated_last_hour = pse_count_generated_posts_last_hour();
    $hourly_usage_percent = min(100.0, max(0.0, ($generated_last_hour / $hourly_limit) * 100));
    $hourly_usage_label = sprintf('%d/%d (%s%%)', $generated_last_hour, $hourly_limit, number_format($hourly_usage_percent, 0, ',', ' '));
    $hourly_usage_class = 'pse-chip-good';
    if ($generated_last_hour >= $hourly_limit) {
        $hourly_usage_class = 'pse-chip-bad';
    } elseif ($hourly_usage_percent >= 70) {
        $hourly_usage_class = 'pse-chip-warn';
    }

    $generator_status = ['label' => 'Brak danych', 'class' => 'pse-chip-warn'];
    if ((int) ($generator_kpis['runs'] ?? 0) > 0) {
        if ((float) ($generator_kpis['success_rate'] ?? 0) >= 80) {
            $generator_status = ['label' => 'Stabilny', 'class' => 'pse-chip-good'];
        } elseif ((float) ($generator_kpis['success_rate'] ?? 0) >= 50) {
            $generator_status = ['label' => 'Uwaga', 'class' => 'pse-chip-warn'];
        } else {
            $generator_status = ['label' => 'Wymaga interwencji', 'class' => 'pse-chip-bad'];
        }
    }
    $next_runs = [
        'poradnik' => (int) wp_next_scheduled(PSE_CONTENT_GENERATOR_PORADNIK_EVENT),
        'evergreen' => (int) wp_next_scheduled(PSE_CONTENT_GENERATOR_EVERGREEN_EVENT),
        'news' => (int) wp_next_scheduled(PSE_CONTENT_GENERATOR_NEWS_EVENT),
    ];

    $format_next_run = static function (int $timestamp): string {
        if ($timestamp <= 0) {
            return 'brak harmonogramu';
        }

        $now = time();
        $human = human_time_diff($now, $timestamp);
        return sprintf('%s (za %s)', wp_date('Y-m-d H:i:s', $timestamp), $human);
    };

    $run_status = static function (int $timestamp, int $grace): array {
        if ($timestamp <= 0) {
            return ['label' => 'Brak', 'class' => 'pse-chip-warn'];
        }

        $now = time();
        if ($now > ($timestamp + $grace)) {
            return ['label' => 'OpĂłĹşniony', 'class' => 'pse-chip-bad'];
        }

        if ($now > $timestamp) {
            return ['label' => 'W toku', 'class' => 'pse-chip-warn'];
        }

        return ['label' => 'OK', 'class' => 'pse-chip-good'];
    };

    $run_states = [
        'poradnik' => $run_status($next_runs['poradnik'], 12 * HOUR_IN_SECONDS),
        'evergreen' => $run_status($next_runs['evergreen'], 2 * DAY_IN_SECONDS),
        'news' => $run_status($next_runs['news'], 2 * HOUR_IN_SECONDS),
    ];
    $compat = [
        'theme_schema' => pse_is_theme_schema_active(),
        'theme_ratings' => pse_is_theme_ratings_active(),
    ];
    $map_stats = function_exists('pse_get_link_map_stats') ? pse_get_link_map_stats() : [
        'posts' => 0,
        'out_avg' => 0,
        'in_avg' => 0,
        'weak_out' => 0,
        'weak_in' => 0,
        'updated' => current_time('mysql'),
    ];

    $lang_urls = [
        'pl' => home_url('/pl/'),
        'en' => home_url('/en/'),
        'de' => home_url('/de/'),
        'es' => home_url('/es/'),
        'fr' => home_url('/fr/'),
    ];

    $health_report = function_exists('pse_get_link_health_report') ? pse_get_link_health_report() : [
        'generated_at' => current_time('mysql'),
        'totals' => ['weak_out_count' => 0, 'weak_in_count' => 0],
        'weak_out' => [],
        'weak_in' => [],
    ];

    $platform_kpis = function_exists('ppp_get_portal_kpis')
        ? ppp_get_portal_kpis()
        : [
            'articles_count' => (int) (wp_count_posts('poradnik')->publish ?? 0),
            'rankings_count' => (int) (wp_count_posts('ranking')->publish ?? 0),
            'reviews_count' => (int) (wp_count_posts('recenzja')->publish ?? 0),
            'traffic_monthly_views' => 0,
            'affiliate_monthly_revenue' => 0,
        ];

    $platform_stats = [
        'poradnik' => (int) ($platform_kpis['articles_count'] ?? 0),
        'ranking' => (int) ($platform_kpis['rankings_count'] ?? 0),
        'recenzja' => (int) ($platform_kpis['reviews_count'] ?? 0),
        'traffic' => (int) ($platform_kpis['traffic_monthly_views'] ?? 0),
        'revenue' => (float) ($platform_kpis['affiliate_monthly_revenue'] ?? 0),
    ];

    $platform_links = [
        'dashboard' => admin_url('admin.php?page=ppp-dashboard'),
        'poradnik' => admin_url('edit.php?post_type=poradnik'),
        'ranking' => admin_url('edit.php?post_type=ranking'),
        'recenzja' => admin_url('edit.php?post_type=recenzja'),
    ];

    $ov_custom_topics = pse_parse_custom_topic_list((string) ($settings['content_poradnik_custom_topics'] ?? ''));
    $ov_recent_topics = get_option('pse_recent_topics', []);
    if (!is_array($ov_recent_topics)) { $ov_recent_topics = []; }
    $ov_pool_total  = count($ov_custom_topics);
    $ov_pool_used   = count(array_intersect($ov_custom_topics, $ov_recent_topics));
    $ov_pool_avail  = max(0, $ov_pool_total - $ov_pool_used);
    $ov_pool_chip   = $ov_pool_total === 0 ? 'pse-chip-warn' : ($ov_pool_avail === 0 ? 'pse-chip-bad' : ($ov_pool_avail <= 3 ? 'pse-chip-warn' : 'pse-chip-good'));
    $ov_pool_label  = $ov_pool_total === 0 ? 'Brak własnej puli' : ($ov_pool_avail === 0 ? 'Pula wyczerpana' : ($ov_pool_avail <= 3 ? 'Pula prawie pusta' : 'Pula OK'));
    ?>
    <div class="wrap">
        <style>
            .pse-dashboard-grid{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:12px;margin:14px 0 18px}
            .pse-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px}
            .pse-panel{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:12px 14px;margin:12px 0 18px}
            .pse-card h3{margin:0 0 8px 0;font-size:13px;color:#50575e;text-transform:uppercase;letter-spacing:.4px}
            .pse-kpi{font-size:24px;font-weight:700;line-height:1.2;margin:0;color:#101517}
            .pse-sub{margin:6px 0 0 0;color:#646970;font-size:12px}
            .pse-hero{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;border-radius:12px;padding:18px 18px 14px;margin:10px 0 16px}
            .pse-hero h1{margin:0 0 8px 0;color:#fff;font-size:28px;line-height:1.2}
            .pse-hero p{margin:0;opacity:.92;font-size:14px}
            .pse-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
            .pse-badge{background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.2);padding:4px 10px;border-radius:999px;font-size:12px}
            .pse-panel a{color:#1d4ed8}
            .pse-panel a:hover{color:#1e40af}
            .pse-table-wrap{overflow:auto;max-height:520px;border:1px solid #dcdcde;border-radius:10px;background:#fff}
            .pse-table{width:100%;border-collapse:separate;border-spacing:0;min-width:860px}
            .pse-table th{position:sticky;top:0;background:#f6f7f7;z-index:1;text-align:left;font-weight:600;color:#1d2327;border-bottom:1px solid #dcdcde;padding:10px 12px}
            .pse-table td{padding:10px 12px;border-bottom:1px solid #f0f0f1;vertical-align:top}
            .pse-table tr:last-child td{border-bottom:none}
            .pse-table tbody tr:hover td{background:#fbfbfc}
            .pse-chip{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;background:#eef2ff;color:#3730a3}
            .pse-chip-good{background:#dcfce7;color:#166534}
            .pse-chip-warn{background:#fef9c3;color:#854d0e}
            .pse-chip-bad{background:#fee2e2;color:#991b1b}
            .pse-progress{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden;margin-top:8px}
            .pse-progress > span{display:block;height:100%;background:#22c55e}
            .pse-progress > span.warn{background:#f59e0b}
            .pse-progress > span.bad{background:#ef4444}
            .pse-sort-btn{all:unset;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
            .pse-sort-btn .arrow{opacity:.45;font-size:10px}
            .pse-sort-btn[data-dir="asc"] .arrow{opacity:1;transform:rotate(180deg)}
            .pse-sort-btn[data-dir="desc"] .arrow{opacity:1}
            .pse-filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:8px 0 12px}
            .pse-filters input,.pse-filters select{min-width:180px}
            .pse-muted{font-size:12px;color:#646970}
            @media (max-width:1100px){.pse-dashboard-grid{grid-template-columns:repeat(2,minmax(180px,1fr));}}
            @media (max-width:680px){.pse-dashboard-grid{grid-template-columns:1fr;}}
            @media (prefers-color-scheme: dark){
                .pse-card,.pse-panel{background:#1f2328;border-color:#30363d}
                .pse-card h3{color:#9da7b3}
                .pse-kpi{color:#f0f6fc}
                .pse-sub,.pse-panel,.pse-panel p,.pse-panel li{color:#c9d1d9}
                .pse-panel a{color:#58a6ff}
                .pse-panel a:hover{color:#79c0ff}
                .pse-muted{color:#9da7b3}
                .pse-table-wrap{background:#1f2328;border-color:#30363d}
                .pse-table th{background:#262c36;color:#f0f6fc;border-bottom-color:#30363d}
                .pse-table td{border-bottom-color:#2c323a;color:#c9d1d9}
                .pse-table tbody tr:hover td{background:#242a33}
                .pse-chip{background:#1d2a4a;color:#9ecbff}
                .pse-chip-good{background:#123a24;color:#86efac}
                .pse-chip-warn{background:#3f2f0f;color:#fde68a}
                .pse-chip-bad{background:#3f1717;color:#fca5a5}
                .pse-progress{background:#263243}
            }
        </style>

        <div class="pse-hero">
            <h1><?php echo esc_html(PSE_BRAND_NAME); ?></h1>
            <p><?php esc_html_e('Centralny panel zarzÄ…dzania SEO, linkowaniem wewnÄ™trznym i multi-language dla peartree.pro.', 'peartree-pro-seo-engine'); ?></p>
            <div class="pse-badges">
                <span class="pse-badge"><?php echo esc_html('v' . PSE_VERSION); ?></span>
                <span class="pse-badge"><?php echo esc_html(!empty($settings['multilang_enabled']) ? 'Multi-language: ON' : 'Multi-language: OFF'); ?></span>
                <span class="pse-badge"><?php echo esc_html(!empty($settings['internal_linking_enabled']) ? 'Linkowanie: ON' : 'Linkowanie: OFF'); ?></span>
                <span class="pse-badge"><?php echo esc_html(!empty($settings['schema_article']) ? 'Schema: ON' : 'Schema: OFF'); ?></span>
            </div>
        </div>

        <?php if (defined('PPP_OPTION_KEY') && function_exists('ppp_get_settings')) : ?>
            <div class="notice notice-info" style="margin:10px 0 14px 0;">
                <p><?php echo esc_html(sprintf('Generator SEO korzysta z limitu peartree.pro: %d wpisĂłw/dzieĹ„ (Ustawienia platformy).', (int) ($settings['content_daily_count'] ?? 0))); ?></p>
            </div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper" style="margin-bottom:14px">
            <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                <?php $tab_url = add_query_arg(['page' => 'peartree-pro-seo-engine', 'tab' => $tab_key], admin_url('admin.php')); ?>
                <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($tab_label); ?></a>
            <?php endforeach; ?>
        </h2>

        <?php if ($active_tab === 'overview' || $active_tab === 'integration') : ?>

        <div class="pse-panel">
            <h2 style="margin:0 0 10px 0"><?php esc_html_e('Integracja peartree.pro', 'peartree-pro-seo-engine'); ?></h2>
            <p style="margin:0 0 8px 0"><?php esc_html_e('Szybka nawigacja miÄ™dzy SEO Engine i panelem platformy.', 'peartree-pro-seo-engine'); ?></p>
            <p style="margin:0;display:flex;flex-wrap:wrap;gap:10px">
                <a class="button button-secondary" href="<?php echo esc_url($platform_links['dashboard']); ?>"><?php esc_html_e('Dashboard peartree.pro', 'peartree-pro-seo-engine'); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url($platform_links['poradnik']); ?>"><?php esc_html_e('Poradniki (CPT)', 'peartree-pro-seo-engine'); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url($platform_links['ranking']); ?>"><?php esc_html_e('Rankingi (CPT)', 'peartree-pro-seo-engine'); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url($platform_links['recenzja']); ?>"><?php esc_html_e('Recenzje (CPT)', 'peartree-pro-seo-engine'); ?></a>
            </p>
            <form method="post" style="margin-top:10px">
                <?php wp_nonce_field('pse_sync_daily_to_platform_action'); ?>
                <input type="hidden" name="pse_sync_daily_to_platform" value="1">
                <?php submit_button(__('Synchronizuj dzienny limit generatora do platformy', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
            </form>
        </div>

        <?php endif; ?>

        <?php if ($active_tab === 'overview') : ?>

        <div class="pse-dashboard-grid">
            <div class="pse-card">
                <h3><?php esc_html_e('Wpisy w mapie', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html((string) (int) $map_stats['posts']); ?></p>
                <p class="pse-sub"><?php esc_html_e('Wszystkie wpisy monitorowane przez link map', 'peartree-pro-seo-engine'); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('Ĺšr. linkĂłw wychodzÄ…cych', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html(number_format((float) $map_stats['out_avg'], 2)); ?></p>
                <p class="pse-sub"><?php esc_html_e('Ĺšrednia liczba linkĂłw wychodzÄ…cych', 'peartree-pro-seo-engine'); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('Ĺšr. linkĂłw przychodzÄ…cych', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html(number_format((float) $map_stats['in_avg'], 2)); ?></p>
                <p class="pse-sub"><?php esc_html_e('Ĺšrednia liczba linkĂłw przychodzÄ…cych', 'peartree-pro-seo-engine'); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('SĹ‚abe wpisy', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html((string) ((int) $map_stats['weak_out'] + (int) $map_stats['weak_in'])); ?></p>
                <p class="pse-sub"><?php echo esc_html(sprintf('Wych. <3: %d | Przych. <3: %d', (int) $map_stats['weak_out'], (int) $map_stats['weak_in'])); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('Generator treĹ›ci: ostatnie uruchomienie', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html((string) (int) ($content_last_run['created'] ?? 0)); ?></p>
                <p class="pse-sub"><?php echo esc_html(sprintf('Plan: %d | %s', (int) ($content_last_run['requested'] ?? 0), (string) ($content_last_run['time'] ?? __('brak danych', 'peartree-pro-seo-engine')))); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('Wykorzystanie limitu/h', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html($hourly_usage_label); ?></p>
                <div class="pse-progress">
                    <span class="<?php echo esc_attr($hourly_usage_percent >= 100 ? 'bad' : ($hourly_usage_percent >= 70 ? 'warn' : '')); ?>" style="width:<?php echo esc_attr(number_format($hourly_usage_percent, 2, '.', '')); ?>%"></span>
                </div>
                <p class="pse-sub"><span class="pse-chip <?php echo esc_attr($hourly_usage_class); ?>"><?php echo esc_html($generated_last_hour >= $hourly_limit ? 'limit osiągnięty' : 'limit aktywny'); ?></span></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('SkutecznoĹ›Ä‡ generatora (7 dni)', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html(number_format((float) $generator_kpis['success_rate'], 1, ',', '') . '%'); ?></p>
                <p class="pse-sub"><?php echo esc_html(sprintf('UruchomieĹ„: %d | Utworzone: %d / %d | Trend: %s', (int) $generator_kpis['runs'], (int) $generator_kpis['created'], (int) $generator_kpis['requested'], $generator_trend_label)); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('Trend vs poprzednie 7 dni', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html($generator_trend_label); ?></p>
                <p class="pse-sub"><?php echo esc_html(sprintf('Poprzednie 7 dni: %s', number_format((float) $prev7_success_rate, 1, ',', '') . '%')); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('Poradniki (platforma)', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html((string) $platform_stats['poradnik']); ?></p>
                <p class="pse-sub"><?php esc_html_e('Liczba opublikowanych poradnikĂłw CPT', 'peartree-pro-seo-engine'); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('Rankingi + Recenzje', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html((string) ($platform_stats['ranking'] + $platform_stats['recenzja'])); ?></p>
                <p class="pse-sub"><?php echo esc_html(sprintf('Rankingi: %d | Recenzje: %d', $platform_stats['ranking'], $platform_stats['recenzja'])); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('MiesiÄ™czny ruch (platforma)', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html(number_format($platform_stats['traffic'], 0, ',', ' ')); ?></p>
                <p class="pse-sub"><?php esc_html_e('Deklarowany ruch z ustawieĹ„ peartree.pro', 'peartree-pro-seo-engine'); ?></p>
            </div>
            <div class="pse-card">
                <h3><?php esc_html_e('PrzychĂłd afiliacyjny (platforma)', 'peartree-pro-seo-engine'); ?></h3>
                <p class="pse-kpi"><?php echo esc_html(pse_format_pln((float) $platform_stats['revenue'])); ?></p>
                <p class="pse-sub"><?php esc_html_e('PrzychĂłd z panelu peartree.pro', 'peartree-pro-seo-engine'); ?></p>
            </div>
        </div>

        <div class="pse-panel">
            <h2 style="margin:0 0 10px 0"><?php esc_html_e('Status SEO i linkowania', 'peartree-pro-seo-engine'); ?></h2>
            <ul style="margin:0 0 10px 18px;list-style:disc">
                <li><?php echo esc_html(sprintf('Wpisy w mapie: %d', (int) $map_stats['posts'])); ?></li>
                <li><?php echo esc_html(sprintf('Ĺšrednia linkĂłw wychodzÄ…cych: %s', number_format((float) $map_stats['out_avg'], 2))); ?></li>
                <li><?php echo esc_html(sprintf('Ĺšrednia linkĂłw przychodzÄ…cych: %s', number_format((float) $map_stats['in_avg'], 2))); ?></li>
                <li><?php echo esc_html(sprintf('Wpisy z < 3 linkami wychodzÄ…cymi: %d', (int) $map_stats['weak_out'])); ?></li>
                <li><?php echo esc_html(sprintf('Wpisy z < 3 linkami przychodzÄ…cymi: %d', (int) $map_stats['weak_in'])); ?></li>
                <li><?php echo esc_html(sprintf('Aktualizacja statystyk: %s', (string) $map_stats['updated'])); ?></li>
                <li><?php echo esc_html(sprintf('KompatybilnoĹ›Ä‡: Theme Schema %s | Theme Ratings %s', $compat['theme_schema'] ? 'ON' : 'OFF', $compat['theme_ratings'] ? 'ON' : 'OFF')); ?></li>
                <li><?php echo esc_html(sprintf('NastÄ™pny run poradnikĂłw: %s', $format_next_run($next_runs['poradnik']))); ?></li>
                <li><span class="pse-chip <?php echo esc_attr($run_states['poradnik']['class']); ?>"><?php echo esc_html('SLA poradniki: ' . $run_states['poradnik']['label']); ?></span></li>
                <li><?php echo esc_html(sprintf('NastÄ™pny run evergreen: %s', $format_next_run($next_runs['evergreen']))); ?></li>
                <li><span class="pse-chip <?php echo esc_attr($run_states['evergreen']['class']); ?>"><?php echo esc_html('SLA evergreen: ' . $run_states['evergreen']['label']); ?></span></li>
                <li><?php echo esc_html(sprintf('NastÄ™pny run newsĂłw: %s', $format_next_run($next_runs['news']))); ?></li>
                <li><span class="pse-chip <?php echo esc_attr($run_states['news']['class']); ?>"><?php echo esc_html('SLA newsy: ' . $run_states['news']['label']); ?></span></li>
                <li><span class="pse-chip <?php echo esc_attr($generator_status['class']); ?>"><?php echo esc_html('Status generatora: ' . $generator_status['label']); ?></span></li>
                <li><span class="pse-chip <?php echo esc_attr($hourly_usage_class); ?>"><?php echo esc_html('Hourly limit: ' . $hourly_usage_label); ?></span></li>
                <li><?php echo esc_html(sprintf('Poprzednie 7 dni: %s', number_format((float) $prev7_success_rate, 1, ',', '') . '%')); ?></li>
            </ul>

            <p style="margin:0 0 8px 0"><strong><?php esc_html_e('SkrĂłty jÄ™zykowe:', 'peartree-pro-seo-engine'); ?></strong></p>
            <p style="margin:0">
                <?php foreach ($lang_urls as $lang => $url) : ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="margin-right:12px"><?php echo esc_html(strtoupper($lang)); ?></a>
                <?php endforeach; ?>
            </p>
        </div>

        <?php endif; ?>

        <?php if ($active_tab === 'settings') : ?>

        <form method="post" action="options.php">
            <?php settings_fields('pse_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Linkowanie wewnÄ™trzne', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[internal_linking_enabled]" value="1" <?php checked((int) $settings['internal_linking_enabled'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Liczba linkĂłw', 'peartree-pro-seo-engine'); ?></th>
                    <td><input type="number" min="3" max="5" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[internal_links_count]" value="<?php echo esc_attr((string) $settings['internal_links_count']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('PowiÄ…zane artykuĹ‚y', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[related_enabled]" value="1" <?php checked((int) $settings['related_enabled'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Breadcrumbs', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[breadcrumbs_enabled]" value="1" <?php checked((int) $settings['breadcrumbs_enabled'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Schema: Article', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[schema_article]" value="1" <?php checked((int) $settings['schema_article'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Schema: HowTo', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[schema_howto]" value="1" <?php checked((int) $settings['schema_howto'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Schema: FAQ', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[schema_faq]" value="1" <?php checked((int) $settings['schema_faq'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Schema: Breadcrumb', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[schema_breadcrumb]" value="1" <?php checked((int) $settings['schema_breadcrumb'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Multi-language', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[multilang_enabled]" value="1" <?php checked((int) $settings['multilang_enabled'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Automatyczne tĹ‚umaczenie', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[auto_translate_enabled]" value="1" <?php checked((int) $settings['auto_translate_enabled'], 1); ?>> <?php esc_html_e('TwĂłrz EN/DE/ES/FR po publikacji PL', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Generator treĹ›ci', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_generator_enabled]" value="1" <?php checked((int) ($settings['content_generator_enabled'] ?? 1), 1); ?>> <?php esc_html_e('WĹ‚Ä…cz automatyczne generowanie treĹ›ci', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Typ generowanych treĹ›ci', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <select name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_generation_mode]">
                            <option value="poradnik" <?php selected((string) ($settings['content_generation_mode'] ?? 'mixed'), 'poradnik'); ?>><?php esc_html_e('Poradniki', 'peartree-pro-seo-engine'); ?></option>
                            <option value="evergreen" <?php selected((string) ($settings['content_generation_mode'] ?? 'mixed'), 'evergreen'); ?>><?php esc_html_e('Evergreen', 'peartree-pro-seo-engine'); ?></option>
                            <option value="news" <?php selected((string) ($settings['content_generation_mode'] ?? 'mixed'), 'news'); ?>><?php esc_html_e('Newsy', 'peartree-pro-seo-engine'); ?></option>
                            <option value="mixed" <?php selected((string) ($settings['content_generation_mode'] ?? 'mixed'), 'mixed'); ?>><?php esc_html_e('Mix (losowo)', 'peartree-pro-seo-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('W\u0142asne tematy poradnik\u00f3w', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <textarea name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_poradnik_custom_topics]" rows="6" cols="60" class="large-text"><?php echo esc_textarea((string) ($settings['content_poradnik_custom_topics'] ?? '')); ?></textarea>
                        <p class="description"><?php esc_html_e('Jeden temat na lini\u0119. Je\u015bli wpisano tematy, generator poradnik\u00f3w u\u017cyje ich w pierwszej kolejno\u015bci (przed domy\u015blnymi). Puste = tematy domy\u015blne.', 'peartree-pro-seo-engine'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Cron: priorytet własnych tematów', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[cron_use_custom_topics_first]" value="1" <?php checked((int) ($settings['cron_use_custom_topics_first'] ?? 1), 1); ?>> <?php esc_html_e('Gdy wpisano własne tematy, cron poradników używa ich w pierwszej kolejności', 'peartree-pro-seo-engine'); ?></label>
                        <p class="description"><?php esc_html_e('Wyłącz, jeśli chcesz by cron losował tematy niezależnie od listy własnej.', 'peartree-pro-seo-engine'); ?></p>
                    </td>
                </tr>
                <?php if (!(defined('PPP_OPTION_KEY') && function_exists('ppp_get_settings'))) : ?>
                    <td><input type="number" min="1" max="12" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_daily_count]" value="<?php echo esc_attr((string) ($settings['content_daily_count'] ?? 2)); ?>"></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row"><?php esc_html_e('Poradniki / run (daily)', 'peartree-pro-seo-engine'); ?></th>
                    <td><input type="number" min="1" max="6" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_poradnik_per_run]" value="<?php echo esc_attr((string) ($settings['content_poradnik_per_run'] ?? 2)); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Evergreen / run (weekly)', 'peartree-pro-seo-engine'); ?></th>
                    <td><input type="number" min="1" max="6" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_evergreen_per_run]" value="<?php echo esc_attr((string) ($settings['content_evergreen_per_run'] ?? 2)); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Newsy / run (co 4h)', 'peartree-pro-seo-engine'); ?></th>
                    <td><input type="number" min="1" max="6" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_news_per_run]" value="<?php echo esc_attr((string) ($settings['content_news_per_run'] ?? 1)); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Retry prób / element', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <input type="number" min="1" max="5" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_retry_attempts]" value="<?php echo esc_attr((string) ($settings['content_retry_attempts'] ?? 2)); ?>">
                        <p class="description"><?php esc_html_e('Ile razy generator ma ponowić próbę dla jednego elementu, zanim uzna go za błąd.', 'peartree-pro-seo-engine'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Okno unikania tematów', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <input type="number" min="10" max="200" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_recent_topics_window]" value="<?php echo esc_attr((string) ($settings['content_recent_topics_window'] ?? 25)); ?>">
                        <p class="description"><?php esc_html_e('Liczba ostatnich tematów zapamiętywanych, aby ograniczyć powtórzenia.', 'peartree-pro-seo-engine'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Max wpisów na godzinę', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <input type="number" min="1" max="100" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_hourly_limit]" value="<?php echo esc_attr((string) ($settings['content_hourly_limit'] ?? 10)); ?>">
                        <p class="description"><?php esc_html_e('Twardy limit bezpieczeństwa dla batchy ręcznych i cron.', 'peartree-pro-seo-engine'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('SLA alert: quiet hours', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[sla_quiet_hours_enabled]" value="1" <?php checked((int) ($settings['sla_quiet_hours_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Wycisz alerty poza godzinami pracy', 'peartree-pro-seo-engine'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Quiet hours od/do', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <input type="number" min="0" max="23" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[sla_quiet_hours_start]" value="<?php echo esc_attr((string) ($settings['sla_quiet_hours_start'] ?? 22)); ?>" style="width:90px"> :00
                        â€”
                        <input type="number" min="0" max="23" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[sla_quiet_hours_end]" value="<?php echo esc_attr((string) ($settings['sla_quiet_hours_end'] ?? 7)); ?>" style="width:90px"> :00
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Status nowych wpisĂłw', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <select name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_post_status]">
                            <option value="draft" <?php selected((string) ($settings['content_post_status'] ?? 'draft'), 'draft'); ?>><?php esc_html_e('Szkic', 'peartree-pro-seo-engine'); ?></option>
                            <option value="publish" <?php selected((string) ($settings['content_post_status'] ?? 'draft'), 'publish'); ?>><?php esc_html_e('Publikuj od razu', 'peartree-pro-seo-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Preferowana kategoria generatora', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <?php $generator_categories = get_categories(['taxonomy' => 'category', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']); ?>
                        <select name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[content_preferred_category_id]">
                            <option value="0" <?php selected((int) ($settings['content_preferred_category_id'] ?? 0), 0); ?>><?php esc_html_e('Automatycznie wg trybu (fallback)', 'peartree-pro-seo-engine'); ?></option>
                            <?php foreach ((array) $generator_categories as $generator_category) : ?>
                                <option value="<?php echo esc_attr((string) (int) $generator_category->term_id); ?>" <?php selected((int) ($settings['content_preferred_category_id'] ?? 0), (int) $generator_category->term_id); ?>>
                                    <?php echo esc_html((string) $generator_category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Generator zdjÄ™Ä‡ poradnika', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[image_generator_enabled]" value="1" <?php checked((int) $settings['image_generator_enabled'], 1); ?>> <?php esc_html_e('WĹ‚Ä…cz automatyczne generowanie obrazĂłw', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Obrazy krokĂłw poradnika', 'peartree-pro-seo-engine'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[image_step_inject_enabled]" value="1" <?php checked((int) ($settings['image_step_inject_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Wstawiaj obrazy krokĂłw nad treĹ›ciÄ… (domyĹ›lnie 3)', 'peartree-pro-seo-engine'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Liczba obrazĂłw krokĂłw', 'peartree-pro-seo-engine'); ?></th>
                    <td><input type="number" min="1" max="6" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[image_step_count]" value="<?php echo esc_attr((string) ($settings['image_step_count'] ?? 3)); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Model obrazĂłw', 'peartree-pro-seo-engine'); ?></th>
                    <td><input type="text" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[image_model]" value="<?php echo esc_attr((string) ($settings['image_model'] ?? 'gpt-image-1')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Klucz API tĹ‚umaczeĹ„', 'peartree-pro-seo-engine'); ?></th>
                    <td>
                        <input type="password" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[translator_api_key]" value="" class="regular-text" autocomplete="off">
                        <p class="description"><?php esc_html_e('Pozostaw puste, aby zachowaÄ‡ obecny klucz.', 'peartree-pro-seo-engine'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Model tĹ‚umaczeĹ„', 'peartree-pro-seo-engine'); ?></th>
                    <td><input type="text" name="<?php echo esc_attr(PSE_OPTION_KEY); ?>[translator_model]" value="<?php echo esc_attr((string) $settings['translator_model']); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz ustawienia', 'peartree-pro-seo-engine')); ?>
        </form>

        <?php endif; ?>

        <?php if ($active_tab === 'tools') : ?>

        <hr>

        <h2><?php esc_html_e('NarzÄ™dzia serwisowe', 'peartree-pro-seo-engine'); ?></h2>
        <p><?php esc_html_e('UĹĽyj przyciskĂłw poniĹĽej po wiÄ™kszym imporcie treĹ›ci, zmianach kategorii lub zmianach jÄ™zykĂłw.', 'peartree-pro-seo-engine'); ?></p>

        <form method="post" style="display:inline-block;margin-right:10px">
            <?php wp_nonce_field('pse_rebuild_map_action'); ?>
            <input type="hidden" name="pse_rebuild_map_now" value="1">
            <?php submit_button(__('Przebuduj mapÄ™ linkĂłw teraz', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block">
            <?php wp_nonce_field('pse_reindex_keywords_action'); ?>
            <input type="hidden" name="pse_reindex_keywords" value="1">
            <?php submit_button(__('Reindex sĹ‚Ăłw kluczowych', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_run_health_check_action'); ?>
            <input type="hidden" name="pse_run_health_check" value="1">
            <?php submit_button(__('Uruchom kontrolÄ™ kondycji linkĂłw', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_autofix_weak_out_action'); ?>
            <input type="hidden" name="pse_autofix_weak_out" value="1">
            <?php submit_button(__('Auto-fix: wychodzÄ…ce < 3', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_autofix_preview_action'); ?>
            <input type="hidden" name="pse_autofix_preview" value="1">
            <?php submit_button(__('Dry-run auto-fix (bez zapisu)', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_generate_categories_now_action'); ?>
            <input type="hidden" name="pse_generate_categories_now" value="1">
            <?php submit_button(__('Generator kategorii', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="margin:14px 0 0 0;max-width:620px">
            <?php wp_nonce_field('pse_generate_custom_categories_now_action'); ?>
            <input type="hidden" name="pse_generate_custom_categories_now" value="1">
            <label for="pse_custom_categories" style="display:block;font-weight:600;margin:0 0 6px 0"><?php esc_html_e('WĹ‚asne kategorie (po przecinku lub w nowych liniach)', 'peartree-pro-seo-engine'); ?></label>
            <textarea id="pse_custom_categories" name="pse_custom_categories" rows="3" class="large-text" placeholder="SEO, Marketing, AI"></textarea>
            <p style="margin:8px 0 0 0"><?php submit_button(__('Generator wĹ‚asnych kategorii', 'peartree-pro-seo-engine'), 'secondary', '', false); ?></p>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_generate_content_now_action'); ?>
            <input type="hidden" name="pse_generate_content_now" value="1">
            <?php submit_button(__('Generuj treĹ›ci teraz', 'peartree-pro-seo-engine'), 'primary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_generate_content_poradnik_now_action'); ?>
            <input type="hidden" name="pse_generate_content_poradnik_now" value="1">
            <?php submit_button(__('Generuj poradniki teraz', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_generate_content_evergreen_now_action'); ?>
            <input type="hidden" name="pse_generate_content_evergreen_now" value="1">
            <?php submit_button(__('Generuj evergreen teraz', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <form method="post" style="display:inline-block;margin-left:10px">
            <?php wp_nonce_field('pse_generate_content_news_now_action'); ?>
            <input type="hidden" name="pse_generate_content_news_now" value="1">
            <?php submit_button(__('Generuj newsy teraz', 'peartree-pro-seo-engine'), 'secondary', '', false); ?>
        </form>

        <div style="margin-top:16px;padding:12px;border:1px solid #d7deea;border-radius:10px;background:#fff;max-width:980px">
            <h3 style="margin-top:0"><?php esc_html_e('Custom batch generatora', 'peartree-pro-seo-engine'); ?></h3>
            <form method="post" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                <?php wp_nonce_field('pse_generate_content_custom_now_action'); ?>
                <input type="hidden" name="pse_generate_content_custom_now" value="1">
                <p style="margin:0">
                    <label for="pse_custom_mode"><strong><?php esc_html_e('Tryb', 'peartree-pro-seo-engine'); ?></strong></label><br>
                    <select id="pse_custom_mode" name="pse_custom_mode">
                        <option value="mixed"><?php esc_html_e('Mix', 'peartree-pro-seo-engine'); ?></option>
                        <option value="poradnik"><?php esc_html_e('Poradniki', 'peartree-pro-seo-engine'); ?></option>
                        <option value="evergreen"><?php esc_html_e('Evergreen', 'peartree-pro-seo-engine'); ?></option>
                        <option value="news"><?php esc_html_e('Newsy', 'peartree-pro-seo-engine'); ?></option>
                    </select>
                </p>
                <p style="margin:0">
                    <label for="pse_custom_count"><strong><?php esc_html_e('Liczba pozycji', 'peartree-pro-seo-engine'); ?></strong></label><br>
                    <input id="pse_custom_count" type="number" min="1" max="20" name="pse_custom_count" value="5" style="width:90px">
                </p>
                <p style="margin:0"><?php submit_button(__('Uruchom custom batch', 'peartree-pro-seo-engine'), 'primary', '', false); ?></p>
            </form>
        </div>

        <div style="margin-top:12px;padding:12px;border:1px solid #d7deea;border-radius:10px;background:#fff;max-width:980px">
            <h3 style="margin-top:0"><?php esc_html_e('Dry-run planu generatora', 'peartree-pro-seo-engine'); ?></h3>
            <form method="post" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                <?php wp_nonce_field('pse_preview_content_plan_action'); ?>
                <input type="hidden" name="pse_preview_content_plan" value="1">
                <p style="margin:0">
                    <label for="pse_preview_mode"><strong><?php esc_html_e('Tryb', 'peartree-pro-seo-engine'); ?></strong></label><br>
                    <select id="pse_preview_mode" name="pse_preview_mode">
                        <option value="mixed"><?php esc_html_e('Mix', 'peartree-pro-seo-engine'); ?></option>
                        <option value="poradnik"><?php esc_html_e('Poradniki', 'peartree-pro-seo-engine'); ?></option>
                        <option value="evergreen"><?php esc_html_e('Evergreen', 'peartree-pro-seo-engine'); ?></option>
                        <option value="news"><?php esc_html_e('Newsy', 'peartree-pro-seo-engine'); ?></option>
                    </select>
                </p>
                <p style="margin:0">
                    <label for="pse_preview_count"><strong><?php esc_html_e('Liczba pozycji', 'peartree-pro-seo-engine'); ?></strong></label><br>
                    <input id="pse_preview_count" type="number" min="1" max="20" name="pse_preview_count" value="8" style="width:90px">
                </p>
                <p style="margin:0"><?php submit_button(__('Pokaż plan (bez zapisu)', 'peartree-pro-seo-engine'), 'secondary', '', false); ?></p>
            </form>

            <?php if (!empty($content_plan_preview) && is_array($content_plan_preview)) : ?>
                <table class="widefat striped" style="margin-top:12px;max-width:900px">
                    <thead>
                        <tr>
                            <th style="width:80px">#</th>
                            <th><?php esc_html_e('Tryb', 'peartree-pro-seo-engine'); ?></th>
                            <th><?php esc_html_e('Planowany temat', 'peartree-pro-seo-engine'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($content_plan_preview as $index => $preview_row) : ?>
                            <tr>
                                <td><?php echo esc_html((string) ((int) $index + 1)); ?></td>
                                <td><?php echo esc_html((string) ($preview_row['mode'] ?? 'mixed')); ?></td>
                                <td><?php echo esc_html((string) ($preview_row['topic'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-top:12px;padding:12px;border:1px solid #c3d9f0;border-radius:10px;background:#f0f6fc;max-width:980px">
            <h3 style="margin-top:0"><?php esc_html_e('Generator poradnik\u00f3w z listy temat\u00f3w', 'peartree-pro-seo-engine'); ?></h3>

            <?php
            $pse_unused_custom = pse_get_unused_custom_topics();
            $pse_all_custom    = pse_parse_custom_topic_list((string) ($settings['content_poradnik_custom_topics'] ?? ''));
            if (!empty($pse_all_custom)) : ?>
                <p style="margin:0 0 10px 0">
                    <strong><?php echo esc_html(sprintf(
                        'W\u0142asna pula temat\u00f3w: %d \u2014 %d jeszcze nieu\u017cytych.',
                        count($pse_all_custom),
                        count($pse_unused_custom)
                    )); ?></strong>
                    <?php if (count($pse_unused_custom) < count($pse_all_custom)) : ?>
                        <span style="color:#b45309;margin-left:8px"><?php esc_html_e('(cz\u0119\u015b\u0107 temat\u00f3w ju\u017c u\u017cyta \u2014 wyczy\u015b\u0107 histori\u0119 w narz\u0119dziach poni\u017cej)', 'peartree-pro-seo-engine'); ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <p style="margin:0 0 10px 0"><?php esc_html_e('Wklej list\u0119 temat\u00f3w (jeden na lini\u0119). Generator wygeneruje po jednym wpisie dla ka\u017cdego tematu w trybie \u201ePoradniki\u201d, z uwzgl\u0119dnieniem limitu godzinowego i retry.', 'peartree-pro-seo-engine'); ?></p>

            <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start">
                <div style="flex:1;min-width:300px">
                    <form method="post">
                        <?php wp_nonce_field('pse_generate_from_topic_list_action'); ?>
                        <input type="hidden" name="pse_generate_from_topic_list" value="1">
                        <p style="margin:0 0 6px 0">
                            <label for="pse_topic_list_input"><strong><?php esc_html_e('Lista temat\u00f3w (jeden na lini\u0119):', 'peartree-pro-seo-engine'); ?></strong></label><br>
                            <textarea id="pse_topic_list_input" name="pse_topic_list_input" rows="8" cols="60" class="large-text" placeholder="Jak wyczy\u015bci\u0107 filtr powietrza krok po kroku&#10;Jak zamontowa\u0107 p\u00f3\u0142k\u0119 bez wiercenia&#10;Jak przyspieszy\u0107 stary laptop"></textarea>
                        </p>
                        <p style="margin:0"><?php submit_button(__('Generuj poradniki z listy', 'peartree-pro-seo-engine'), 'primary', '', false); ?></p>
                    </form>
                </div>

                <div style="flex:1;min-width:300px">
                    <form method="post">
                        <?php wp_nonce_field('pse_preview_topic_list_action'); ?>
                        <input type="hidden" name="pse_preview_topic_list" value="1">
                        <p style="margin:0 0 6px 0">
                            <label for="pse_topic_list_input_preview"><strong><?php esc_html_e('Dry-run kolejki (bez zapisu):', 'peartree-pro-seo-engine'); ?></strong></label><br>
                            <textarea id="pse_topic_list_input_preview" name="pse_topic_list_input_preview" rows="8" cols="60" class="large-text" placeholder="Wklej tematy, aby zobaczy\u0107 kolejk\u0119 bez generowania..."></textarea>
                        </p>
                        <p style="margin:0"><?php submit_button(__('Poka\u017c kolejk\u0119 (dry-run)', 'peartree-pro-seo-engine'), 'secondary', '', false); ?></p>
                    </form>
                </div>
            </div>

            <?php if ($pse_topic_list_preview !== null) : ?>
                <table class="widefat striped" style="margin-top:14px;max-width:700px">
                    <thead>
                        <tr>
                            <th style="width:46px">#</th>
                            <th><?php esc_html_e('Temat', 'peartree-pro-seo-engine'); ?></th>
                            <th style="width:90px"><?php esc_html_e('Tryb', 'peartree-pro-seo-engine'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pse_topic_list_preview as $pse_tl_idx => $pse_tl_topic) : ?>
                            <tr>
                                <td><?php echo esc_html((string) ((int) $pse_tl_idx + 1)); ?></td>
                                <td><?php echo esc_html($pse_tl_topic); ?></td>
                                <td>poradnik</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($pse_topic_list_result !== null) : ?>
                <div style="margin-top:12px;padding:10px 14px;background:<?php echo (int) ($pse_topic_list_result['failed'] ?? 0) > 0 ? '#fff8e1' : '#eaf4e8'; ?>;border-radius:6px;border:1px solid <?php echo (int) ($pse_topic_list_result['failed'] ?? 0) > 0 ? '#f0d070' : '#b5d9a8'; ?>">
                    <strong><?php echo esc_html(sprintf(
                        'Wynik: %d z %d temat\u00f3w wygenerowanych, %d b\u0142\u0119d\u00f3w.',
                        (int) ($pse_topic_list_result['created'] ?? 0),
                        (int) ($pse_topic_list_result['requested'] ?? 0),
                        (int) ($pse_topic_list_result['failed'] ?? 0)
                    )); ?></strong>
                    <?php if (!empty($pse_topic_list_result['failures'])) : ?>
                        <ul style="margin:6px 0 0 18px">
                            <?php foreach ($pse_topic_list_result['failures'] as $pse_tl_fail) : ?>
                                <li><?php echo esc_html((string) ($pse_tl_fail['message'] ?? '')); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:12px;padding:12px;border:1px solid #e2dff0;border-radius:10px;background:#faf9ff;max-width:980px">
            <h3 style="margin-top:0"><?php esc_html_e('Zarządzanie pulą tematów poradników', 'peartree-pro-seo-engine'); ?></h3>
            <?php
            $pse_mgmt_custom = pse_parse_custom_topic_list((string) ($settings['content_poradnik_custom_topics'] ?? ''));
            $pse_mgmt_recent = get_option('pse_recent_topics', []);
            if (!is_array($pse_mgmt_recent)) { $pse_mgmt_recent = []; }
            $pse_mgmt_used   = count(array_intersect($pse_mgmt_custom, $pse_mgmt_recent));
            $pse_mgmt_avail  = count($pse_mgmt_custom) - $pse_mgmt_used;
            ?>

            <?php if (!empty($pse_mgmt_custom)) : ?>
                <div style="margin-bottom:12px;padding:10px 14px;background:#f0f6e8;border-radius:6px;border:1px solid #b5d9a8;display:flex;gap:16px;flex-wrap:wrap;align-items:center">
                    <span><strong><?php echo esc_html(sprintf('Pula: %d — %d użytych, %d dostępnych', count($pse_mgmt_custom), $pse_mgmt_used, $pse_mgmt_avail)); ?></strong></span>
                    <?php if ($pse_mgmt_avail > 0) : ?>
                    <form method="post" style="display:flex;gap:8px;align-items:center;margin:0">
                        <?php wp_nonce_field('pse_generate_from_unused_pool_action'); ?>
                        <input type="hidden" name="pse_generate_from_unused_pool" value="1">
                        <label style="margin:0;font-weight:600"><?php esc_html_e('Ile:', 'peartree-pro-seo-engine'); ?></label>
                        <input type="number" name="pse_pool_batch_count" min="1" max="<?php echo esc_attr((string) $pse_mgmt_avail); ?>" value="<?php echo esc_attr((string) min(5, $pse_mgmt_avail)); ?>" style="width:70px">
                        <?php submit_button(__('Generuj z niespalonej puli', 'peartree-pro-seo-engine'), 'primary small', '', false); ?>
                    </form>
                    <?php else : ?>
                        <span style="color:#b45309"><?php esc_html_e('Wszystkie tematy użyte — wyczyść historię poniżej.', 'peartree-pro-seo-engine'); ?></span>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'pse_export_topic_pool', '_wpnonce' => wp_create_nonce('pse_export_topic_pool')], admin_url('admin-ajax.php'))); ?>" class="button button-secondary button-small"><?php esc_html_e('Eksportuj pulę (TXT)', 'peartree-pro-seo-engine'); ?></a>
                </div>
                <table class="widefat striped" style="max-width:700px;margin-bottom:12px">
                    <thead><tr><th style="width:46px">#</th><th><?php esc_html_e('Temat', 'peartree-pro-seo-engine'); ?></th><th style="width:100px"><?php esc_html_e('Status', 'peartree-pro-seo-engine'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($pse_mgmt_custom as $pse_mi => $pse_mt) : ?>
                            <?php $pse_mused = in_array($pse_mt, $pse_mgmt_recent, true); ?>
                            <tr<?php echo $pse_mused ? ' style="opacity:0.55"' : ''; ?>>
                                <td><?php echo esc_html((string) ((int) $pse_mi + 1)); ?></td>
                                <td><?php echo esc_html($pse_mt); ?></td>
                                <td><?php if ($pse_mused) : ?><span style="color:#b45309;font-weight:600">● użyta</span><?php else : ?><span style="color:#2e7d32;font-weight:600">● dostępna</span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p style="color:#666"><?php esc_html_e('Brak własnych tematów. Dodaj je poniżej lub w Ustawieniach.', 'peartree-pro-seo-engine'); ?></p>
            <?php endif; ?>

            <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start">
                <form method="post" style="flex:0 0 auto">
                    <?php wp_nonce_field('pse_clear_recent_topics_action'); ?>
                    <input type="hidden" name="pse_clear_recent_topics" value="1">
                    <?php submit_button(__('Wyczyść historię tematów', 'peartree-pro-seo-engine'), 'secondary', '', false, ['onclick' => "return confirm('Czy na pewno wyczyścić historię?');"]); ?>
                    <p class="description" style="margin-top:4px"><?php esc_html_e('Resetuje licznik użytych tematów — wszystkie tematy stają się znowu dostępne.', 'peartree-pro-seo-engine'); ?></p>
                </form>

                <form method="post" style="flex:1;min-width:260px">
                    <?php wp_nonce_field('pse_quick_add_topics_action'); ?>
                    <input type="hidden" name="pse_quick_add_topics" value="1">
                    <p style="margin:0 0 6px 0">
                        <label for="pse_quick_add_input"><strong><?php esc_html_e('Szybkie dodanie tematów do puli:', 'peartree-pro-seo-engine'); ?></strong></label><br>
                        <textarea id="pse_quick_add_input" name="pse_quick_add_input" rows="4" cols="50" class="large-text" placeholder="Nowy temat 1&#10;Nowy temat 2"></textarea>
                    </p>
                    <p style="margin:0"><?php submit_button(__('Dodaj do puli', 'peartree-pro-seo-engine'), 'secondary', '', false); ?></p>
                </form>

                <form method="post" style="flex:1;min-width:260px">
                    <?php wp_nonce_field('pse_import_topic_list_txt_action'); ?>
                    <input type="hidden" name="pse_import_topic_list_txt" value="1">
                    <p style="margin:0 0 6px 0">
                        <label for="pse_import_txt_input"><strong><?php esc_html_e('Import puli (TXT — jeden temat/linia):', 'peartree-pro-seo-engine'); ?></strong></label><br>
                        <textarea id="pse_import_txt_input" name="pse_import_txt_input" rows="4" cols="50" class="large-text" placeholder="Temat A&#10;Temat B"></textarea>
                    </p>
                    <p style="margin:0 0 6px 0">
                        <label style="margin-right:16px">
                            <input type="radio" name="pse_import_mode" value="merge" checked> <?php esc_html_e('Scal z istniejącą pulą', 'peartree-pro-seo-engine'); ?>
                        </label>
                        <label>
                            <input type="radio" name="pse_import_mode" value="replace"> <?php esc_html_e('Zastąp całą pulę', 'peartree-pro-seo-engine'); ?>
                        </label>
                    </p>
                    <p style="margin:0"><?php submit_button(__('Importuj tematy', 'peartree-pro-seo-engine'), 'secondary', '', false); ?></p>
                </form>
            </div>
        </div>

        <?php endif; ?>

        <hr>

        <h2><?php esc_html_e('Dziennik generatora treĹ›ci', 'peartree-pro-seo-engine'); ?></h2>
        <?php if (empty($content_audit_log)) : ?>
            <p><?php esc_html_e('Brak historii uruchomieĹ„.', 'peartree-pro-seo-engine'); ?></p>
        <?php else : ?>
            <div class="pse-table-wrap" style="margin-bottom:16px">
            <table class="pse-table">
                <thead>
                <tr>
                    <th><?php esc_html_e('Czas', 'peartree-pro-seo-engine'); ?></th>
                    <th><?php esc_html_e('Tryb', 'peartree-pro-seo-engine'); ?></th>
                    <th><?php esc_html_e('ĹąrĂłdĹ‚o', 'peartree-pro-seo-engine'); ?></th>
                    <th><?php esc_html_e('Plan', 'peartree-pro-seo-engine'); ?></th>
                    <th><?php esc_html_e('Utworzone', 'peartree-pro-seo-engine'); ?></th>
                    <th><?php esc_html_e('BĹ‚Ä™dy', 'peartree-pro-seo-engine'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_reverse(array_slice($content_audit_log, -20)) as $entry) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($entry['time'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($entry['mode'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($entry['trigger'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) (int) ($entry['requested'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) (int) ($entry['created'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) (int) ($entry['failed'] ?? 0)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'health') : ?>

        <hr>

        <h2><?php esc_html_e('Raport sĹ‚abych powiÄ…zaĹ„', 'peartree-pro-seo-engine'); ?></h2>
        <p>
            <?php echo esc_html(sprintf(
                'Ostatni raport: %s | WychodzÄ…ce < 3: %d | PrzychodzÄ…ce < 3: %d',
                (string) ($health_report['generated_at'] ?? 'brak'),
                (int) ($health_report['totals']['weak_out_count'] ?? 0),
                (int) ($health_report['totals']['weak_in_count'] ?? 0)
            )); ?>
        </p>
        <div class="pse-filters">
            <input type="search" id="pse-report-search" class="regular-text" placeholder="Szukaj po tytuleâ€¦">
            <select id="pse-report-lang">
                <option value="all"><?php esc_html_e('Wszystkie jÄ™zyki', 'peartree-pro-seo-engine'); ?></option>
                <option value="pl">PL</option>
                <option value="en">EN</option>
                <option value="de">DE</option>
                <option value="es">ES</option>
                <option value="fr">FR</option>
            </select>
            <span class="pse-muted" id="pse-report-visible"></span>
        </div>

        <?php if (empty($health_report['weak_out']) && empty($health_report['weak_in'])) : ?>
            <p><?php esc_html_e('Brak wpisĂłw wymagajÄ…cych naprawy w raporcie.', 'peartree-pro-seo-engine'); ?></p>
        <?php else : ?>
            <h3><?php esc_html_e('NajsĹ‚absze linki wychodzÄ…ce (do 80)', 'peartree-pro-seo-engine'); ?></h3>
            <div class="pse-table-wrap" style="margin-bottom:16px">
            <table class="pse-table pse-sort-table pse-report-table">
                <thead>
                <tr>
                    <th><button type="button" class="pse-sort-btn" data-key="id" data-type="number"><?php esc_html_e('ID', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="title" data-type="string"><?php esc_html_e('TytuĹ‚', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="lang" data-type="string"><?php esc_html_e('JÄ™zyk', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="outgoing" data-type="number"><?php esc_html_e('WychodzÄ…ce', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="incoming" data-type="number"><?php esc_html_e('PrzychodzÄ…ce', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><?php esc_html_e('Akcje', 'peartree-pro-seo-engine'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ((array) ($health_report['weak_out'] ?? []) as $row) : ?>
                    <tr class="pse-report-row" data-id="<?php echo esc_attr((string) ($row['post_id'] ?? 0)); ?>" data-lang="<?php echo esc_attr(strtolower((string) ($row['lang'] ?? 'pl'))); ?>" data-title="<?php echo esc_attr(mb_strtolower((string) ($row['title'] ?? ''), 'UTF-8')); ?>" data-outgoing="<?php echo esc_attr((string) ($row['outgoing'] ?? 0)); ?>" data-incoming="<?php echo esc_attr((string) ($row['incoming'] ?? 0)); ?>">
                        <td><?php echo esc_html((string) ($row['post_id'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($row['title'] ?? '')); ?></td>
                        <td><span class="pse-chip"><?php echo esc_html(strtoupper((string) ($row['lang'] ?? 'pl'))); ?></span></td>
                        <td><?php echo esc_html((string) ($row['outgoing'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($row['incoming'] ?? 0)); ?></td>
                        <td>
                            <?php if (!empty($row['view_url'])) : ?><a href="<?php echo esc_url((string) $row['view_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('PodglÄ…d', 'peartree-pro-seo-engine'); ?></a><?php endif; ?>
                            <?php if (!empty($row['edit_url'])) : ?> | <a href="<?php echo esc_url((string) $row['edit_url']); ?>"><?php esc_html_e('Edytuj', 'peartree-pro-seo-engine'); ?></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <h3><?php esc_html_e('NajsĹ‚absze linki przychodzÄ…ce (do 80)', 'peartree-pro-seo-engine'); ?></h3>
            <div class="pse-table-wrap">
            <table class="pse-table pse-sort-table pse-report-table">
                <thead>
                <tr>
                    <th><button type="button" class="pse-sort-btn" data-key="id" data-type="number"><?php esc_html_e('ID', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="title" data-type="string"><?php esc_html_e('TytuĹ‚', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="lang" data-type="string"><?php esc_html_e('JÄ™zyk', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="outgoing" data-type="number"><?php esc_html_e('WychodzÄ…ce', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="incoming" data-type="number"><?php esc_html_e('PrzychodzÄ…ce', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><?php esc_html_e('Akcje', 'peartree-pro-seo-engine'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ((array) ($health_report['weak_in'] ?? []) as $row) : ?>
                    <tr class="pse-report-row" data-id="<?php echo esc_attr((string) ($row['post_id'] ?? 0)); ?>" data-lang="<?php echo esc_attr(strtolower((string) ($row['lang'] ?? 'pl'))); ?>" data-title="<?php echo esc_attr(mb_strtolower((string) ($row['title'] ?? ''), 'UTF-8')); ?>" data-outgoing="<?php echo esc_attr((string) ($row['outgoing'] ?? 0)); ?>" data-incoming="<?php echo esc_attr((string) ($row['incoming'] ?? 0)); ?>">
                        <td><?php echo esc_html((string) ($row['post_id'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($row['title'] ?? '')); ?></td>
                        <td><span class="pse-chip"><?php echo esc_html(strtoupper((string) ($row['lang'] ?? 'pl'))); ?></span></td>
                        <td><?php echo esc_html((string) ($row['outgoing'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($row['incoming'] ?? 0)); ?></td>
                        <td>
                            <?php if (!empty($row['view_url'])) : ?><a href="<?php echo esc_url((string) $row['view_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('PodglÄ…d', 'peartree-pro-seo-engine'); ?></a><?php endif; ?>
                            <?php if (!empty($row['edit_url'])) : ?> | <a href="<?php echo esc_url((string) $row['edit_url']); ?>"><?php esc_html_e('Edytuj', 'peartree-pro-seo-engine'); ?></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>

        <?php endif; ?>

        <?php if (!empty($autofix_preview['rows']) && is_array($autofix_preview['rows'])) : ?>
            <hr>
            <h2><?php esc_html_e('Dry-run auto-fix â€” plan zmian', 'peartree-pro-seo-engine'); ?></h2>
            <p><?php echo esc_html(sprintf('Wygenerowano: %s', (string) ($autofix_preview['generated_at'] ?? ''))); ?></p>
            <div class="pse-filters">
                <input type="search" id="pse-preview-search" class="regular-text" placeholder="Szukaj po tytuleâ€¦">
                <select id="pse-preview-lang">
                    <option value="all"><?php esc_html_e('Wszystkie jÄ™zyki', 'peartree-pro-seo-engine'); ?></option>
                    <option value="pl">PL</option>
                    <option value="en">EN</option>
                    <option value="de">DE</option>
                    <option value="es">ES</option>
                    <option value="fr">FR</option>
                </select>
                <span class="pse-muted" id="pse-preview-visible"></span>
            </div>
            <div class="pse-table-wrap">
            <table class="pse-table pse-sort-table pse-preview-table">
                <thead>
                <tr>
                    <th><button type="button" class="pse-sort-btn" data-key="id" data-type="number"><?php esc_html_e('ID', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="title" data-type="string"><?php esc_html_e('TytuĹ‚', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="lang" data-type="string"><?php esc_html_e('JÄ™zyk', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="outgoing" data-type="number"><?php esc_html_e('Obecne wychodzÄ…ce', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><button type="button" class="pse-sort-btn" data-key="planned" data-type="number"><?php esc_html_e('Do dodania', 'peartree-pro-seo-engine'); ?> <span class="arrow">â–Ľ</span></button></th>
                    <th><?php esc_html_e('Planowane ID targetĂłw', 'peartree-pro-seo-engine'); ?></th>
                    <th><?php esc_html_e('Akcja', 'peartree-pro-seo-engine'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($autofix_preview['rows'] as $row) : ?>
                    <tr class="pse-preview-row" data-id="<?php echo esc_attr((string) ($row['post_id'] ?? 0)); ?>" data-lang="<?php echo esc_attr(strtolower((string) ($row['lang'] ?? 'pl'))); ?>" data-title="<?php echo esc_attr(mb_strtolower((string) ($row['title'] ?? ''), 'UTF-8')); ?>" data-outgoing="<?php echo esc_attr((string) ($row['current_outgoing'] ?? 0)); ?>" data-planned="<?php echo esc_attr((string) ($row['planned_add_count'] ?? 0)); ?>">
                        <td><?php echo esc_html((string) ($row['post_id'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($row['title'] ?? '')); ?></td>
                        <td><span class="pse-chip"><?php echo esc_html(strtoupper((string) ($row['lang'] ?? 'pl'))); ?></span></td>
                        <td><?php echo esc_html((string) ($row['current_outgoing'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($row['planned_add_count'] ?? 0)); ?></td>
                        <td><?php echo esc_html(implode(', ', array_map('intval', (array) ($row['planned_targets'] ?? [])))); ?></td>
                        <td>
                            <?php if (!empty($row['edit_url'])) : ?>
                                <a href="<?php echo esc_url((string) $row['edit_url']); ?>"><?php esc_html_e('Edytuj', 'peartree-pro-seo-engine'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>

        <script>
        (function () {
            function wireFilter(config) {
                var rows = Array.prototype.slice.call(document.querySelectorAll(config.rowSelector));
                if (!rows.length) { return; }

                var search = document.getElementById(config.searchId);
                var lang = document.getElementById(config.langId);
                var counter = document.getElementById(config.counterId);

                function apply() {
                    var q = (search && search.value ? search.value : '').toLowerCase().trim();
                    var l = (lang && lang.value ? lang.value : 'all').toLowerCase();
                    var visible = 0;

                    rows.forEach(function (row) {
                        var title = (row.getAttribute('data-title') || '').toLowerCase();
                        var rowLang = (row.getAttribute('data-lang') || '').toLowerCase();

                        var okQ = !q || title.indexOf(q) !== -1;
                        var okL = l === 'all' || l === rowLang;
                        var show = okQ && okL;

                        row.style.display = show ? '' : 'none';
                        if (show) { visible++; }
                    });

                    if (counter) {
                        counter.textContent = 'Widoczne: ' + visible + ' / ' + rows.length;
                    }
                }

                if (search) { search.addEventListener('input', apply); }
                if (lang) { lang.addEventListener('change', apply); }
                apply();
            }

            wireFilter({
                rowSelector: '.pse-report-row',
                searchId: 'pse-report-search',
                langId: 'pse-report-lang',
                counterId: 'pse-report-visible'
            });

            wireFilter({
                rowSelector: '.pse-preview-row',
                searchId: 'pse-preview-search',
                langId: 'pse-preview-lang',
                counterId: 'pse-preview-visible'
            });

            function wireSort(tableSelector) {
                var tables = document.querySelectorAll(tableSelector);
                if (!tables.length) { return; }

                tables.forEach(function (table) {
                    var tbody = table.querySelector('tbody');
                    if (!tbody) { return; }

                    var sortButtons = table.querySelectorAll('.pse-sort-btn');
                    sortButtons.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var key = btn.getAttribute('data-key');
                            var type = btn.getAttribute('data-type') || 'string';
                            var current = btn.getAttribute('data-dir') || 'none';
                            var nextDir = current === 'asc' ? 'desc' : 'asc';

                            sortButtons.forEach(function (b) { b.setAttribute('data-dir', 'none'); });
                            btn.setAttribute('data-dir', nextDir);

                            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                            rows.sort(function (a, b) {
                                var av = a.getAttribute('data-' + key) || '';
                                var bv = b.getAttribute('data-' + key) || '';

                                var cmp;
                                if (type === 'number') {
                                    cmp = (parseFloat(av) || 0) - (parseFloat(bv) || 0);
                                } else {
                                    cmp = av.localeCompare(bv, undefined, { sensitivity: 'base' });
                                }

                                return nextDir === 'asc' ? cmp : -cmp;
                            });

                            rows.forEach(function (row) { tbody.appendChild(row); });
                        });
                    });
                });
            }

            wireSort('.pse-sort-table');
        }());
        </script>
    </div>
    <?php
}


