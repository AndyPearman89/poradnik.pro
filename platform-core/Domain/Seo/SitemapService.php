<?php

namespace Poradnik\Platform\Domain\Seo;

use WP_Query;
use WP_Term;

if (! defined('ABSPATH')) {
    exit;
}

final class SitemapService
{
    private const URLS_PER_SITEMAP = 500;

    public static function init(): void
    {
        add_action('init', [self::class, 'registerRewriteRules'], 9);
        add_filter('query_vars', [self::class, 'registerQueryVars']);
        add_action('template_redirect', [self::class, 'handleRequest'], 1);
    }

    public static function registerRewriteRules(): void
    {
        add_rewrite_tag('%poradnik_sitemap%', '([^&]+)');
        add_rewrite_tag('%poradnik_sitemap_section%', '([^&]+)');
        add_rewrite_tag('%poradnik_sitemap_page%', '([0-9]+)');

        add_rewrite_rule('^sitemap\.xml$', 'index.php?poradnik_sitemap=index', 'top');
        add_rewrite_rule('^sitemap-([a-z0-9\-]+)\.xml$', 'index.php?poradnik_sitemap=section&poradnik_sitemap_section=$matches[1]&poradnik_sitemap_page=1', 'top');
        add_rewrite_rule('^sitemap-([a-z0-9\-]+)-([0-9]+)\.xml$', 'index.php?poradnik_sitemap=section&poradnik_sitemap_section=$matches[1]&poradnik_sitemap_page=$matches[2]', 'top');
    }

    /**
     * @param array<int, string> $queryVars
     * @return array<int, string>
     */
    public static function registerQueryVars(array $queryVars): array
    {
        $queryVars[] = 'poradnik_sitemap';
        $queryVars[] = 'poradnik_sitemap_section';
        $queryVars[] = 'poradnik_sitemap_page';

        return $queryVars;
    }

    public static function handleRequest(): void
    {
        $mode = get_query_var('poradnik_sitemap');
        if (! is_string($mode) || $mode === '') {
            return;
        }

        if ($mode === 'index') {
            self::renderSitemapIndex();
            exit;
        }

        if ($mode === 'section') {
            $section = sanitize_key((string) get_query_var('poradnik_sitemap_section'));
            $page = max(1, absint((string) get_query_var('poradnik_sitemap_page')));
            self::renderSectionSitemap($section, $page);
            exit;
        }

        status_header(404);
        exit;
    }

    private static function renderSitemapIndex(): void
    {
        $sections = self::sections();
        $items = [];

        foreach ($sections as $section => $config) {
            $total = (int) call_user_func($config['count']);
            if ($total < 1) {
                continue;
            }

            $pages = (int) ceil($total / self::URLS_PER_SITEMAP);
            $lastmod = (string) call_user_func($config['lastmod']);

            for ($page = 1; $page <= $pages; $page++) {
                $items[] = [
                    'loc' => home_url('/sitemap-' . $section . '-' . $page . '.xml'),
                    'lastmod' => $lastmod,
                ];
            }
        }

        self::sendXmlHeader();
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($items as $item) {
            echo '  <sitemap>' . "\n";
            echo '    <loc>' . self::xml($item['loc']) . '</loc>' . "\n";
            if ((string) $item['lastmod'] !== '') {
                echo '    <lastmod>' . self::xml((string) $item['lastmod']) . '</lastmod>' . "\n";
            }
            echo '  </sitemap>' . "\n";
        }

        echo '</sitemapindex>';
    }

    private static function renderSectionSitemap(string $section, int $page): void
    {
        $sections = self::sections();
        if (! isset($sections[$section])) {
            status_header(404);
            exit;
        }

        $urls = call_user_func($sections[$section]['items'], $page);
        if (! is_array($urls)) {
            $urls = [];
        }

        self::sendXmlHeader();
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            if (! is_array($url)) {
                continue;
            }

            $loc = isset($url['loc']) ? (string) $url['loc'] : '';
            if ($loc === '') {
                continue;
            }

            echo '  <url>' . "\n";
            echo '    <loc>' . self::xml($loc) . '</loc>' . "\n";

            $lastmod = isset($url['lastmod']) ? (string) $url['lastmod'] : '';
            if ($lastmod !== '') {
                echo '    <lastmod>' . self::xml($lastmod) . '</lastmod>' . "\n";
            }

            echo '  </url>' . "\n";
        }

