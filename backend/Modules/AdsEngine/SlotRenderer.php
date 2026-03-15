<?php

namespace Poradnik\Platform\Modules\AdsEngine;

use Poradnik\Platform\Modules\AdsEngine\Repository\Campaigns;

if (! defined('ABSPATH')) {
    exit;
}

final class SlotRenderer
{
    public static function render(string $slotName): string
    {
        $slotName = sanitize_text_field($slotName);
        if ($slotName === '') {
            return '';
        }

        if (! self::isVisibleForCurrentTemplate($slotName)) {
            return '';
        }

        $ad = Campaigns::findActiveAdBySlot($slotName);
        if (! is_array($ad)) {
            return '';
        }

        $adId = absint($ad['id'] ?? 0);
        if ($adId < 1) {
            return '';
        }

        Campaigns::incrementImpressions($adId);

        $clickUrl = home_url('/ad-click/' . $adId . '/');
        $creativeUrl = esc_url((string) ($ad['creative_url'] ?? ''));
        $label = __('Reklama', 'poradnik-theme');

        if ($creativeUrl !== '') {
            $creative = '<img src="' . $creativeUrl . '" alt="' . esc_attr($label) . '" loading="lazy" />';
        } else {
            $creative = '<span class="poradnik-ad-fallback">' . esc_html($label) . '</span>';
        }

        return '<div class="poradnik-ad-slot poradnik-ad-slot-' . esc_attr(strtolower($slotName)) . '"><a class="poradnik-ad-link" href="' . esc_url($clickUrl) . '" rel="nofollow sponsored noopener" target="_blank">' . $creative . '</a></div>';
    }

    private static function isVisibleForCurrentTemplate(string $slotName): bool
    {
        $slot = strtoupper($slotName);

        if ($slot === 'AD_SLOT_HERO') {
            return is_front_page() || is_home() || is_archive() || is_category();
        }

        if ($slot === 'AD_SLOT_SIDEBAR') {
            return is_singular(['guide', 'ranking', 'review']) || is_archive() || is_category();
        }

        if (in_array($slot, ['AD_SLOT_ARTICLE_TOP', 'AD_SLOT_ARTICLE_MIDDLE', 'AD_SLOT_ARTICLE_BOTTOM'], true)) {
            return is_singular(['guide', 'ranking', 'review']);
        }

        if ($slot === 'AD_SLOT_FOOTER') {
            return true;
        }

        return true;
    }
}
