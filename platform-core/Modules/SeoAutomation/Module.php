<?php

namespace Poradnik\Platform\Modules\SeoAutomation;

use Poradnik\Platform\Domain\Seo\BreadcrumbService;
use Poradnik\Platform\Domain\Seo\CanonicalService;
use Poradnik\Platform\Domain\Seo\ContentEnhancer;
use Poradnik\Platform\Domain\Seo\MetaService;
use Poradnik\Platform\Domain\Seo\SchemaService;
use Poradnik\Platform\Domain\Seo\SitemapService;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        SitemapService::init();

        add_filter('document_title_parts', [self::class, 'filterDocumentTitle']);
        add_filter('robots_txt', [self::class, 'appendSitemapToRobots'], 20, 2);
        add_action('wp_head', [self::class, 'renderMetaAndSchema'], 5);
        add_action('wp_head', [CanonicalService::class, 'renderHead'], 6);

        add_filter('the_content', [self::class, 'enhanceContent'], 25);
        add_filter('the_content', [self::class, 'prependBreadcrumbs'], 15);
    }

    /**
     * @param array<string, mixed> $parts
     * @return array<string, mixed>
     */
    public static function filterDocumentTitle(array $parts): array
    {
        return MetaService::documentTitleParts($parts);
    }

    public static function renderMetaAndSchema(): void
    {
        if (! is_singular()) {
            return;
        }

        $description = MetaService::metaDescription();
        if ($description !== '') {
            echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        }

        self::renderSocialImageMeta((int) get_the_ID());

        $schemas = SchemaService::forCurrentPost();
        foreach ($schemas as $schema) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }
    }

    private static function renderSocialImageMeta(int $postId): void
    {
        $ogId = absint((string) get_post_meta($postId, 'og_image', true));
        if ($ogId < 1) {
            $ogId = get_post_thumbnail_id($postId);
        }

        $socialId = absint((string) get_post_meta($postId, 'social_image', true));
        if ($socialId < 1) {
            $socialId = $ogId;
        }

        $ogUrl = $ogId > 0 ? wp_get_attachment_url($ogId) : '';
        $socialUrl = $socialId > 0 ? wp_get_attachment_url($socialId) : '';

        if (is_string($ogUrl) && $ogUrl !== '') {
            echo '<meta property="og:image" content="' . esc_url($ogUrl) . '" />' . "\n";
        }

        if (is_string($socialUrl) && $socialUrl !== '') {
            echo '<meta name="twitter:image" content="' . esc_url($socialUrl) . '" />' . "\n";
        }
    }

    public static function prependBreadcrumbs(string $content): string
    {
        if (! is_singular()) {
            return $content;
        }

        $breadcrumbs = BreadcrumbService::renderHtml();
        if ($breadcrumbs === '') {
            return $content;
        }

        return $breadcrumbs . $content;
    }

    public static function enhanceContent(string $content): string
    {
        $content = ContentEnhancer::maybeInjectToc($content);
        $content = ContentEnhancer::appendInternalLinks($content);

        return $content;
    }

    public static function appendSitemapToRobots(string $output, bool $public): string
    {
        if (! $public) {
            return $output;
        }

        $sitemapUrl = home_url('/sitemap.xml');
        if (! is_string($sitemapUrl) || $sitemapUrl === '') {
            return $output;
        }

        if (stripos($output, $sitemapUrl) !== false) {
            return $output;
        }

        $line = 'Sitemap: ' . $sitemapUrl;

        if ($output !== '' && ! str_ends_with($output, "\n")) {
            $output .= "\n";
        }

        return $output . $line . "\n";
    }
}