        echo '</urlset>';
    }

    /**
     * @return array<string, array{count:callable,lastmod:callable,items:callable}>
     */
    private static function sections(): array
    {
        return [
            'static' => [
                'count' => [self::class, 'countStaticUrls'],
                'lastmod' => [self::class, 'lastModifiedStatic'],
                'items' => [self::class, 'staticUrlsForPage'],
            ],
            'guide' => self::postTypeSection('guide'),
            'ranking' => self::postTypeSection('ranking'),
            'review' => self::postTypeSection('review'),
            'comparison' => self::postTypeSection('comparison'),
            'tool' => self::postTypeSection('tool'),
            'news' => self::postTypeSection('news'),
            'tag' => self::taxonomySection('post_tag'),
            'category' => self::taxonomySection('category'),
            'author' => [
                'count' => [self::class, 'countAuthors'],
                'lastmod' => [self::class, 'lastModifiedAuthors'],
                'items' => [self::class, 'authorUrlsForPage'],
            ],
        ];
    }

    /**
     * @return array{count:callable,lastmod:callable,items:callable}
     */
    private static function postTypeSection(string $postType): array
    {
        return [
            'count' => static fn (): int => self::countPublishedPosts($postType),
            'lastmod' => static fn (): string => self::lastModifiedPostType($postType),
            'items' => static fn (int $page): array => self::postTypeUrlsForPage($postType, $page),
        ];
    }

    /**
     * @return array{count:callable,lastmod:callable,items:callable}
     */
    private static function taxonomySection(string $taxonomy): array
    {
        return [
            'count' => static fn (): int => self::countTerms($taxonomy),
            'lastmod' => static fn (): string => self::lastModifiedTerms($taxonomy),
            'items' => static fn (int $page): array => self::termUrlsForPage($taxonomy, $page),
        ];
    }

    private static function countPublishedPosts(string $postType): int
    {
        $count = wp_count_posts($postType);
        if (! is_object($count) || ! isset($count->publish)) {
            return 0;
        }

        return max(0, (int) $count->publish);
    }

    private static function lastModifiedPostType(string $postType): string
    {
        $query = new WP_Query([
            'post_type' => $postType,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        if (empty($query->posts)) {
            return '';
        }

        return (string) get_post_modified_time(DATE_ATOM, true, (int) $query->posts[0]);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private static function postTypeUrlsForPage(string $postType, int $page): array
    {
        $query = new WP_Query([
            'post_type' => $postType,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => self::URLS_PER_SITEMAP,
            'paged' => $page,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        $items = [];
        foreach ($query->posts as $postId) {
            $postId = (int) $postId;
            $url = get_permalink($postId);
            if (! is_string($url) || $url === '') {
                continue;
            }

            $items[] = [
                'loc' => $url,
                'lastmod' => (string) get_post_modified_time(DATE_ATOM, true, $postId),
            ];
        }

        return $items;
    }

    private static function countTerms(string $taxonomy): int
    {
        if (! taxonomy_exists($taxonomy)) {
            return 0;
        }

        $count = wp_count_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ]);

        return is_numeric($count) ? (int) $count : 0;
    }

    private static function lastModifiedTerms(string $taxonomy): string
    {
        if (! taxonomy_exists($taxonomy)) {
            return '';
        }

        return gmdate(DATE_ATOM);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private static function termUrlsForPage(string $taxonomy, int $page): array
    {
        if (! taxonomy_exists($taxonomy)) {
            return [];
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => self::URLS_PER_SITEMAP,
            'offset' => ($page - 1) * self::URLS_PER_SITEMAP,
            'orderby' => 'term_id',
            'order' => 'ASC',
        ]);

        if (! is_array($terms)) {
            return [];
        }

        $items = [];
        foreach ($terms as $term) {
            if (! $term instanceof WP_Term) {
                continue;
            }

            $url = get_term_link($term);
            if (! is_string($url) || $url === '') {
                continue;
            }

            $items[] = [
                'loc' => $url,
                'lastmod' => gmdate(DATE_ATOM),
            ];
        }

        return $items;
    }

    private static function countAuthors(): int
    {
        return count(self::authorIds());
    }

    private static function lastModifiedAuthors(): string
    {
        return gmdate(DATE_ATOM);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private static function authorUrlsForPage(int $page): array
    {
        $authors = array_slice(self::authorIds(), ($page - 1) * self::URLS_PER_SITEMAP, self::URLS_PER_SITEMAP);
        $items = [];

        foreach ($authors as $authorId) {
            $authorId = (int) $authorId;
            if ($authorId < 1) {
                continue;
            }

            $items[] = [
                'loc' => get_author_posts_url($authorId),
                'lastmod' => gmdate(DATE_ATOM),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, int>
     */
    private static function authorIds(): array
    {
        $users = get_users([
            'fields' => 'ID',
            'has_published_posts' => ['guide', 'ranking', 'review', 'comparison', 'tool', 'news'],
            'number' => -1,
        ]);

        if (! is_array($users)) {
            return [];
        }

        return array_values(array_map('absint', $users));
    }

    private static function countStaticUrls(): int
    {
        return count(self::staticUrls());
    }

    private static function lastModifiedStatic(): string
    {
        return gmdate(DATE_ATOM);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private static function staticUrlsForPage(int $page): array
    {
        return array_slice(self::staticUrls(), ($page - 1) * self::URLS_PER_SITEMAP, self::URLS_PER_SITEMAP);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private static function staticUrls(): array
    {
        $paths = [
            '/',
            '/poradniki',
            '/rankingi',
            '/recenzje',
            '/porownania',
            '/narzedzia',
            '/aktualnosci',
            '/technologia',
            '/wordpress',
            '/seo',
            '/ai',
            '/marketing',
            '/social-media',
            '/ecommerce',
            '/internet',
            '/komputery',
            '/oprogramowanie',
            '/biznes',
            '/finanse',
            '/produktywnosc',
            '/smartfony',
            '/aplikacje',
            '/dom',
            '/remont',
            '/motoryzacja',
            '/zdrowie',
            '/fitness',
            '/edukacja',
            '/search',
            '/najpopularniejsze',
            '/najnowsze',
            '/o-nas',
            '/kontakt',
            '/reklama',
            '/polityka-prywatnosci',
            '/regulamin',
        ];

        $items = [];
        foreach ($paths as $path) {
            $items[] = [
                'loc' => home_url($path),
                'lastmod' => gmdate(DATE_ATOM),
            ];
        }

        return $items;
    }

    private static function sendXmlHeader(): void
    {
        status_header(200);
        nocache_headers();
        header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);
    }

    private static function xml(string $value): string
    {
        if (function_exists('esc_xml')) {
            return esc_xml($value);
        }

        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
