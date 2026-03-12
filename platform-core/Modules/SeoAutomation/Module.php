<?php

namespace Poradnik\Platform\Modules\SeoAutomation;

use Poradnik\Platform\Domain\Seo\ContentEnhancer;
use Poradnik\Platform\Domain\Seo\MetaService;
use Poradnik\Platform\Domain\Seo\SchemaService;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_filter('document_title_parts', [self::class, 'filterDocumentTitle']);
        add_action('wp_head', [self::class, 'renderMetaAndSchema'], 5);

        add_filter('the_content', [self::class, 'enhanceContent'], 25);
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

        $schemas = SchemaService::forCurrentPost();
        foreach ($schemas as $schema) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }
    }

    public static function enhanceContent(string $content): string
    {
        $content = ContentEnhancer::maybeInjectToc($content);
        $content = ContentEnhancer::appendInternalLinks($content);

        return $content;
    }
}
