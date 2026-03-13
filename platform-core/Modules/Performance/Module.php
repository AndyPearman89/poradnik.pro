<?php

namespace Poradnik\Platform\Modules\Performance;

use Poradnik\Platform\Domain\Performance\CacheHelper;
use Poradnik\Platform\Domain\Performance\LazyLoader;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_filter('the_content', [LazyLoader::class, 'applyLazyLoadToContent'], 99);
        add_filter('post_thumbnail_html', [LazyLoader::class, 'addLazyLoadToPostThumbnail'], 99);

        add_action('save_post', [CacheHelper::class, 'purgeOnPostSave'], 10, 1);
        add_action('send_headers', [CacheHelper::class, 'addCdnReadinessHeaders']);
    }
}
