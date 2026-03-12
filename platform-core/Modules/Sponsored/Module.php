<?php

namespace Poradnik\Platform\Modules\Sponsored;

use Poradnik\Platform\Admin\SponsoredOrdersPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            SponsoredOrdersPage::init();
        }

        add_filter('the_content', [self::class, 'decorateSponsoredContent'], 20);
    }

    public static function decorateSponsoredContent(string $content): string
    {
        if (! is_singular('sponsored')) {
            return $content;
        }

        $badge = '<p class="poradnik-sponsored-badge"><strong>Artykul sponsorowany</strong></p>';
        $content = $badge . $content;

        return preg_replace('/<a\s+([^>]*href=["\'][^"\']+["\'][^>]*)>/i', '<a $1 rel="nofollow sponsored noopener">', $content) ?? $content;
    }
}
