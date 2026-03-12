<?php

namespace Poradnik\Platform\Domain\Seo;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Generates breadcrumb HTML + BreadcrumbList JSON-LD schema.
 *
 * Breadcrumb structure:
 *  - Home
 *  - (optional) Post type archive
 *  - (optional) Primary taxonomy term
 *  - Current post/page title
 */
final class BreadcrumbService
{
    /**
     * @return array<int, array<string, string>> List of {label, url} pairs.
     */
    public static function crumbs(): array
    {
        $items = [
            ['label' => __('Home', 'poradnik-platform'), 'url' => home_url('/')],
        ];

        if (is_singular()) {
            $post = get_queried_object();
            if (! $post instanceof \WP_Post) {
                return $items;
            }

            // Post type archive link.
            $archiveUrl = get_post_type_archive_link($post->post_type);
            if (is_string($archiveUrl) && $archiveUrl !== '') {
                $pto = get_post_type_object($post->post_type);
                $label = ($pto instanceof \WP_Post_Type && $pto->labels->name !== '')
                    ? (string) $pto->labels->name
                    : ucfirst($post->post_type);

                $items[] = ['label' => $label, 'url' => $archiveUrl];
            }

            // Primary taxonomy term (first term of first registered taxonomy for this type).
            $taxonomies = get_object_taxonomies($post->post_type);
            $platformTaxonomies = ['topic', 'intent', 'stage', 'industry'];
            $activeTax = '';
            foreach ($platformTaxonomies as $tax) {
                if (in_array($tax, $taxonomies, true)) {
                    $activeTax = $tax;
                    break;
                }
            }

            if ($activeTax !== '') {
                $terms = get_the_terms($post, $activeTax);
                if (is_array($terms) && $terms !== []) {
                    $term = reset($terms);
                    if ($term instanceof \WP_Term) {
                        $termUrl = get_term_link($term);
                        if (! is_wp_error($termUrl)) {
                            $items[] = ['label' => $term->name, 'url' => $termUrl];
                        }
                    }
                }
            }

            // Current post.
            $items[] = ['label' => get_the_title($post), 'url' => ''];

            return $items;
        }

        if (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $items[] = ['label' => $term->name, 'url' => ''];
            }

            return $items;
        }

        if (is_post_type_archive()) {
            $pto = get_queried_object();
            if ($pto instanceof \WP_Post_Type) {
                $items[] = ['label' => (string) $pto->labels->name, 'url' => ''];
            }

            return $items;
        }

        return $items;
    }

    public static function renderHtml(): string
    {
        $crumbs = self::crumbs();
        if (count($crumbs) < 2) {
            return '';
        }

        $html = '<nav class="poradnik-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'poradnik-platform') . '">';
        $html .= '<ol class="poradnik-breadcrumbs__list">';

        $last = count($crumbs) - 1;
        foreach ($crumbs as $i => $crumb) {
            $label = esc_html($crumb['label']);
            $html .= '<li class="poradnik-breadcrumbs__item">';
            if ($i === $last || $crumb['url'] === '') {
                $html .= '<span aria-current="page">' . $label . '</span>';
            } else {
                $html .= '<a href="' . esc_url($crumb['url']) . '">' . $label . '</a>';
                $html .= '<span class="poradnik-breadcrumbs__sep" aria-hidden="true"> &rsaquo; </span>';
            }
            $html .= '</li>';
        }

        $html .= '</ol></nav>';

        return $html;
    }

    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $crumbs = self::crumbs();
        if (count($crumbs) < 2) {
            return [];
        }

        $elements = [];
        foreach ($crumbs as $i => $crumb) {
            $position  = $i + 1;
            $element = [
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $crumb['label'],
            ];

            if ($crumb['url'] !== '') {
                $element['item'] = $crumb['url'];
            }

            $elements[] = $element;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}
