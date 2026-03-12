<?php

namespace Poradnik\Platform\Domain\Seo;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles canonical URL output and robots meta tag generation.
 *
 * Outputs:
 *   <link rel="canonical" href="..." />
 *   <meta name="robots" content="..." />
 *
 * Robots rules:
 *  - Archives with ?paged=N  → noindex, follow
 *  - Search results, 404     → noindex, nofollow
 *  - Drafts/private posts    → noindex, nofollow
 *  - Programmatic drafts     → noindex, follow (until published)
 *  - All other singulars     → index, follow
 */
final class CanonicalService
{
    public static function renderHead(): void
    {
        self::renderCanonical();
        self::renderRobots();
    }

    private static function renderCanonical(): void
    {
        $url = self::getCanonicalUrl();
        if ($url === '') {
            return;
        }

        echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    }

    private static function renderRobots(): void
    {
        $directive = self::getRobotsDirective();
        echo '<meta name="robots" content="' . esc_attr($directive) . '" />' . "\n";
    }

    private static function getCanonicalUrl(): string
    {
        if (is_singular()) {
            $post = get_queried_object();
            if (! $post instanceof \WP_Post) {
                return '';
            }

            // Custom canonical override stored in post meta (e.g. by editor).
            $override = get_post_meta($post->ID, '_poradnik_canonical_url', true);
            if (is_string($override) && filter_var($override, FILTER_VALIDATE_URL)) {
                return $override;
            }

            $url = get_permalink($post);
            return is_string($url) ? $url : '';
        }

        if (is_home() || is_front_page()) {
            return home_url('/');
        }

        if (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $url = get_term_link($term);
                return (! is_wp_error($url) && is_string($url)) ? $url : '';
            }
        }

        return '';
    }

    private static function getRobotsDirective(): string
    {
        // 404 pages – never indexed.
        if (is_404()) {
            return 'noindex, nofollow';
        }

        // Search results – never indexed.
        if (is_search()) {
            return 'noindex, nofollow';
        }

        // Paginated archives.
        if (is_paged()) {
            return 'noindex, follow';
        }

        if (is_singular()) {
            $post = get_queried_object();
            if (! $post instanceof \WP_Post) {
                return 'noindex, follow';
            }

            // Non-public post status.
            if (! in_array($post->post_status, ['publish', 'inherit'], true)) {
                return 'noindex, nofollow';
            }

            // Programmatic drafts meta flag.
            $isProgrammatic = (string) get_post_meta($post->ID, '_poradnik_programmatic_template', true);
            if ($isProgrammatic !== '' && $post->post_status !== 'publish') {
                return 'noindex, follow';
            }

            // Custom override.
            $override = sanitize_text_field((string) get_post_meta($post->ID, '_poradnik_robots', true));
            if ($override !== '') {
                return $override;
            }
        }

        return 'index, follow';
    }
}
