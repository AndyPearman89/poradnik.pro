<?php

namespace Poradnik\Platform\Modules\Reviews;

use Poradnik\Platform\Domain\Review\ReviewService;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_filter('the_content', [self::class, 'injectReviewBox'], 30);
    }

    public static function injectReviewBox(string $content): string
    {
        if (! is_singular('review')) {
            return $content;
        }

        if (strpos($content, 'poradnik-review-box') !== false) {
            return $content;
        }

        $review = ReviewService::fromPost((int) get_the_ID());
        $box = ReviewService::renderBox($review);

        return $box . $content;
    }
}
