<?php

namespace Poradnik\Platform\Modules\AdsMarketplace;

use Poradnik\Platform\Admin\AdsCampaignsPage;
use Poradnik\Platform\Domain\Ads\CampaignRepository;
use Poradnik\Platform\Domain\Ads\SlotRepository;
use Poradnik\Platform\Domain\Ads\Tracker;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            AdsCampaignsPage::init();
        }

        add_action('init', [self::class, 'registerRewriteRules'], 9);
        add_action('init', [self::class, 'seedSlots'], 20);
        add_action('template_redirect', [self::class, 'handleRedirect']);
        add_filter('query_vars', [self::class, 'registerQueryVars']);

        add_shortcode('poradnik_ad_slot', [self::class, 'renderAdSlot']);
    }

    public static function seedSlots(): void
    {
        SlotRepository::ensureDefaults();
    }

    public static function registerRewriteRules(): void
    {
        add_rewrite_tag('%poradnik_ad_campaign%', '([0-9]+)');
        add_rewrite_rule('^ad/go/([0-9]+)/?$', 'index.php?poradnik_ad_campaign=$matches[1]', 'top');
    }

    /**
     * @param array<int, string> $queryVars
     * @return array<int, string>
     */
    public static function registerQueryVars(array $queryVars): array
    {
        $queryVars[] = 'poradnik_ad_campaign';

        return $queryVars;
    }

    public static function handleRedirect(): void
    {
        $campaignId = absint(get_query_var('poradnik_ad_campaign'));
        if ($campaignId < 1) {
            return;
        }

        $campaign = CampaignRepository::findById($campaignId);
        if (! is_array($campaign)) {
            self::notFound();
            return;
        }

        $slotId = isset($campaign['slot_id']) ? absint($campaign['slot_id']) : 0;
        $userIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $tracked = Tracker::trackClick($campaignId, $slotId, 'redirect', $userIp);

        if ($tracked instanceof WP_Error) {
            self::notFound();
            return;
        }

        $destination = isset($campaign['destination_url']) ? esc_url_raw((string) $campaign['destination_url']) : '';
        if ($destination === '') {
            self::notFound();
            return;
        }

        wp_redirect($destination, 302, 'PoradnikPlatformAds');
        exit;
    }

    /**
     * @param array<string, string> $atts
     */
    public static function renderAdSlot(array $atts = []): string
    {
        $atts = shortcode_atts([
            'slot' => 'sidebar-banner',
            'source' => 'shortcode',
        ], $atts, 'poradnik_ad_slot');

        $slotKey = sanitize_key((string) $atts['slot']);
        $source = sanitize_text_field((string) $atts['source']);

        $slot = SlotRepository::findByKey($slotKey);
        if (! is_array($slot) || ! isset($slot['id'])) {
            return '';
        }

        $campaign = CampaignRepository::findActiveBySlotKey($slotKey);
        if (! is_array($campaign) || ! isset($campaign['id'])) {
            return '';
        }

        $campaignId = absint($campaign['id']);
        $slotId = absint($slot['id']);
        $userIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        Tracker::trackImpression($campaignId, $slotId, $source, $userIp);

        $label = isset($campaign['creative_text']) && (string) $campaign['creative_text'] !== ''
            ? (string) $campaign['creative_text']
            : (string) ($campaign['name'] ?? 'Advertisement');

        $url = home_url('/ad/go/' . $campaignId . '/');

        return '<div class="poradnik-ad-slot poradnik-ad-slot-' . esc_attr($slotKey) . '"><a class="poradnik-ad-link" href="' . esc_url($url) . '" rel="nofollow sponsored noopener" target="_blank">' . esc_html($label) . '</a></div>';
    }

    private static function notFound(): void
    {
        global $wp_query;

        $wp_query->set_404();
        status_header(404);
    }
}
