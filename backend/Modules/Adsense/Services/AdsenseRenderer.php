<?php

namespace Poradnik\Platform\Modules\Adsense\Services;

if (! defined('ABSPATH')) {
    exit;
}

final class AdsenseRenderer
{
    private const OPTION_KEY = 'poradnik_adsense_settings';

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $settings = get_option(self::OPTION_KEY, []);

        if (! is_array($settings)) {
            return [];
        }

        return $settings;
    }

    public static function renderSlot(string $slot): string
    {
        $settings = self::settings();
        if (empty($settings['enabled'])) {
            return '';
        }

        $clientId = isset($settings['client_id']) ? sanitize_text_field((string) $settings['client_id']) : '';
        if ($clientId === '') {
            return '';
        }

        $slot = AdsenseSlots::sanitize($slot);
        $slotOptionKey = 'slot_' . $slot;
        $slotId = isset($settings[$slotOptionKey]) ? sanitize_text_field((string) $settings[$slotOptionKey]) : '';

        if ($slotId === '') {
            return '';
        }

        $format = $slot === AdsenseSlots::INLINE ? 'auto' : 'rectangle';

        return '<div class="poradnik-adsense-slot poradnik-adsense-slot-' . esc_attr($slot) . '">'
            . '<ins class="adsbygoogle" style="display:block" data-ad-client="' . esc_attr($clientId) . '" data-ad-slot="' . esc_attr($slotId) . '" data-ad-format="' . esc_attr($format) . '" data-full-width-responsive="true"></ins>'
            . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>'
            . '</div>';
    }
}
