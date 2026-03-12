<?php

namespace PPAM\Frontend;

use PPAM\Analytics\Stats;
use PPAM\Core\CampaignManager;

if (!defined('ABSPATH')) {
    exit;
}

class AdSlots
{
    public static function init(): void
    {
        add_shortcode('ppam_ad_slot', [self::class, 'renderShortcode']);
        add_action('wp_head', [self::class, 'renderHeaderSlot']);
        add_action('wp_footer', [self::class, 'renderFooterSlot']);
        add_filter('the_content', [self::class, 'injectArticleBanner'], 20);
    }

    public static function renderShortcode(array $atts): string
    {
        $atts = shortcode_atts(['slot' => 'sidebar_banner'], $atts, 'ppam_ad_slot');
        $slot = sanitize_key((string) $atts['slot']);

        return self::renderSlot($slot);
    }

    public static function renderHeaderSlot(): void
    {
        echo self::renderSlot('header_banner');
    }

    public static function renderFooterSlot(): void
    {
        echo self::renderSlot('footer_banner');
    }

    public static function injectArticleBanner(string $content): string
    {
        if (!is_singular('post') && !is_singular('poradnik')) {
            return $content;
        }

        $banner = self::renderSlot('article_banner');
        if ($banner === '') {
            return $content;
        }

        $parts = explode('</p>', $content);
        if (count($parts) > 2) {
            $parts[1] .= '</p>' . $banner;
            return implode('</p>', $parts);
        }

        return $content . $banner;
    }

    private static function renderSlot(string $slot): string
    {
        $campaign = CampaignManager::getActiveCampaignForSlot($slot);
        if (!$campaign) {
            return '';
        }

        $campaignId = (int) $campaign->ID;
        $targetUrl = (string) get_post_meta($campaignId, '_ppam_target_url', true);
        $bannerUrl = (string) get_post_meta($campaignId, '_ppam_banner_url', true);
        $title = (string) $campaign->post_title;

        if ($targetUrl === '') {
            return '';
        }

        Stats::addImpression($campaignId);
        $trackedUrl = Stats::buildTrackedUrl($campaignId, $targetUrl);

        $creative = $bannerUrl !== ''
            ? '<img src="' . esc_url($bannerUrl) . '" alt="' . esc_attr($title) . '">'
            : '<span class="ppam-text-ad">' . esc_html($title) . '</span>';

        return '<div class="ppam-slot ppam-slot-' . esc_attr($slot) . '"><a href="' . esc_url($trackedUrl) . '" rel="sponsored nofollow noopener" target="_blank">' . $creative . '</a></div>';
    }
}
