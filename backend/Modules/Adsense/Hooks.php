<?php

namespace Poradnik\Platform\Modules\Adsense;

use Poradnik\Platform\Modules\Adsense\Services\AdsenseRenderer;
use Poradnik\Platform\Modules\Adsense\Services\AdsenseSlots;

if (! defined('ABSPATH')) {
    exit;
}

final class Hooks
{
    public static function register(): void
    {
        add_shortcode('poradnik_adsense', [self::class, 'renderShortcode']);
        add_filter('the_content', [self::class, 'injectInlineSlot'], 30);
    }

    /**
     * @param array<string, string> $atts
     */
    public static function renderShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'slot' => AdsenseSlots::INLINE,
        ], $atts, 'poradnik_adsense');

        $slot = AdsenseSlots::sanitize((string) $atts['slot']);

        return AdsenseRenderer::renderSlot($slot);
    }

    public static function injectInlineSlot(string $content): string
    {
        if (! is_singular()) {
            return $content;
        }

        $settings = AdsenseRenderer::settings();
        if (empty($settings['enabled']) || empty($settings['auto_insert_inline'])) {
            return $content;
        }

        $paragraph = isset($settings['insert_after_paragraph'])
            ? max(1, absint((string) $settings['insert_after_paragraph']))
            : 3;

        $adMarkup = AdsenseRenderer::renderSlot(AdsenseSlots::INLINE);
        if ($adMarkup === '') {
            return $content;
        }

        $parts = explode('</p>', $content);
        if (count($parts) <= $paragraph) {
            return $content . $adMarkup;
        }

        $output = '';
        foreach ($parts as $index => $part) {
            if ($part === '' && $index === count($parts) - 1) {
                continue;
            }

            $output .= $part;
            if ($index < count($parts) - 1) {
                $output .= '</p>';
            }

            if ($index + 1 === $paragraph) {
                $output .= $adMarkup;
            }
        }

        return $output;
    }
}
