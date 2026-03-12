<?php
/**
 * Plugin Name: peartree.pro Platform
 * Description: Platforma portalu wiedzy: poradniki, diagnostyka, rankingi, SEO generator, automatyzacje i dashboard.
 * Version: 1.0.0
 * Author: peartree.pro
 * Text Domain: peartree-pro-platform
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PPP_VERSION', '1.0.0');
define('PPP_OPTION_KEY', 'ppp_settings');
define('PPP_CRON_EVENT', 'ppp_generate_tutorials_event');
define('PPP_PATH', plugin_dir_path(__FILE__));
define('PPP_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('peartree-pro-platform', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 5);

function ppp_enqueue_unified_admin_styles(string $hook_suffix): void
{
    if (!is_admin()) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
    $targets = ['ppp-', 'peartree-pro-seo-engine', 'ppam-', 'paa-', 'ppae-'];

    $matches = false;
    foreach ($targets as $prefix) {
        if (strpos($page, $prefix) === 0 || strpos($hook_suffix, $prefix) !== false) {
            $matches = true;
            break;
        }
    }

    if (!$matches) {
        return;
    }

    wp_enqueue_style(
        'peartree-admin-unified',
        PPP_URL . 'assets/admin/peartree-admin-unified.css',
        [],
        PPP_VERSION
    );
}
add_action('admin_enqueue_scripts', 'ppp_enqueue_unified_admin_styles', 5);

function ppp_get_admin_page_label(string $page): string
{
    $labels = [
        'ppp-ecosystem-dashboard' => 'Dashboard Ekosystemu',
        'ppp-dashboard' => 'Dashboard platformy',
        'peartree-pro-seo-engine' => 'SEO Engine',
        'ppam-marketplace' => 'Ads Marketplace',
        'ppam-campaigns' => 'Kampanie reklamowe',
        'ppam-orders' => 'Zamówienia reklamowe',
        'paa-monetization' => 'Monetyzacja',
        'paa-settings' => 'Ustawienia AdSense',
        'paa-affiliate-links' => 'Linki afiliacyjne',
        'paa-click-statistics' => 'Statystyki kliknięć',
        'ppae-dashboard' => 'Programmatic',
        'ppae-settings' => 'Ustawienia AdSense',
        'ppae-products' => 'Produkty afiliacyjne',
        'ppae-keywords' => 'Słowa kluczowe autolink',
        'ppae-seo-pages' => 'Strony Programmatic SEO',
        'ppae-statistics' => 'Statystyki',
    ];

    return $labels[$page] ?? 'Panel';
}

function ppp_get_admin_quick_links(): array
{
    return [
        ['label' => 'Dashboard', 'url' => admin_url('admin.php?page=ppp-ecosystem-dashboard'), 'match' => ['ppp-'], 'icon' => 'dashicons-dashboard'],
        ['label' => 'SEO Engine', 'url' => admin_url('admin.php?page=peartree-pro-seo-engine'), 'match' => ['peartree-pro-seo-engine'], 'icon' => 'dashicons-chart-area'],
        ['label' => 'Ads Marketplace', 'url' => admin_url('admin.php?page=ppam-marketplace'), 'match' => ['ppam-'], 'icon' => 'dashicons-megaphone'],
        ['label' => 'Monetyzacja', 'url' => admin_url('admin.php?page=paa-monetization'), 'match' => ['paa-'], 'icon' => 'dashicons-chart-line'],
        ['label' => 'Programmatic', 'url' => admin_url('admin.php?page=ppae-dashboard'), 'match' => ['ppae-'], 'icon' => 'dashicons-admin-site-alt3'],
    ];
}

function ppp_is_quick_link_active(string $current_page, array $match_prefixes): bool
{
    if ($current_page === '' || empty($match_prefixes)) {
        return false;
    }

    foreach ($match_prefixes as $prefix) {
        if (strpos($current_page, (string) $prefix) === 0) {
            return true;
        }
    }

    return false;
}

function ppp_render_unified_admin_chrome(): void
{
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
    $targets = ['ppp-', 'peartree-pro-seo-engine', 'ppam-', 'paa-', 'ppae-'];

    $matches = false;
    foreach ($targets as $prefix) {
        if (strpos($page, $prefix) === 0) {
            $matches = true;
            break;
        }
    }

    if (!$matches) {
        return;
    }

    $current_label = ppp_get_admin_page_label($page);
    $quick_links = ppp_get_admin_quick_links();

    echo '<div class="ppp-admin-chrome">';
    echo '<div class="ppp-admin-breadcrumb">';
    echo '<span class="ppp-admin-breadcrumb__root">peartree.pro</span>';
    echo '<span class="ppp-admin-breadcrumb__sep">/</span>';
    echo '<span class="ppp-admin-breadcrumb__current">' . esc_html($current_label) . '</span>';
    echo '</div>';
    echo '<div class="ppp-admin-actions">';

    foreach ($quick_links as $link) {
        $label = (string) ($link['label'] ?? '');
        $url = (string) ($link['url'] ?? '');
        $match_prefixes = (array) ($link['match'] ?? []);
        $icon = sanitize_html_class((string) ($link['icon'] ?? 'dashicons-admin-links'));
        if ($label === '' || $url === '') {
            continue;
        }

        $is_active = ppp_is_quick_link_active($page, $match_prefixes);
        $button_class = $is_active ? 'button button-primary ppp-admin-actions__active' : 'button button-secondary';

        echo '<a class="' . esc_attr($button_class) . '" href="' . esc_url($url) . '">';
        echo '<span class="dashicons ' . esc_attr($icon) . ' ppp-admin-actions__icon" aria-hidden="true"></span>';
        echo '<span class="ppp-admin-actions__label">' . esc_html($label) . '</span>';
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
}
add_action('in_admin_header', 'ppp_render_unified_admin_chrome', 20);

function ppp_default_settings(): array
{
    return [
        'daily_tutorials' => 5,
        'adsense_manager_enabled' => 1,
        'adsense_code' => '',
        'sponsored_banner_enabled' => 0,
        'sponsored_banner_code' => '',
        'sponsored_banner_target_url' => '',
        'affiliate_box_enabled' => 1,
        'affiliate_default_url' => '',
        'sponsored_articles_enabled' => 0,
        'sponsored_articles_count' => 3,
        'ads_auto_insert_enabled' => 1,
        'ads_insert_after_paragraph' => 3,
        'lead_form_shortcode' => '',
        'traffic_monthly_views' => 0,
        'affiliate_monthly_revenue' => 0,
    ];
}

function ppp_get_settings(): array
{
    $settings = get_option(PPP_OPTION_KEY, []);
    return wp_parse_args(is_array($settings) ? $settings : [], ppp_default_settings());
}

function ppp_format_pln(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' PLN';
}

function ppp_extract_adsense_publisher_id(string $value): string
{
    if (preg_match('/ca-pub-[0-9]+/i', $value, $matches) === 1) {
        return strtolower((string) ($matches[0] ?? ''));
    }

    return '';
}

function ppp_register_content_types(): void
{
    register_post_type('poradnik', [
        'labels' => [
            'name' => __('Poradniki', 'peartree-pro-platform'),
            'singular_name' => __('Poradnik', 'peartree-pro-platform'),
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'poradniki'],
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'],
        'show_in_rest' => true,
    ]);

    register_post_type('ranking', [
        'labels' => [
            'name' => __('Rankingi', 'peartree-pro-platform'),
            'singular_name' => __('Ranking', 'peartree-pro-platform'),
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'rankingi'],
        'menu_icon' => 'dashicons-chart-line',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'],
        'show_in_rest' => true,
    ]);

    register_post_type('recenzja', [
        'labels' => [
            'name' => __('Recenzje', 'peartree-pro-platform'),
            'singular_name' => __('Recenzja', 'peartree-pro-platform'),
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'recenzje'],
        'menu_icon' => 'dashicons-star-filled',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'],
        'show_in_rest' => true,
    ]);

    $taxonomies = [
        'kategoria' => __('Kategorie', 'peartree-pro-platform'),
        'urzadzenie' => __('UrzÄ…dzenia', 'peartree-pro-platform'),
        'problem' => __('Problemy', 'peartree-pro-platform'),
    ];

    foreach ($taxonomies as $taxonomy => $label) {
        register_taxonomy($taxonomy, ['poradnik', 'ranking', 'recenzja'], [
            'labels' => [
                'name' => $label,
                'singular_name' => $label,
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => $taxonomy],
        ]);
    }
}
add_action('init', 'ppp_register_content_types');

function ppp_seed_base_terms(): void
{
    $devices = ['router', 'laptop', 'drukarka', 'zmywarka'];
    $problems = ['nie dziaĹ‚a', 'nie wĹ‚Ä…cza siÄ™', 'brak internetu', 'resetuje siÄ™'];
    $categories = ['Dom i ogrĂłd', 'Technologia', 'Internet', 'Motoryzacja', 'Finanse', 'Zdrowie', 'AI'];

    foreach ($devices as $term) {
        if (!term_exists($term, 'urzadzenie')) {
            wp_insert_term($term, 'urzadzenie');
        }
    }

    foreach ($problems as $term) {
        if (!term_exists($term, 'problem')) {
            wp_insert_term($term, 'problem');
        }
    }

    foreach ($categories as $term) {
        if (!term_exists($term, 'kategoria')) {
            wp_insert_term($term, 'kategoria');
        }
    }
}

function ppp_get_or_create_page(string $title, string $content = ''): int
{
    $page = get_page_by_title($title, OBJECT, 'page');
    if ($page instanceof WP_Post) {
        return (int) $page->ID;
    }

    $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_content' => $content,
        'post_name' => sanitize_title($title),
    ], true);

    if (is_wp_error($page_id) || (int) $page_id <= 0) {
        return 0;
    }

    return (int) $page_id;
}

function ppp_seed_site_structure(): void
{
    $front_id = ppp_get_or_create_page('Start', "[pp_diagnostic_wizard]\n\nWitaj w peartree.pro â€” centrum poradnikĂłw i diagnostyki.");
    $blog_id = ppp_get_or_create_page('AktualnoĹ›ci', 'Najnowsze treĹ›ci i aktualizacje portalu.');

    if ($front_id > 0) {
        update_post_meta($front_id, '_wp_page_template', 'template-portal-home.php');
        update_option('show_on_front', 'page');
        update_option('page_on_front', $front_id);
    }

    if ($blog_id > 0) {
        update_option('page_for_posts', $blog_id);
    }

    $menu_name = 'peartree.pro Menu';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : 0;

    if ($menu_id <= 0) {
        $created_menu_id = wp_create_nav_menu($menu_name);
        if (!is_wp_error($created_menu_id)) {
            $menu_id = (int) $created_menu_id;
        }
    }

    if ($menu_id > 0) {
        $items = wp_get_nav_menu_items($menu_id);
        if (empty($items)) {
            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => 'Home',
                'menu-item-url' => home_url('/'),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);

            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => 'Poradniki',
                'menu-item-url' => home_url('/poradniki/'),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);

            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => 'Rankingi',
                'menu-item-url' => home_url('/rankingi/'),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);

            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => 'Recenzje',
                'menu-item-url' => home_url('/recenzje/'),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
        }

        $locations = get_theme_mod('nav_menu_locations');
        if (!is_array($locations)) {
            $locations = [];
        }

        $locations['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
}

function ppp_activate(): void
{
    if (get_option(PPP_OPTION_KEY, null) === null) {
        add_option(PPP_OPTION_KEY, ppp_default_settings());
    }

    ppp_register_content_types();
    ppp_seed_base_terms();
    ppp_seed_site_structure();
    flush_rewrite_rules();

    if (!wp_next_scheduled(PPP_CRON_EVENT)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', PPP_CRON_EVENT);
    }
}
register_activation_hook(__FILE__, 'ppp_activate');

function ppp_deactivate(): void
{
    $timestamp = wp_next_scheduled(PPP_CRON_EVENT);
    if ($timestamp) {
        wp_unschedule_event($timestamp, PPP_CRON_EVENT);
    }
}
register_deactivation_hook(__FILE__, 'ppp_deactivate');

function ppp_build_tutorial_content(string $device, string $problem): array
{
    $title = sprintf('Jak naprawiÄ‡ %s ktĂłry %s', $device, $problem);
    $intro = 'Ten poradnik krok po kroku pomaga zdiagnozowaÄ‡ i naprawiÄ‡ problem szybko, bezpiecznie i skutecznie.';

    $steps = [
        'Zweryfikuj zasilanie i podstawowe poĹ‚Ä…czenia.',
        'SprawdĹş ustawienia systemowe i konfiguracjÄ™ urzÄ…dzenia.',
        'Uruchom diagnostykÄ™ i odczytaj komunikaty bĹ‚Ä™dĂłw.',
        'Wykonaj reset kontrolowany i test funkcjonalny.',
        'Zastosuj rekomendowane poprawki i potwierdĹş stabilnoĹ›Ä‡.',
    ];

    $faq = [
        ['q' => 'Czy reset usunie moje dane?', 'a' => 'To zaleĹĽy od urzÄ…dzenia. Najpierw wykonaj kopiÄ™ zapasowÄ….'],
        ['q' => 'Ile trwa naprawa?', 'a' => 'NajczÄ™Ĺ›ciej od 15 do 45 minut przy podstawowej diagnostyce.'],
    ];

    $content = '<p>' . esc_html($intro) . '</p>';
    $content .= '<h2>Krok po kroku</h2><ol>';

    foreach ($steps as $step) {
        $content .= '<li>' . esc_html($step) . '</li>';
    }

    $content .= '</ol>';
    $content .= '<h2>Praktyczne wskazĂłwki</h2><p>Pracuj etapami i zapisuj zmiany konfiguracji, aby Ĺ‚atwo cofnÄ…Ä‡ bĹ‚Ä™dne ustawienia.</p>';
    $content .= '<h2>Polecane narzÄ™dzia</h2><ul><li>Miernik napiÄ™cia</li><li>ĹšrubokrÄ™t precyzyjny</li><li>Tester sieci</li></ul>';
    $content .= '<h2>FAQ</h2>';

    foreach ($faq as $row) {
        $content .= '<h3>' . esc_html($row['q']) . '</h3><p>' . esc_html($row['a']) . '</p>';
    }

    $content .= '<h2>Diagnostyka rozszerzona</h2>';
    for ($i = 1; $i <= 14; $i++) {
        $content .= '<h3>Etap analizy ' . $i . '</h3>';
        $content .= '<p>' . esc_html(sprintf(
            'W etapie %d sprawdĹş szczegĂłĹ‚owo: %s oraz objaw "%s". Zapisz wyniki testĂłw, porĂłwnaj logi bĹ‚Ä™dĂłw, wykonaj test po zmianach i potwierdĹş stabilnoĹ›Ä‡ przez minimum 10 minut pracy. DziÄ™ki temu ograniczasz ryzyko ponownej awarii i poprawiasz niezawodnoĹ›Ä‡ konfiguracji.',
            $i,
            $device,
            $problem
        )) . '</p>';

        $content .= '<p>' . esc_html(sprintf(
            'Checklista etapu %d: kontrola poĹ‚Ä…czeĹ„, walidacja konfiguracji, test obciÄ…ĹĽeniowy, analiza temperatury i monitoring bĹ‚Ä™dĂłw cyklicznych. Taki proces poprawia jakoĹ›Ä‡ diagnozy dla urzÄ…dzenia %s i zmniejsza prawdopodobieĹ„stwo nawrotu problemu %s.',
            $i,
            $device,
            $problem
        )) . '</p>';
    }

    $content .= '<h2>NajczÄ™stsze bĹ‚Ä™dy uĹĽytkownikĂłw</h2>';
    for ($i = 1; $i <= 12; $i++) {
        $content .= '<p>' . esc_html(sprintf(
            'BĹ‚Ä…d %d: pomijanie weryfikacji podstawowej po zmianie ustawieĹ„. W kontekĹ›cie urzÄ…dzenia "%s" moĹĽe to skutkowaÄ‡ bĹ‚Ä™dnÄ… diagnozÄ… problemu "%s" i wydĹ‚uĹĽeniem czasu naprawy. Zawsze testuj jednÄ… zmianÄ™ na raz i dokumentuj rezultat.',
            $i,
            $device,
            $problem
        )) . '</p>';
    }

    $content .= '<h2>Sekcja ekspercka: stabilnoĹ›Ä‡ dĹ‚ugoterminowa</h2>';
    for ($i = 1; $i <= 10; $i++) {
        $content .= '<p>' . esc_html(sprintf(
            'Rekomendacja ekspercka %d: po naprawie %s wykonaj test 24h, monitoruj parametry krytyczne i porĂłwnuj wyniki z baseline. Dla problemu "%s" zalecana jest teĹĽ regularna kontrola aktualizacji i alertĂłw systemowych.',
            $i,
            $device,
            $problem
        )) . '</p>';
    }

    $content .= '<h2>Podsumowanie</h2><p>Po wdroĹĽeniu powyĹĽszych krokĂłw urzÄ…dzenie powinno dziaĹ‚aÄ‡ stabilnie. JeĹ›li problem wraca, rozwaĹĽ serwis.</p>';

    return [
        'title' => $title,
        'intro' => $intro,
        'content' => $content,
    ];
}

function ppp_generate_placeholder_image(int $post_id, string $title): void
{
    if ($post_id <= 0 || has_post_thumbnail($post_id)) {
        return;
    }

    $upload = wp_upload_dir();
    if (!empty($upload['error'])) {
        return;
    }

    $slug = sanitize_title($title);
    $filename = 'ppp-' . $slug . '.svg';
    $path = trailingslashit($upload['path']) . $filename;
    $url = trailingslashit($upload['url']) . $filename;

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675" viewBox="0 0 1200 675">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#0b5bd3"/><stop offset="100%" stop-color="#1f7ae7"/></linearGradient></defs>'
        . '<rect width="1200" height="675" fill="url(#g)"/>'
        . '<rect x="40" y="40" width="1120" height="595" rx="20" fill="rgba(255,255,255,0.08)"/>'
        . '<text x="70" y="140" fill="#ffffff" font-size="40" font-family="Arial, sans-serif" font-weight="700">peartree.pro</text>'
        . '<text x="70" y="230" fill="#ffffff" font-size="52" font-family="Arial, sans-serif" font-weight="700">' . esc_html($title) . '</text>'
        . '</svg>';

    wp_mkdir_p((string) $upload['path']);
    file_put_contents($path, $svg);

    $attachment_id = wp_insert_attachment([
        'post_mime_type' => 'image/svg+xml',
        'post_title' => $title,
        'post_status' => 'inherit',
        'guid' => $url,
    ], $path, $post_id);

    if (!is_wp_error($attachment_id) && (int) $attachment_id > 0) {
        set_post_thumbnail($post_id, (int) $attachment_id);
    }
}

function ppp_insert_generated_tutorial(string $device, string $problem): int
{
    $payload = ppp_build_tutorial_content($device, $problem);

    $existing = get_page_by_title($payload['title'], OBJECT, 'poradnik');
    if ($existing instanceof WP_Post) {
        return (int) $existing->ID;
    }

    $post_id = wp_insert_post([
        'post_type' => 'poradnik',
        'post_status' => 'publish',
        'post_title' => $payload['title'],
        'post_excerpt' => $payload['intro'],
        'post_content' => wp_kses_post($payload['content']),
        'post_name' => sanitize_title($payload['title']),
    ], true);

    if (is_wp_error($post_id) || (int) $post_id <= 0) {
        return 0;
    }

    $post_id = (int) $post_id;

    wp_set_object_terms($post_id, [$device], 'urzadzenie', false);
    wp_set_object_terms($post_id, [$problem], 'problem', false);
    ppp_generate_placeholder_image($post_id, (string) $payload['title']);

    return $post_id;
}

function ppp_generate_daily_tutorials(?int $limit = null): array
{
    $settings = ppp_get_settings();
    $limit = $limit ?? (int) ($settings['daily_tutorials'] ?? 5);
    $limit = max(1, min(15, $limit));

    $device_terms = get_terms(['taxonomy' => 'urzadzenie', 'hide_empty' => false, 'fields' => 'names']);
    $problem_terms = get_terms(['taxonomy' => 'problem', 'hide_empty' => false, 'fields' => 'names']);

    if (empty($device_terms) || empty($problem_terms)) {
        ppp_seed_base_terms();
        $device_terms = get_terms(['taxonomy' => 'urzadzenie', 'hide_empty' => false, 'fields' => 'names']);
        $problem_terms = get_terms(['taxonomy' => 'problem', 'hide_empty' => false, 'fields' => 'names']);
    }

    $created = 0;
    $attempted = 0;

    foreach ((array) $device_terms as $device) {
        foreach ((array) $problem_terms as $problem) {
            if ($attempted >= $limit) {
                break 2;
            }
            $attempted++;
            $post_id = ppp_insert_generated_tutorial((string) $device, (string) $problem);
            if ($post_id > 0) {
                $created++;
            }
        }
    }

    update_option('ppp_last_generation', [
        'time' => current_time('mysql'),
        'attempted' => $attempted,
        'created' => $created,
    ], false);

    return ['attempted' => $attempted, 'created' => $created];
}

add_action(PPP_CRON_EVENT, function (): void {
    ppp_generate_daily_tutorials();
});

function ppp_render_diagnostic_wizard(): string
{
    $devices = get_terms(['taxonomy' => 'urzadzenie', 'hide_empty' => false]);
    $problems = get_terms(['taxonomy' => 'problem', 'hide_empty' => false]);

    ob_start();
    ?>
    <form class="ppp-wizard" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="hidden" name="post_type" value="poradnik">
        <div class="ppp-row">
            <label for="ppp-device"><?php esc_html_e('UrzÄ…dzenie', 'peartree-pro-platform'); ?></label>
            <select id="ppp-device" name="urzadzenie">
                <?php foreach ((array) $devices as $device) : ?>
                    <option value="<?php echo esc_attr((string) $device->slug); ?>"><?php echo esc_html((string) $device->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ppp-row">
            <label for="ppp-problem"><?php esc_html_e('Problem', 'peartree-pro-platform'); ?></label>
            <select id="ppp-problem" name="problem">
                <?php foreach ((array) $problems as $problem) : ?>
                    <option value="<?php echo esc_attr((string) $problem->slug); ?>"><?php echo esc_html((string) $problem->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit"><?php esc_html_e('ZnajdĹş rozwiÄ…zanie', 'peartree-pro-platform'); ?></button>
    </form>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('pp_diagnostic_wizard', 'ppp_render_diagnostic_wizard');

add_action('pre_get_posts', function (WP_Query $query): void {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('post_type') !== 'poradnik') {
        return;
    }

    $tax_query = [];

    $device = sanitize_title((string) $query->get('urzadzenie'));
    if ($device !== '') {
        $tax_query[] = [
            'taxonomy' => 'urzadzenie',
            'field' => 'slug',
            'terms' => [$device],
        ];
    }

    $problem = sanitize_title((string) $query->get('problem'));
    if ($problem !== '') {
        $tax_query[] = [
            'taxonomy' => 'problem',
            'field' => 'slug',
            'terms' => [$problem],
        ];
    }

    if (!empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }
});

function ppp_ranking_metabox(): void
{
    add_meta_box(
        'ppp_ranking_products',
        __('Produkty w rankingu', 'peartree-pro-platform'),
        'ppp_render_ranking_metabox',
        'ranking',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ppp_ranking_metabox');

function ppp_render_ranking_metabox(WP_Post $post): void
{
    wp_nonce_field('ppp_save_ranking', 'ppp_ranking_nonce');
    $rows = get_post_meta($post->ID, '_ppp_ranking_rows', true);
    $rows = is_array($rows) ? $rows : [];

    if (empty($rows)) {
        $rows = [[
            'name' => '',
            'rating' => '4.5',
            'pros' => '',
            'cons' => '',
            'url' => '',
            'image' => '',
        ]];
    }

    echo '<p>' . esc_html__('WprowadĹş produkty (jeden wiersz = jedna pozycja rankingu).', 'peartree-pro-platform') . '</p>';

    foreach ($rows as $index => $row) {
        echo '<div style="border:1px solid #ddd;padding:12px;margin:0 0 10px 0;background:#fafafa">';
        echo '<p><label>Nazwa<br><input type="text" style="width:100%" name="ppp_rows[' . (int) $index . '][name]" value="' . esc_attr((string) ($row['name'] ?? '')) . '"></label></p>';
        echo '<p><label>Rating (0-5)<br><input type="number" min="0" max="5" step="0.1" name="ppp_rows[' . (int) $index . '][rating]" value="' . esc_attr((string) ($row['rating'] ?? '4.5')) . '"></label></p>';
        echo '<p><label>Plusy<br><textarea style="width:100%" rows="2" name="ppp_rows[' . (int) $index . '][pros]">' . esc_textarea((string) ($row['pros'] ?? '')) . '</textarea></label></p>';
        echo '<p><label>Minusy<br><textarea style="width:100%" rows="2" name="ppp_rows[' . (int) $index . '][cons]">' . esc_textarea((string) ($row['cons'] ?? '')) . '</textarea></label></p>';
        echo '<p><label>URL afiliacyjny<br><input type="url" style="width:100%" name="ppp_rows[' . (int) $index . '][url]" value="' . esc_attr((string) ($row['url'] ?? '')) . '"></label></p>';
        echo '<p><label>URL obrazka<br><input type="url" style="width:100%" name="ppp_rows[' . (int) $index . '][image]" value="' . esc_attr((string) ($row['image'] ?? '')) . '"></label></p>';
        echo '</div>';
    }

    echo '<p>' . esc_html__('Aby dodaÄ‡ kolejne pozycje, zapisz wpis i skopiuj wiersz w kodzie/meta albo rozszerzymy UI o repeater JS.', 'peartree-pro-platform') . '</p>';
}

function ppp_save_ranking_metabox(int $post_id): void
{
    $nonce = isset($_POST['ppp_ranking_nonce']) ? (string) wp_unslash($_POST['ppp_ranking_nonce']) : '';
    if ($nonce === '' || !wp_verify_nonce($nonce, 'ppp_save_ranking')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $rows = (isset($_POST['ppp_rows']) && is_array($_POST['ppp_rows'])) ? wp_unslash($_POST['ppp_rows']) : [];
    $clean = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = sanitize_text_field((string) ($row['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $clean[] = [
            'name' => $name,
            'rating' => max(0, min(5, (float) ($row['rating'] ?? 4.5))),
            'pros' => sanitize_textarea_field((string) ($row['pros'] ?? '')),
            'cons' => sanitize_textarea_field((string) ($row['cons'] ?? '')),
            'url' => esc_url_raw((string) ($row['url'] ?? '')),
            'image' => esc_url_raw((string) ($row['image'] ?? '')),
        ];
    }

    update_post_meta($post_id, '_ppp_ranking_rows', $clean);
}
add_action('save_post_ranking', 'ppp_save_ranking_metabox');

function ppp_render_adsense_block(): string
{
    $settings = ppp_get_settings();
    if (empty($settings['adsense_manager_enabled'])) {
        return '';
    }

    $code = (string) ($settings['adsense_code'] ?? '');
    if ($code === '') {
        return '<div class="ppp-ad-box">AdSense</div>';
    }

    return '<div class="ppp-ad-box">' . $code . '</div>';
}

function ppp_get_ad_click_stats(): array
{
    $stats = get_option('ppp_ad_click_stats', []);
    if (!is_array($stats)) {
        $stats = [];
    }

    return wp_parse_args($stats, [
        'affiliate' => 0,
        'sponsored_banner' => 0,
        'sponsored_article' => 0,
    ]);
}

function ppp_build_tracking_url(string $type, string $target_url, int $post_id = 0): string
{
    $target_url = esc_url_raw($target_url);
    if ($target_url === '') {
        return '';
    }

    return add_query_arg([
        'ppp_click' => '1',
        'type' => sanitize_key($type),
        'to' => rawurlencode($target_url),
        'post_id' => max(0, $post_id),
    ], home_url('/'));
}

function ppp_handle_click_redirect(): void
{
    $isTrackingClick = isset($_GET['ppp_click']) ? (string) wp_unslash($_GET['ppp_click']) : '';
    if ($isTrackingClick !== '1') {
        return;
    }

    $type = isset($_GET['type']) ? sanitize_key((string) wp_unslash($_GET['type'])) : '';
    $allowed = ['affiliate', 'sponsored_banner', 'sponsored_article'];
    if (!in_array($type, $allowed, true)) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    $encoded = isset($_GET['to']) ? (string) wp_unslash($_GET['to']) : '';
    $target_url = esc_url_raw(rawurldecode($encoded));
    if ($target_url === '' || !preg_match('#^https?://#i', $target_url)) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    $stats = ppp_get_ad_click_stats();
    $stats[$type] = (int) ($stats[$type] ?? 0) + 1;
    update_option('ppp_ad_click_stats', $stats, false);

    wp_safe_redirect($target_url);
    exit;
}
add_action('template_redirect', 'ppp_handle_click_redirect');

function ppp_render_sponsored_banner(int $post_id): string
{
    $settings = ppp_get_settings();
    if (empty($settings['sponsored_banner_enabled'])) {
        return '';
    }

    $code = (string) ($settings['sponsored_banner_code'] ?? '');
    if ($code === '') {
        return '';
    }

    $target = (string) ($settings['sponsored_banner_target_url'] ?? '');
    if ($target !== '') {
        $track_url = ppp_build_tracking_url('sponsored_banner', $target, $post_id);
        if ($track_url !== '') {
            return '<div class="ppp-sponsored-banner"><a href="' . esc_url($track_url) . '" rel="sponsored nofollow noopener" target="_blank">' . $code . '</a></div>';
        }
    }

    return '<div class="ppp-sponsored-banner">' . $code . '</div>';
}

function ppp_render_affiliate_box(int $post_id): string
{
    $settings = ppp_get_settings();
    if (empty($settings['affiliate_box_enabled'])) {
        return '';
    }

    $url = (string) get_post_meta($post_id, '_ppp_affiliate_url', true);
    if ($url === '') {
        $url = (string) ($settings['affiliate_default_url'] ?? '');
    }

    if ($url === '') {
        return '';
    }

    $track_url = ppp_build_tracking_url('affiliate', $url, $post_id);
    if ($track_url === '') {
        return '';
    }

    return '<div class="ppp-affiliate-box"><h3>Box afiliacyjny</h3><a class="ppp-btn" href="' . esc_url($track_url) . '" target="_blank" rel="sponsored nofollow noopener">Kup teraz</a></div>';
}

function ppp_render_sponsored_articles_block(int $post_id): string
{
    $settings = ppp_get_settings();
    if (empty($settings['sponsored_articles_enabled'])) {
        return '';
    }

    $count = max(1, min(8, (int) ($settings['sponsored_articles_count'] ?? 3)));
    $query = new WP_Query([
        'post_type' => ['post', 'poradnik'],
        'posts_per_page' => $count,
        'post__not_in' => [$post_id],
        'category_name' => 'sponsorowane',
        'post_status' => 'publish',
    ]);

    if (!$query->have_posts()) {
        return '';
    }

    $items = '';
    while ($query->have_posts()) {
        $query->the_post();
        $link = ppp_build_tracking_url('sponsored_article', (string) get_permalink(), (int) get_the_ID());
        if ($link === '') {
            continue;
        }
        $items .= '<li><a href="' . esc_url($link) . '">' . esc_html(get_the_title()) . '</a></li>';
    }
    wp_reset_postdata();

    if ($items === '') {
        return '';
    }

    return '<div class="ppp-sponsored-articles"><h3>ArtykuĹ‚y sponsorowane</h3><ul>' . $items . '</ul></div>';
}

function ppp_insert_after_paragraph(string $content, string $insertion, int $paragraph_number): string
{
    $paragraph_number = max(1, $paragraph_number);
    $parts = explode('</p>', $content);

    foreach ($parts as $index => $part) {
        if (trim($part) === '') {
            continue;
        }

        $parts[$index] .= '</p>';
        if (($index + 1) === $paragraph_number) {
            $parts[$index] .= $insertion;
            break;
        }
    }

    return implode('', $parts);
}

function ppp_filter_tutorial_content(string $content): string
{
    if (!is_singular(['poradnik', 'post'])) {
        return $content;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return $content;
    }

    $settings = ppp_get_settings();
    $lead_shortcode = (string) ($settings['lead_form_shortcode'] ?? '');
    $lead_html = $lead_shortcode !== '' ? do_shortcode($lead_shortcode) : '';

    $monetization = ppp_render_adsense_block() . ppp_render_sponsored_banner($post_id) . ppp_render_affiliate_box($post_id) . ppp_render_sponsored_articles_block($post_id);
    if ($lead_html !== '') {
        $monetization .= '<div class="ppp-lead-box">' . $lead_html . '</div>';
    }

    if ($monetization === '') {
        return $content;
    }

    $bundle = '<section class="ppp-monetization">' . $monetization . '</section>';

    if (!empty($settings['ads_auto_insert_enabled'])) {
        $after_paragraph = max(1, min(12, (int) ($settings['ads_insert_after_paragraph'] ?? 3)));
        return ppp_insert_after_paragraph($content, $bundle, $after_paragraph);
    }

    return $content . $bundle;
}
add_filter('the_content', 'ppp_filter_tutorial_content', 20);

function ppp_admin_menu(): void
{
    add_menu_page(
        __('peartree.pro Ecosystem', 'peartree-pro-platform'),
        __('peartree.pro', 'peartree-pro-platform'),
        'manage_options',
        'ppp-ecosystem-dashboard',
        'ppp_render_ecosystem_dashboard',
        'dashicons-layout',
        3
    );

    add_submenu_page(
        'ppp-ecosystem-dashboard',
        __('Ecosystem Dashboard', 'peartree-pro-platform'),
        __('Ecosystem Dashboard', 'peartree-pro-platform'),
        'manage_options',
        'ppp-ecosystem-dashboard'
    );

    add_submenu_page(
        'ppp-ecosystem-dashboard',
        __('PrzeglÄ…d', 'peartree-pro-platform'),
        __('Dashboard klasyczny', 'peartree-pro-platform'),
        'manage_options',
        'ppp-dashboard',
        'ppp_render_dashboard'
    );

    add_submenu_page(
        'ppp-ecosystem-dashboard',
        __('Generator', 'peartree-pro-platform'),
        __('Generator', 'peartree-pro-platform'),
        'manage_options',
        'ppp-dashboard-generator',
        'ppp_render_dashboard'
    );

    add_submenu_page(
        'ppp-ecosystem-dashboard',
        __('Ustawienia', 'peartree-pro-platform'),
        __('Ustawienia', 'peartree-pro-platform'),
        'manage_options',
        'ppp-dashboard-settings',
        'ppp_render_dashboard'
    );

    add_submenu_page(
        'ppp-ecosystem-dashboard',
        __('Silniki', 'peartree-pro-platform'),
        __('Silniki', 'peartree-pro-platform'),
        'manage_options',
        'ppp-dashboard-engines',
        'ppp_render_dashboard'
    );

    add_submenu_page(
        'ppp-ecosystem-dashboard',
        __('Integracja SEO', 'peartree-pro-platform'),
        __('Integracja SEO', 'peartree-pro-platform'),
        'manage_options',
        'peartree-pro-seo-engine',
        'ppp_render_seo_bridge_page'
    );
}
add_action('admin_menu', 'ppp_admin_menu');

function ppp_render_ecosystem_dashboard(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $kpis = ppp_get_portal_kpis();
    $integration = ppp_get_plugin_integration_summary();
    $ads = ppp_get_ads_marketplace_summary();

    $module_rows = [
        [
            'name' => 'Platform',
            'enabled' => (bool) ($integration['platform'] ?? false),
            'url' => admin_url('admin.php?page=ppp-ecosystem-dashboard'),
            'rest' => rest_url('ppp/v1/status'),
        ],
        [
            'name' => 'SEO Engine',
            'enabled' => (bool) ($integration['seo'] ?? false),
            'url' => admin_url('admin.php?page=peartree-pro-seo-engine'),
            'rest' => rest_url('pse/v1/status'),
        ],
        [
            'name' => 'Ads Marketplace',
            'enabled' => (bool) ($integration['ads_marketplace'] ?? false),
            'url' => admin_url('admin.php?page=ppam-marketplace'),
            'rest' => rest_url('ppam/v1/stats'),
        ],
        [
            'name' => 'Afiliacja + AdSense',
            'enabled' => (bool) ($integration['afiliacja_adsense'] ?? false),
            'url' => admin_url('admin.php?page=paa-monetization'),
            'rest' => rest_url('peartree/v1/affiliate/health'),
        ],
        [
            'name' => 'Programmatic Affiliate',
            'enabled' => (bool) ($integration['programmatic_affiliate'] ?? false),
            'url' => admin_url('admin.php?page=ppae-dashboard'),
            'rest' => rest_url('ppae/v1/stats'),
        ],
    ];

    usort($module_rows, static function (array $left, array $right): int {
        $leftEnabled = !empty($left['enabled']) ? 1 : 0;
        $rightEnabled = !empty($right['enabled']) ? 1 : 0;

        if ($leftEnabled !== $rightEnabled) {
            return $rightEnabled <=> $leftEnabled;
        }

        return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
    });

    $seoSlaStates = function_exists('pse_content_schedule_sla_states')
        ? (array) pse_content_schedule_sla_states()
        : [];

    $cron_rows = [
        ['label' => 'Platform generator', 'event' => PPP_CRON_EVENT, 'sla_key' => '', 'grace' => DAY_IN_SECONDS],
        ['label' => 'Ads kampanie', 'event' => 'ppam_expire_campaigns_event', 'sla_key' => '', 'grace' => 2 * HOUR_IN_SECONDS],
        ['label' => 'SEO mapa linkĂłw', 'event' => defined('PSE_LINK_MAP_EVENT') ? PSE_LINK_MAP_EVENT : 'pse_rebuild_link_map_event', 'sla_key' => '', 'grace' => DAY_IN_SECONDS],
        ['label' => 'SEO poradniki', 'event' => defined('PSE_CONTENT_GENERATOR_PORADNIK_EVENT') ? PSE_CONTENT_GENERATOR_PORADNIK_EVENT : 'pse_content_generator_poradnik_event', 'sla_key' => 'poradnik', 'grace' => 12 * HOUR_IN_SECONDS],
        ['label' => 'SEO evergreen', 'event' => defined('PSE_CONTENT_GENERATOR_EVERGREEN_EVENT') ? PSE_CONTENT_GENERATOR_EVERGREEN_EVENT : 'pse_content_generator_evergreen_event', 'sla_key' => 'evergreen', 'grace' => 2 * DAY_IN_SECONDS],
        ['label' => 'SEO news', 'event' => defined('PSE_CONTENT_GENERATOR_NEWS_EVENT') ? PSE_CONTENT_GENERATOR_NEWS_EVENT : 'pse_content_generator_news_event', 'sla_key' => 'news', 'grace' => 2 * HOUR_IN_SECONDS],
    ];

    $cron_rows_enriched = [];
    foreach ($cron_rows as $row) {
        $nextTs = (int) wp_next_scheduled((string) $row['event']);

        $slaState = 'missing';
        if ((string) ($row['sla_key'] ?? '') !== '' && isset($seoSlaStates[(string) $row['sla_key']])) {
            $slaState = (string) $seoSlaStates[(string) $row['sla_key']];
        } elseif ($nextTs > 0) {
            $grace = (int) ($row['grace'] ?? DAY_IN_SECONDS);
            $slaState = (time() > ($nextTs + $grace)) ? 'delayed' : 'ok';
        }

        $severityMap = [
            'missing' => 0,
            'delayed' => 1,
            'running' => 2,
            'ok' => 3,
        ];

        $row['next_ts'] = $nextTs;
        $row['sla_state'] = $slaState;
        $row['severity'] = (int) ($severityMap[$slaState] ?? 4);
        $cron_rows_enriched[] = $row;
    }

    usort($cron_rows_enriched, static function (array $left, array $right): int {
        $severityCompare = ((int) ($left['severity'] ?? 4)) <=> ((int) ($right['severity'] ?? 4));
        if ($severityCompare !== 0) {
            return $severityCompare;
        }

        return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
    });

    $slaCriticalCount = 0;
    $slaWarningCount = 0;
    foreach ($cron_rows_enriched as $row) {
        $state = (string) ($row['sla_state'] ?? 'missing');
        if ($state === 'missing') {
            $slaCriticalCount++;
        } elseif ($state === 'delayed') {
            $slaWarningCount++;
        }
    }

    $adsenseSynced = !empty($integration['adsense_synced']);
    $trendCurrent = (float) ($kpis['generator_success_rate_7d'] ?? 0.0);
    $trendPrevious = (float) ($kpis['generator_prev7_success_rate'] ?? 0.0);
    $trendDelta = (float) ($kpis['generator_trend_pp'] ?? 0.0);
    $trendCurrentWidth = max(0, min(100, $trendCurrent));
    $trendPreviousWidth = max(0, min(100, $trendPrevious));

    $seoSettings = function_exists('pse_get_settings') ? (array) pse_get_settings() : [];
    $hourlyLimit = max(1, min(100, (int) ($seoSettings['content_hourly_limit'] ?? 10)));
    $hourlyUsed = function_exists('pse_count_generated_posts_last_hour') ? (int) pse_count_generated_posts_last_hour() : 0;
    $hourlyPercent = min(100.0, max(0.0, ($hourlyUsed / $hourlyLimit) * 100));
    $hourlyLabel = sprintf('%d/%d (%s%%)', $hourlyUsed, $hourlyLimit, number_format($hourlyPercent, 0, ',', ' '));
    $hourlyClass = 'ppp-eco-status-ok';
    $hourlyBarClass = '';
    if ($hourlyUsed >= $hourlyLimit) {
        $hourlyClass = 'ppp-eco-status-bad';
        $hourlyBarClass = 'bad';
    } elseif ($hourlyPercent >= 70) {
        $hourlyClass = 'ppp-eco-status-warn';
        $hourlyBarClass = 'warn';
    }

    if (empty($integration['seo'])) {
        $hourlyLabel = 'SEO Engine nieaktywny';
        $hourlyPercent = 0.0;
        $hourlyClass = 'ppp-eco-status-neutral';
        $hourlyBarClass = '';
    }

    $activeModules = 0;
    foreach ($module_rows as $module) {
        if (!empty($module['enabled'])) {
            $activeModules++;
        }
    }

    ?>
    <div class="wrap">
        <h1>peartree.pro Ecosystem Dashboard</h1>
        <style>
            .ppp-eco-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin:14px 0}
            .ppp-eco-card{background:#fff;border:1px solid #d7deea;border-radius:12px;padding:16px}
            .ppp-eco-kpi{font-size:28px;font-weight:700;color:#0b5bd3;line-height:1.2}
            .ppp-eco-panel{background:#fff;border:1px solid #d7deea;border-radius:12px;padding:16px;margin:0 0 14px 0}
            .ppp-eco-links{display:flex;flex-wrap:wrap;gap:8px}
            .ppp-eco-status{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid transparent}
            .ppp-eco-status-ok{background:#e7f7ee;color:#12663b;border-color:#bde6cc}
            .ppp-eco-status-warn{background:#fff4df;color:#7a4b00;border-color:#ffd998}
            .ppp-eco-status-bad{background:#ffe7e7;color:#8f1d1d;border-color:#ffc2c2}
            .ppp-eco-status-neutral{background:#eef2f7;color:#344054;border-color:#d7deea}
            .ppp-eco-table{width:100%;border-collapse:collapse}
            .ppp-eco-table th,.ppp-eco-table td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}
            .ppp-eco-chart{display:grid;gap:8px;max-width:560px}
            .ppp-eco-chart-row{display:grid;grid-template-columns:130px 1fr 62px;gap:8px;align-items:center}
            .ppp-eco-chart-track{height:12px;background:#eef2f7;border-radius:999px;overflow:hidden}
            .ppp-eco-chart-fill{height:100%;background:#0b5bd3}
            .ppp-eco-progress{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden;margin-top:8px}
            .ppp-eco-progress > span{display:block;height:100%;background:#22c55e}
            .ppp-eco-progress > span.warn{background:#f59e0b}
            .ppp-eco-progress > span.bad{background:#ef4444}
        </style>

        <div class="ppp-eco-panel">
            <h2 style="margin-top:0">Widok globalny</h2>
            <p style="margin-top:0">Jeden panel do zarzÄ…dzania SEO, reklamÄ…, afiliacjÄ… i automatyzacjÄ… treĹ›ci.</p>
            <p style="margin-top:0">Alarmy SLA:
                <span class="ppp-eco-status <?php echo $slaCriticalCount > 0 ? 'ppp-eco-status-bad' : 'ppp-eco-status-ok'; ?>"><?php echo esc_html((string) $slaCriticalCount); ?> krytyczne</span>
                <span class="ppp-eco-status <?php echo $slaWarningCount > 0 ? 'ppp-eco-status-warn' : 'ppp-eco-status-ok'; ?>"><?php echo esc_html((string) $slaWarningCount); ?> ostrzeĹĽeĹ„</span>
            </p>
            <div class="ppp-eco-links">
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ppp-dashboard')); ?>">Dashboard klasyczny</a>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ppam-marketplace')); ?>">Ads Marketplace</a>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=peartree-pro-seo-engine')); ?>">SEO Engine</a>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=paa-monetization')); ?>">Monetyzacja</a>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ppae-dashboard')); ?>">Programmatic</a>
            </div>
        </div>

        <div class="ppp-eco-grid">
            <div class="ppp-eco-card"><div class="ppp-eco-kpi"><?php echo esc_html((string) $activeModules); ?>/5</div><div>Aktywne moduĹ‚y</div></div>
            <div class="ppp-eco-card"><div class="ppp-eco-kpi"><?php echo esc_html((string) (int) ($kpis['articles_count'] ?? 0)); ?></div><div>Poradniki</div></div>
            <div class="ppp-eco-card"><div class="ppp-eco-kpi"><?php echo esc_html((string) (int) ($ads['campaigns'] ?? 0)); ?></div><div>Kampanie reklamowe</div></div>
            <div class="ppp-eco-card"><div class="ppp-eco-kpi"><?php echo esc_html(number_format((float) ($ads['total_budget'] ?? 0), 2, ',', ' ') . ' PLN'); ?></div><div>BudĹĽet kampanii</div></div>
            <div class="ppp-eco-card"><div class="ppp-eco-kpi"><?php echo esc_html(number_format((float) ($kpis['generator_success_rate_7d'] ?? 0), 1, ',', '') . '%'); ?></div><div>SkutecznoĹ›Ä‡ generatora (7d)</div></div>
            <div class="ppp-eco-card"><div class="ppp-eco-kpi"><?php echo esc_html(ppp_format_pln((float) ($kpis['affiliate_monthly_revenue'] ?? 0))); ?></div><div>PrzychĂłd afiliacyjny</div></div>
            <div class="ppp-eco-card">
                <div class="ppp-eco-kpi"><?php echo esc_html($hourlyLabel); ?></div>
                <div>Limit generatora / h</div>
                <div class="ppp-eco-progress"><span class="<?php echo esc_attr($hourlyBarClass); ?>" style="width:<?php echo esc_attr(number_format($hourlyPercent, 2, '.', '')); ?>%"></span></div>
                <p style="margin:8px 0 0 0"><span class="ppp-eco-status <?php echo esc_attr($hourlyClass); ?>"><?php echo esc_html(empty($integration['seo']) ? 'nieaktywny' : ($hourlyUsed >= $hourlyLimit ? 'limit osiągnięty' : 'limit aktywny')); ?></span></p>
            </div>
        </div>

        <div class="ppp-eco-panel">
            <h2 style="margin-top:0">Trend generatora (7 dni)</h2>
            <div class="ppp-eco-chart">
                <div class="ppp-eco-chart-row">
                    <div>Aktualne 7 dni</div>
                    <div class="ppp-eco-chart-track"><div class="ppp-eco-chart-fill" style="width:<?php echo esc_attr((string) $trendCurrentWidth); ?>%"></div></div>
                    <div><strong><?php echo esc_html(number_format($trendCurrent, 1, ',', '') . '%'); ?></strong></div>
                </div>
                <div class="ppp-eco-chart-row">
                    <div>Poprzednie 7 dni</div>
                    <div class="ppp-eco-chart-track"><div class="ppp-eco-chart-fill" style="width:<?php echo esc_attr((string) $trendPreviousWidth); ?>%;background:#98a2b3"></div></div>
                    <div><strong><?php echo esc_html(number_format($trendPrevious, 1, ',', '') . '%'); ?></strong></div>
                </div>
            </div>
            <p style="margin:10px 0 0 0">Zmiana: <span class="ppp-eco-status <?php echo $trendDelta >= 0 ? 'ppp-eco-status-ok' : 'ppp-eco-status-warn'; ?>"><?php echo esc_html(($trendDelta >= 0 ? '+' : '') . number_format($trendDelta, 1, ',', '') . ' pp'); ?></span></p>
        </div>

        <div class="ppp-eco-panel">
            <h2 style="margin-top:0">Health moduĹ‚Ăłw</h2>
            <p>AdSense sync: <span class="ppp-eco-status <?php echo $adsenseSynced ? 'ppp-eco-status-ok' : 'ppp-eco-status-warn'; ?>"><?php echo $adsenseSynced ? 'zsynchronizowany' : 'wymaga synchronizacji'; ?></span></p>
            <table class="ppp-eco-table">
                <thead>
                    <tr>
                        <th>ModuĹ‚</th>
                        <th>Status</th>
                        <th>Panel</th>
                        <th>REST</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($module_rows as $module) : ?>
                        <tr>
                            <td><strong><?php echo esc_html((string) $module['name']); ?></strong></td>
                            <td>
                                <span class="ppp-eco-status <?php echo !empty($module['enabled']) ? 'ppp-eco-status-ok' : 'ppp-eco-status-neutral'; ?>">
                                    <?php echo !empty($module['enabled']) ? 'aktywny' : 'nieaktywny'; ?>
                                </span>
                            </td>
                            <td><a href="<?php echo esc_url((string) $module['url']); ?>">otwĂłrz</a></td>
                            <td><code><?php echo esc_html((string) $module['rest']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="ppp-eco-panel">
            <h2 style="margin-top:0">Cron i automatyzacje</h2>
            <p style="margin:0 0 10px 0">
                <label>
                    <input type="checkbox" id="ppp-eco-only-alerts">
                    Pokaż tylko alarmy SLA (missing/delayed)
                </label>
                <span id="ppp-eco-alert-counter" class="ppp-eco-status ppp-eco-status-neutral" style="margin-left:8px">0 / 0</span>
            </p>
            <table class="ppp-eco-table">
                <thead>
                    <tr>
                        <th>Zadanie</th>
                        <th>Event</th>
                        <th>NastÄ™pne uruchomienie</th>
                        <th>Status SLA</th>
                    </tr>
                </thead>
                <tbody id="ppp-eco-cron-tbody">
                    <?php foreach ($cron_rows_enriched as $row) : ?>
                        <?php
                        $nextTs = (int) ($row['next_ts'] ?? 0);
                        $slaState = (string) ($row['sla_state'] ?? 'missing');

                        $slaClass = 'ppp-eco-status-neutral';
                        if ($slaState === 'ok' || $slaState === 'running') {
                            $slaClass = 'ppp-eco-status-ok';
                        } elseif ($slaState === 'delayed') {
                            $slaClass = 'ppp-eco-status-warn';
                        } elseif ($slaState === 'missing') {
                            $slaClass = 'ppp-eco-status-bad';
                        }
                        ?>
                        <tr data-sla="<?php echo esc_attr($slaState); ?>">
                            <td><?php echo esc_html((string) $row['label']); ?></td>
                            <td><code><?php echo esc_html((string) $row['event']); ?></code></td>
                            <td><?php echo esc_html($nextTs > 0 ? wp_date('Y-m-d H:i:s', $nextTs) : 'niezaplanowane'); ?></td>
                            <td><span class="ppp-eco-status <?php echo esc_attr($slaClass); ?>"><?php echo esc_html($slaState); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <script>
                (function () {
                    var checkbox = document.getElementById('ppp-eco-only-alerts');
                    var tbody = document.getElementById('ppp-eco-cron-tbody');
                    var storageKey = 'pppEcoOnlyAlerts';
                    if (!checkbox || !tbody) {
                        return;
                    }

                    try {
                        var saved = window.localStorage ? window.localStorage.getItem(storageKey) : null;
                        if (saved === '1') {
                            checkbox.checked = true;
                        }
                    } catch (error) {
                        // no-op
                    }

                    var applyFilter = function () {
                        var rows = tbody.querySelectorAll('tr[data-sla]');
                        var visible = 0;
                        var alerts = 0;

                        for (var index = 0; index < rows.length; index++) {
                            var row = rows[index];
                            var state = row.getAttribute('data-sla') || '';
                            var isAlert = state === 'missing' || state === 'delayed';
                            var isVisible = (!checkbox.checked || isAlert);
                            row.style.display = isVisible ? '' : 'none';

                            if (isVisible) {
                                visible++;
                            }
                            if (isAlert) {
                                alerts++;
                            }
                        }

                        var counter = document.getElementById('ppp-eco-alert-counter');
                        if (counter) {
                            counter.textContent = alerts + ' alarmów / ' + visible + ' widocznych';
                            counter.className = 'ppp-eco-status ' + (alerts > 0 ? 'ppp-eco-status-warn' : 'ppp-eco-status-ok');
                        }

                        try {
                            if (window.localStorage) {
                                window.localStorage.setItem(storageKey, checkbox.checked ? '1' : '0');
                            }
                        } catch (error) {
                            // no-op
                        }
                    };

                    checkbox.addEventListener('change', applyFilter);
                    applyFilter();
                })();
            </script>
        </div>
    </div>
    <?php
}

function ppp_render_seo_bridge_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (function_exists('pse_render_settings_page')) {
        pse_render_settings_page();
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Integracja SEO', 'peartree-pro-platform') . '</h1>';
    echo '<div class="notice notice-warning"><p>'
        . esc_html__('Nie moĹĽna wczytaÄ‡ peartree-pro-seo-engine. Upewnij siÄ™, ĹĽe plugin SEO Engine jest aktywny i bez bĹ‚Ä™dĂłw.', 'peartree-pro-platform')
        . '</p></div>';
    echo '<p><a class="button button-primary" href="' . esc_url(admin_url('plugins.php')) . '">'
        . esc_html__('PrzejdĹş do listy pluginĂłw', 'peartree-pro-platform')
        . '</a></p>';
    echo '</div>';
}

function ppp_admin_register_settings(): void
{
    register_setting('ppp_settings_group', PPP_OPTION_KEY, [
        'type' => 'array',
        'sanitize_callback' => 'ppp_sanitize_settings',
        'default' => ppp_default_settings(),
    ]);
}
add_action('admin_init', 'ppp_admin_register_settings');

function ppp_sanitize_settings(array $input): array
{
    $sanitized = [
        'daily_tutorials' => max(1, min(20, (int) ($input['daily_tutorials'] ?? 5))),
        'adsense_manager_enabled' => empty($input['adsense_manager_enabled']) ? 0 : 1,
        'adsense_code' => (string) ($input['adsense_code'] ?? ''),
        'sponsored_banner_enabled' => empty($input['sponsored_banner_enabled']) ? 0 : 1,
        'sponsored_banner_code' => (string) ($input['sponsored_banner_code'] ?? ''),
        'sponsored_banner_target_url' => esc_url_raw((string) ($input['sponsored_banner_target_url'] ?? '')),
        'affiliate_box_enabled' => empty($input['affiliate_box_enabled']) ? 0 : 1,
        'affiliate_default_url' => esc_url_raw((string) ($input['affiliate_default_url'] ?? '')),
        'sponsored_articles_enabled' => empty($input['sponsored_articles_enabled']) ? 0 : 1,
        'sponsored_articles_count' => max(1, min(8, (int) ($input['sponsored_articles_count'] ?? 3))),
        'ads_auto_insert_enabled' => empty($input['ads_auto_insert_enabled']) ? 0 : 1,
        'ads_insert_after_paragraph' => max(1, min(12, (int) ($input['ads_insert_after_paragraph'] ?? 3))),
        'lead_form_shortcode' => sanitize_text_field((string) ($input['lead_form_shortcode'] ?? '')),
        'traffic_monthly_views' => max(0, (int) ($input['traffic_monthly_views'] ?? 0)),
        'affiliate_monthly_revenue' => max(0, (float) ($input['affiliate_monthly_revenue'] ?? 0)),
    ];

    if (defined('PSE_OPTION_KEY')) {
        $seo_settings = get_option(PSE_OPTION_KEY, []);
        if (!is_array($seo_settings)) {
            $seo_settings = [];
        }

        $seo_settings['content_daily_count'] = (int) $sanitized['daily_tutorials'];
        update_option(PSE_OPTION_KEY, $seo_settings, false);
    }

    $publisher_id = ppp_extract_adsense_publisher_id((string) $sanitized['adsense_code']);

    if (defined('PAA_VERSION') || get_option('paa_adsense_settings', null) !== null) {
        $paa_settings = get_option('paa_adsense_settings', []);
        if (!is_array($paa_settings)) {
            $paa_settings = [];
        }

        $paa_settings['publisher_id'] = $publisher_id;
        $paa_settings['adsense_script'] = (string) $sanitized['adsense_code'];
        $paa_settings['auto_ads'] = (int) ($sanitized['adsense_manager_enabled'] ?? 0);
        update_option('paa_adsense_settings', $paa_settings, false);
    }

    if (defined('PPAE_VERSION') || get_option('ppae_adsense_settings', null) !== null) {
        $ppae_settings = get_option('ppae_adsense_settings', []);
        if (!is_array($ppae_settings)) {
            $ppae_settings = [];
        }

        $ppae_settings['publisher_id'] = $publisher_id;
        $ppae_settings['script'] = (string) $sanitized['adsense_code'];
        $ppae_settings['auto_ads'] = (int) ($sanitized['adsense_manager_enabled'] ?? 0);
        update_option('ppae_adsense_settings', $ppae_settings, false);

        $ppae_general = get_option('ppae_general_settings', []);
        if (!is_array($ppae_general)) {
            $ppae_general = [];
        }

        $ppae_general['daily_generation_limit'] = (int) $sanitized['daily_tutorials'];
        update_option('ppae_general_settings', $ppae_general, false);
    }

    do_action('ppp_settings_synchronized', $sanitized);

    return $sanitized;
}

function ppp_count_posts(string $post_type): int
{
    $counts = wp_count_posts($post_type);
    return (int) ($counts->publish ?? 0);
}

function ppp_get_portal_kpis(): array
{
    $settings = ppp_get_settings();
    $last_generation = get_option('ppp_last_generation', []);
    $generator_kpis = function_exists('pse_get_generator_kpis')
        ? pse_get_generator_kpis(7)
        : ['success_rate' => 0.0, 'runs' => 0, 'created' => 0, 'requested' => 0];
    $generator_kpis_14 = function_exists('pse_get_generator_kpis')
        ? pse_get_generator_kpis(14)
        : $generator_kpis;

    $prev7_requested = max(0, (int) ($generator_kpis_14['requested'] ?? 0) - (int) ($generator_kpis['requested'] ?? 0));
    $prev7_created = max(0, (int) ($generator_kpis_14['created'] ?? 0) - (int) ($generator_kpis['created'] ?? 0));
    $prev7_success_rate = $prev7_requested > 0 ? round(($prev7_created / $prev7_requested) * 100, 1) : 0.0;
    $trend_pp = round(((float) ($generator_kpis['success_rate'] ?? 0)) - $prev7_success_rate, 1);

    $generator_status = 'Brak danych';
    if ((int) ($generator_kpis['runs'] ?? 0) > 0) {
        if ((float) ($generator_kpis['success_rate'] ?? 0) >= 80) {
            $generator_status = 'Stabilny';
        } elseif ((float) ($generator_kpis['success_rate'] ?? 0) >= 50) {
            $generator_status = 'Uwaga';
        } else {
            $generator_status = 'Wymaga interwencji';
        }
    }

    return [
        'articles_count' => ppp_count_posts('poradnik'),
        'rankings_count' => ppp_count_posts('ranking'),
        'reviews_count' => ppp_count_posts('recenzja'),
        'last_generation_created' => (int) ($last_generation['created'] ?? 0),
        'last_generation_time' => (string) ($last_generation['time'] ?? 'brak danych'),
        'traffic_monthly_views' => (int) ($settings['traffic_monthly_views'] ?? 0),
        'affiliate_monthly_revenue' => (float) ($settings['affiliate_monthly_revenue'] ?? 0),
        'generator_success_rate_7d' => (float) ($generator_kpis['success_rate'] ?? 0),
        'generator_runs_7d' => (int) ($generator_kpis['runs'] ?? 0),
        'generator_created_7d' => (int) ($generator_kpis['created'] ?? 0),
        'generator_requested_7d' => (int) ($generator_kpis['requested'] ?? 0),
        'generator_prev7_success_rate' => (float) $prev7_success_rate,
        'generator_trend_pp' => (float) $trend_pp,
        'generator_status' => (string) $generator_status,
        'ad_click_stats_last_reset' => (string) get_option('ppp_ad_click_stats_last_reset', ''),
    ];
}

function ppp_get_ads_marketplace_summary(): array
{
    $cache_key = 'ppp_ads_marketplace_summary_v1';
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $summary = [
        'available' => defined('PPAM_VERSION') || class_exists('PPAM\\Core\\Marketplace'),
        'campaigns' => 0,
        'active' => 0,
        'pending_payment' => 0,
        'pending_approval' => 0,
        'paused' => 0,
        'completed' => 0,
        'rejected' => 0,
        'total_budget' => 0.0,
    ];

    if (!$summary['available'] || !post_type_exists('ppam_campaign')) {
        return $summary;
    }

    $total_query = new \WP_Query([
        'post_type' => 'ppam_campaign',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
    ]);

    $summary['campaigns'] = (int) $total_query->found_posts;

    $status_keys = ['active', 'pending_payment', 'pending_approval', 'paused', 'completed', 'rejected'];
    foreach ($status_keys as $status_key) {
        $status_query = new \WP_Query([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'meta_query' => [
                [
                    'key' => '_ppam_status',
                    'value' => $status_key,
                ],
            ],
        ]);

        $summary[$status_key] = (int) $status_query->found_posts;
    }

    global $wpdb;
    $budget_sum = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(CAST(pm.meta_value AS DECIMAL(20,2)))
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
             WHERE p.post_type = %s
               AND p.post_status = %s
               AND pm.meta_key = %s",
            'ppam_campaign',
            'publish',
            '_ppam_budget'
        )
    );

    $summary['total_budget'] = (float) ($budget_sum ?? 0.0);

    set_transient($cache_key, $summary, MINUTE_IN_SECONDS);

    return $summary;
}

function ppp_get_plugin_integration_summary(): array
{
    global $wpdb;

    $products_table = $wpdb->prefix . 'peartree_affiliate_products';
    $links_table = $wpdb->prefix . 'peartree_affiliate_links';
    $keywords_table = $wpdb->prefix . 'peartree_affiliate_keywords';
    $clicks_table = $wpdb->prefix . 'peartree_affiliate_clicks';

    $table_exists = static function (string $table_name) use ($wpdb): bool {
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        return is_string($found) && $found !== '';
    };

    $ppp_settings = ppp_get_settings();
    $paa_settings = get_option('paa_adsense_settings', []);
    $ppae_settings = get_option('ppae_adsense_settings', []);

    if (!is_array($paa_settings)) {
        $paa_settings = [];
    }

    if (!is_array($ppae_settings)) {
        $ppae_settings = [];
    }

    $platform_script = (string) ($ppp_settings['adsense_code'] ?? '');
    $paa_script = (string) ($paa_settings['adsense_script'] ?? '');
    $ppae_script = (string) ($ppae_settings['script'] ?? '');

    $summary = [
        'platform' => defined('PPP_VERSION'),
        'seo' => defined('PSE_VERSION') || function_exists('pse_get_settings'),
        'ads_marketplace' => defined('PPAM_VERSION') || class_exists('PPAM\\Core\\Marketplace'),
        'afiliacja_adsense' => defined('PAA_VERSION') || class_exists('Poradnik\\AfilacjaAdsense\\Core\\Kernel'),
        'programmatic_affiliate' => defined('PPAE_VERSION') || class_exists('PearTree\\ProgrammaticAffiliate\\Core\\Kernel'),
        'products_count' => 0,
        'links_count' => 0,
        'keywords_count' => 0,
        'clicks_30d' => 0,
        'adsense_synced' => true,
    ];

    if ($table_exists($products_table)) {
        $summary['products_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$products_table}");
    }

    if ($table_exists($links_table)) {
        $summary['links_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$links_table}");
    }

    if ($table_exists($keywords_table)) {
        $summary['keywords_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$keywords_table}");
    }

    if ($table_exists($clicks_table)) {
        $summary['clicks_30d'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$clicks_table} WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }

    if ($platform_script !== '' && (($paa_script !== '' && $paa_script !== $platform_script) || ($ppae_script !== '' && $ppae_script !== $platform_script))) {
        $summary['adsense_synced'] = false;
    }

    return $summary;
}

function ppp_flush_ads_marketplace_summary_cache(int $post_id = 0): void
{
    if ($post_id > 0 && get_post_type($post_id) !== 'ppam_campaign') {
        return;
    }

    delete_transient('ppp_ads_marketplace_summary_v1');
}
add_action('save_post_ppam_campaign', 'ppp_flush_ads_marketplace_summary_cache');
add_action('deleted_post', 'ppp_flush_ads_marketplace_summary_cache');
add_action('trashed_post', 'ppp_flush_ads_marketplace_summary_cache');
add_action('untrashed_post', 'ppp_flush_ads_marketplace_summary_cache');

function ppp_rest_permissions(): bool
{
    return current_user_can('manage_options');
}

function ppp_register_rest_routes(): void
{
    register_rest_route('ppp/v1', '/status', [
        'methods' => 'GET',
        'callback' => 'ppp_rest_get_status',
        'permission_callback' => 'ppp_rest_permissions',
    ]);

    register_rest_route('ppp/v1', '/kpis', [
        'methods' => 'GET',
        'callback' => 'ppp_rest_get_kpis',
        'permission_callback' => 'ppp_rest_permissions',
    ]);
}
add_action('rest_api_init', 'ppp_register_rest_routes');

function ppp_rest_get_status(\WP_REST_Request $request): \WP_REST_Response
{
    unset($request);

    $payload = [
        'ok' => true,
        'time' => current_time('mysql'),
        'integration' => ppp_get_plugin_integration_summary(),
        'ads_marketplace' => ppp_get_ads_marketplace_summary(),
    ];

    return new \WP_REST_Response($payload, 200);
}

function ppp_rest_get_kpis(\WP_REST_Request $request): \WP_REST_Response
{
    unset($request);

    return new \WP_REST_Response([
        'ok' => true,
        'time' => current_time('mysql'),
        'kpis' => ppp_get_portal_kpis(),
    ], 200);
}

function ppp_render_dashboard(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $ad_stats_notice = '';

    if (isset($_POST['ppp_run_generator']) && check_admin_referer('ppp_run_generator_action')) {
        $result = ppp_generate_daily_tutorials();
        echo '<div class="notice notice-success"><p>' . esc_html(sprintf('Generator SEO uruchomiony. PrĂłby: %d, utworzone: %d', (int) $result['attempted'], (int) $result['created'])) . '</p></div>';
    }

    if (isset($_POST['ppp_reset_ad_click_stats'])) {
        check_admin_referer('ppp_reset_ad_click_stats_action');
        update_option('ppp_ad_click_stats', [
            'affiliate' => 0,
            'sponsored_banner' => 0,
            'sponsored_article' => 0,
        ], false);
        update_option('ppp_ad_click_stats_last_reset', current_time('mysql'), false);
        $ad_stats_notice = 'Statystyki klikniÄ™Ä‡ zostaĹ‚y wyzerowane.';
    }

    $settings = ppp_get_settings();
    $kpis = ppp_get_portal_kpis();
    $ads_marketplace = ppp_get_ads_marketplace_summary();
    $integration = ppp_get_plugin_integration_summary();

    $active_tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : '';
    if ($active_tab === '') {
        $current_page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : 'ppp-dashboard';
        $page_to_tab = [
            'ppp-dashboard' => 'overview',
            'ppp-dashboard-generator' => 'generator',
            'ppp-dashboard-settings' => 'settings',
            'ppp-dashboard-engines' => 'engines',
        ];
        $active_tab = $page_to_tab[$current_page] ?? 'overview';
    }
    $tabs = [
        'overview' => __('PrzeglÄ…d', 'peartree-pro-platform'),
        'settings' => __('Ustawienia', 'peartree-pro-platform'),
        'ads' => __('Reklamy i afiliacja', 'peartree-pro-platform'),
        'generator' => __('Generator', 'peartree-pro-platform'),
        'engines' => __('Silniki', 'peartree-pro-platform'),
        'seo' => __('Integracja SEO', 'peartree-pro-platform'),
    ];

    if (!isset($tabs[$active_tab])) {
        $active_tab = 'overview';
    }

    $dashboard_url = admin_url('admin.php?page=ppp-dashboard');
    $seo_engine_url = admin_url('admin.php?page=peartree-pro-seo-engine');
    $marketplace_url = admin_url('admin.php?page=ppam-marketplace');
    $campaigns_url = admin_url('admin.php?page=ppam-campaigns');
    $orders_url = admin_url('admin.php?page=ppam-orders');
    $monetization_url = admin_url('admin.php?page=paa-monetization');
    $programmatic_url = admin_url('admin.php?page=ppae-dashboard');

    ?>
    <div class="wrap">
        <h1>peartree.pro Dashboard</h1>
        <h2 class="nav-tab-wrapper" style="margin-bottom:14px">
            <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                <?php $tab_url = add_query_arg(['page' => 'ppp-dashboard', 'tab' => $tab_key], admin_url('admin.php')); ?>
                <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($tab_label); ?></a>
            <?php endforeach; ?>
        </h2>
        <style>
            .ppp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin:14px 0}
            .ppp-card{background:#fff;border:1px solid #d7deea;border-radius:12px;padding:16px}
            .ppp-kpi{font-size:28px;font-weight:700;color:#0b5bd3}
            .ppp-panel{background:#fff;border:1px solid #d7deea;border-radius:12px;padding:16px;margin:0 0 14px 0}
            .ppp-links{display:flex;flex-wrap:wrap;gap:8px}
            .ppp-hero{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start}
            .ppp-meta{font-size:12px;color:#667085;margin-top:6px}
            .ppp-status{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid transparent}
            .ppp-status-ok{background:#e7f7ee;color:#12663b;border-color:#bde6cc}
            .ppp-status-warn{background:#fff4df;color:#7a4b00;border-color:#ffd998}
            .ppp-status-bad{background:#ffe7e7;color:#8f1d1d;border-color:#ffc2c2}
            .ppp-status-neutral{background:#eef2f7;color:#344054;border-color:#d7deea}
        </style>

        <?php if ($active_tab === 'overview') : ?>
            <?php
            $trend_label = ((float) $kpis['generator_trend_pp'] >= 0 ? '+' : '') . number_format((float) $kpis['generator_trend_pp'], 1, ',', '') . ' pp';
            $status_class = 'ppp-status-neutral';
            if ((string) $kpis['generator_status'] === 'Stabilny') {
                $status_class = 'ppp-status-ok';
            } elseif ((string) $kpis['generator_status'] === 'Uwaga') {
                $status_class = 'ppp-status-warn';
            } elseif ((string) $kpis['generator_status'] === 'Wymaga interwencji') {
                $status_class = 'ppp-status-bad';
            }

            $modules = [
                'Platform' => (bool) ($integration['platform'] ?? false),
                'SEO Engine' => (bool) ($integration['seo'] ?? false),
                'Ads Marketplace' => (bool) ($integration['ads_marketplace'] ?? false),
                'Afiliacja + AdSense' => (bool) ($integration['afiliacja_adsense'] ?? false),
                'Programmatic Affiliate' => (bool) ($integration['programmatic_affiliate'] ?? false),
            ];
            ?>
            <div class="ppp-panel ppp-hero">
                <div>
                    <h2 style="margin:0">Operacyjne centrum peartree.pro</h2>
                    <p style="margin:8px 0 0 0">Jeden dashboard do zarządzania SEO, reklamą, afiliacją i automatyzacją treści.</p>
                    <p class="ppp-meta">Synchronizacja AdSense: <span class="ppp-status <?php echo !empty($integration['adsense_synced']) ? 'ppp-status-ok' : 'ppp-status-warn'; ?>"><?php echo !empty($integration['adsense_synced']) ? 'zsynchronizowana' : 'wymaga ujednolicenia'; ?></span></p>
                </div>
                <div class="ppp-links">
                    <a class="button button-primary" href="<?php echo esc_url($dashboard_url); ?>">Dashboard platformy</a>
                    <a class="button button-secondary" href="<?php echo esc_url($seo_engine_url); ?>">SEO Engine</a>
                    <a class="button button-secondary" href="<?php echo esc_url($marketplace_url); ?>">Ads Marketplace</a>
                    <a class="button button-secondary" href="<?php echo esc_url($monetization_url); ?>">Afiliacja + AdSense</a>
                    <a class="button button-secondary" href="<?php echo esc_url($programmatic_url); ?>">Programmatic Affiliate</a>
                </div>
            </div>

            <div class="ppp-grid">
                <?php foreach ($modules as $module_name => $enabled) : ?>
                    <div class="ppp-card">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                            <strong><?php echo esc_html($module_name); ?></strong>
                            <span class="ppp-status <?php echo $enabled ? 'ppp-status-ok' : 'ppp-status-neutral'; ?>"><?php echo $enabled ? 'aktywny' : 'nieaktywny'; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="ppp-grid">
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html((string) $kpis['articles_count']); ?></div><div>ArtykuĹ‚y poradnikowe</div></div>
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html((string) $kpis['rankings_count']); ?></div><div>Rankingi</div></div>
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html((string) $kpis['reviews_count']); ?></div><div>Recenzje</div></div>
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html((string) $kpis['last_generation_created']); ?></div><div>Ostatnia generacja</div></div>
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html(number_format($kpis['traffic_monthly_views'], 0, ',', ' ')); ?></div><div>MiesiÄ™czny ruch</div></div>
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html(ppp_format_pln((float) $kpis['affiliate_monthly_revenue'])); ?></div><div>PrzychĂłd afiliacyjny</div></div>
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html(number_format((float) $kpis['generator_success_rate_7d'], 1, ',', '') . '%'); ?></div><div>SkutecznoĹ›Ä‡ generatora (7 dni)</div></div>
                <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html($trend_label); ?></div><div>Trend vs poprzednie 7 dni</div></div>
            </div>
            <div class="ppp-panel">
                <h2>Centrum zarzÄ…dzania</h2>
                <p>UĹĽyj zakĹ‚adek, aby zarzÄ…dzaÄ‡ generatorem, ustawieniami i integracjÄ… SEO bez powielania konfiguracji.</p>
                <p><?php echo esc_html(sprintf('Generator 7 dni: uruchomieĹ„ %d | utworzone %d / %d.', (int) $kpis['generator_runs_7d'], (int) $kpis['generator_created_7d'], (int) $kpis['generator_requested_7d'])); ?></p>
                <p>Status generatora: <span class="ppp-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html((string) $kpis['generator_status']); ?></span> | Poprzednie 7 dni: <?php echo esc_html(number_format((float) $kpis['generator_prev7_success_rate'], 1, ',', '') . '%'); ?></p>
                <p>Ostatni reset statystyk klikniÄ™Ä‡: <?php echo esc_html(((string) ($kpis['ad_click_stats_last_reset'] ?? '')) !== '' ? (string) $kpis['ad_click_stats_last_reset'] : 'brak'); ?></p>
                <div class="ppp-links">
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=poradnik')); ?>">Poradniki</a>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=ranking')); ?>">Rankingi</a>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=recenzja')); ?>">Recenzje</a>
                    <a class="button button-secondary" href="<?php echo esc_url($seo_engine_url); ?>">Panel SEO</a>
                    <a class="button button-secondary" href="<?php echo esc_url($campaigns_url); ?>">Kampanie reklamowe</a>
                    <a class="button button-secondary" href="<?php echo esc_url($orders_url); ?>">Zamówienia reklamowe</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'generator') : ?>
            <div class="ppp-panel">
                <h2>SEO Generator</h2>
                <p>device Ă— problem â†’ automatyczne poradniki SEO.</p>
                <form method="post">
                    <?php wp_nonce_field('ppp_run_generator_action'); ?>
                    <input type="hidden" name="ppp_run_generator" value="1">
                    <?php submit_button('Uruchom generator teraz', 'primary', '', false); ?>
                </form>
                <p><?php echo esc_html(sprintf('Ostatnio: %s', $kpis['last_generation_time'])); ?></p>
                <p><a href="<?php echo esc_url(add_query_arg(['page' => 'peartree-pro-seo-engine', 'tab' => 'tools'], admin_url('admin.php'))); ?>">NarzÄ™dzia generatora SEO (zaawansowane)</a></p>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'settings') : ?>
            <div class="ppp-panel">
                <h2>Ustawienia platformy</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('ppp_settings_group'); ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">Ile poradnikĂłw dziennie</th>
                            <td><input type="number" min="1" max="20" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[daily_tutorials]" value="<?php echo esc_attr((string) ($settings['daily_tutorials'] ?? 5)); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Shortcode formularza leadĂłw</th>
                            <td><input type="text" class="regular-text" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[lead_form_shortcode]" value="<?php echo esc_attr((string) ($settings['lead_form_shortcode'] ?? '')); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">MiesiÄ™czny ruch (views)</th>
                            <td><input type="number" min="0" step="1" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[traffic_monthly_views]" value="<?php echo esc_attr((string) (int) ($settings['traffic_monthly_views'] ?? 0)); ?>"></td>
                        </tr>
                    </table>
                    <?php submit_button('Zapisz ustawienia'); ?>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'ads') : ?>
            <div class="ppp-panel">
                <h2>Reklamy i afiliacja</h2>
                <?php if (empty($integration['adsense_synced'])) : ?>
                    <div class="notice notice-warning inline"><p>Wykryto rozbieżność konfiguracji AdSense między modułami. Zapisz ustawienia w tej zakładce, aby ujednolicić konfigurację.</p></div>
                <?php endif; ?>
                <h3>Integracja Marketplace Reklam</h3>
                <?php if (!empty($ads_marketplace['available'])) : ?>
                    <div class="ppp-grid" style="margin-top:10px">
                        <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html((string) (int) $ads_marketplace['campaigns']); ?></div><div>Kampanie Ĺ‚Ä…cznie</div></div>
                        <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html((string) (int) $ads_marketplace['active']); ?></div><div>Aktywne kampanie</div></div>
                        <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html((string) (int) $ads_marketplace['pending_payment']); ?></div><div>OczekujÄ… na pĹ‚atnoĹ›Ä‡</div></div>
                        <div class="ppp-card"><div class="ppp-kpi"><?php echo esc_html(number_format((float) $ads_marketplace['total_budget'], 2, ',', ' ')); ?> PLN</div><div>ĹÄ…czny budĹĽet</div></div>
                    </div>
                    <p class="ppp-links" style="margin-top:8px">
                        <a class="button button-primary" href="<?php echo esc_url($marketplace_url); ?>">Marketplace reklam</a>
                        <a class="button button-secondary" href="<?php echo esc_url($campaigns_url); ?>">Kampanie reklamowe</a>
                        <a class="button button-secondary" href="<?php echo esc_url($orders_url); ?>">ZamĂłwienia reklamowe</a>
                        <a class="button button-secondary" href="<?php echo esc_url(home_url('/panel-reklamodawcy/')); ?>" target="_blank" rel="noopener">Panel reklamodawcy</a>
                        <a class="button button-secondary" href="<?php echo esc_url(home_url('/oferty-sponsorowane/')); ?>" target="_blank" rel="noopener">Oferty sponsorowane</a>
                    </p>
                <?php else : ?>
                    <div class="notice notice-warning inline"><p>Plugin peartree.pro Ads Marketplace jest nieaktywny. Aktywuj go, aby zarzÄ…dzaÄ‡ kampaniami i pĹ‚atnoĹ›ciami reklamowymi z dashboardu.</p></div>
                    <p><a class="button button-secondary" href="<?php echo esc_url(admin_url('plugins.php')); ?>">PrzejdĹş do pluginĂłw</a></p>
                <?php endif; ?>
                <?php if ($ad_stats_notice !== '') : ?>
                    <div class="notice notice-success"><p><?php echo esc_html($ad_stats_notice); ?></p></div>
                <?php endif; ?>
                <form method="post" action="options.php">
                    <?php settings_fields('ppp_settings_group'); ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">AdSense manager</th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[adsense_manager_enabled]" value="1" <?php checked((int) ($settings['adsense_manager_enabled'] ?? 1), 1); ?>> WĹ‚Ä…cz</label></td>
                        </tr>
                        <tr>
                            <th scope="row">Google AdSense code</th>
                            <td><textarea class="large-text" rows="4" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[adsense_code]"><?php echo esc_textarea((string) ($settings['adsense_code'] ?? '')); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row">Banery sponsorowane</th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[sponsored_banner_enabled]" value="1" <?php checked((int) ($settings['sponsored_banner_enabled'] ?? 0), 1); ?>> WĹ‚Ä…cz</label></td>
                        </tr>
                        <tr>
                            <th scope="row">Kod banera sponsorowanego</th>
                            <td><textarea class="large-text" rows="4" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[sponsored_banner_code]"><?php echo esc_textarea((string) ($settings['sponsored_banner_code'] ?? '')); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row">URL banera sponsorowanego</th>
                            <td><input type="url" class="regular-text" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[sponsored_banner_target_url]" value="<?php echo esc_attr((string) ($settings['sponsored_banner_target_url'] ?? '')); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Box afiliacyjny</th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[affiliate_box_enabled]" value="1" <?php checked((int) ($settings['affiliate_box_enabled'] ?? 1), 1); ?>> WĹ‚Ä…cz</label></td>
                        </tr>
                        <tr>
                            <th scope="row">DomyĹ›lny URL afiliacyjny</th>
                            <td><input type="url" class="regular-text" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[affiliate_default_url]" value="<?php echo esc_attr((string) ($settings['affiliate_default_url'] ?? '')); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">ArtykuĹ‚y sponsorowane</th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[sponsored_articles_enabled]" value="1" <?php checked((int) ($settings['sponsored_articles_enabled'] ?? 0), 1); ?>> WĹ‚Ä…cz</label></td>
                        </tr>
                        <tr>
                            <th scope="row">Liczba artykuĹ‚Ăłw sponsorowanych</th>
                            <td><input type="number" min="1" max="8" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[sponsored_articles_count]" value="<?php echo esc_attr((string) (int) ($settings['sponsored_articles_count'] ?? 3)); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Automatyczne wstawianie reklam</th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[ads_auto_insert_enabled]" value="1" <?php checked((int) ($settings['ads_auto_insert_enabled'] ?? 1), 1); ?>> WĹ‚Ä…cz</label></td>
                        </tr>
                        <tr>
                            <th scope="row">Wstaw po akapicie nr</th>
                            <td><input type="number" min="1" max="12" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[ads_insert_after_paragraph]" value="<?php echo esc_attr((string) (int) ($settings['ads_insert_after_paragraph'] ?? 3)); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">MiesiÄ™czny przychĂłd afiliacyjny (PLN)</th>
                            <td><input type="number" min="0" step="0.01" name="<?php echo esc_attr(PPP_OPTION_KEY); ?>[affiliate_monthly_revenue]" value="<?php echo esc_attr((string) (float) ($settings['affiliate_monthly_revenue'] ?? 0)); ?>"></td>
                        </tr>
                    </table>
                    <?php $click_stats = ppp_get_ad_click_stats(); ?>
                    <h3>Statystyki klikniÄ™Ä‡</h3>
                    <table class="widefat striped" style="max-width:700px;margin:8px 0 16px 0">
                        <thead><tr><th>Element</th><th>KlikniÄ™cia</th></tr></thead>
                        <tbody>
                            <tr><td>Box afiliacyjny</td><td><?php echo esc_html((string) (int) ($click_stats['affiliate'] ?? 0)); ?></td></tr>
                            <tr><td>Banery sponsorowane</td><td><?php echo esc_html((string) (int) ($click_stats['sponsored_banner'] ?? 0)); ?></td></tr>
                            <tr><td>ArtykuĹ‚y sponsorowane</td><td><?php echo esc_html((string) (int) ($click_stats['sponsored_article'] ?? 0)); ?></td></tr>
                        </tbody>
                    </table>
                    <?php $last_reset = (string) get_option('ppp_ad_click_stats_last_reset', ''); ?>
                    <p><strong>Ostatni reset:</strong> <?php echo esc_html($last_reset !== '' ? $last_reset : 'brak'); ?></p>
                    <p>
                        <?php wp_nonce_field('ppp_reset_ad_click_stats_action'); ?>
                        <?php submit_button('Wyzeruj statystyki klikniÄ™Ä‡', 'delete', 'ppp_reset_ad_click_stats', false, ['onclick' => "return confirm('Na pewno wyzerowaÄ‡ statystyki klikniÄ™Ä‡?');"]); ?>
                    </p>
                    <?php submit_button('Zapisz ustawienia reklam'); ?>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'engines') : ?>
            <div class="ppp-panel">
                <h2>Silniki</h2>
                <ul style="list-style:disc;padding-left:18px">
                    <li>Diagnostic engine: shortcode <code>[pp_diagnostic_wizard]</code></li>
                    <li>Ranking manager: metabox w typie <code>ranking</code></li>
                    <li>Monetyzacja: AdSense + afiliacja + lead form</li>
                    <li>Automatyzacja: cron publikacji poradnikĂłw</li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'seo') : ?>
            <div class="ppp-panel">
                <h2>Integracja SEO</h2>
                <p>Zaawansowane SEO, linkowanie i kontrola kondycji linkĂłw sÄ… zarzÄ…dzane w dedykowanym panelu SEO.</p>
                <p style="margin:0">
                    <a class="button button-primary" href="<?php echo esc_url($seo_engine_url); ?>">OtwĂłrz panel SEO</a>
                    <a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['page' => 'peartree-pro-seo-engine', 'tab' => 'settings'], admin_url('admin.php'))); ?>">SEO ustawienia</a>
                    <a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['page' => 'peartree-pro-seo-engine', 'tab' => 'health'], admin_url('admin.php'))); ?>">SEO kondycja linkĂłw</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}


