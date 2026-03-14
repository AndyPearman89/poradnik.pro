<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Ads\CampaignRepository;
use Poradnik\Platform\Domain\Ads\SlotRepository;
use Poradnik\Platform\Domain\Dashboard\StatsService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class DashboardController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/dashboard/overview', [
            'methods' => 'GET',
            'callback' => [self::class, 'overview'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/dashboard/campaigns', [
            'methods' => 'GET',
            'callback' => [self::class, 'campaigns'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/dashboard/statistics', [
            'methods' => 'GET',
            'callback' => [self::class, 'statistics'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/dashboard/payments', [
            'methods' => 'GET',
            'callback' => [self::class, 'payments'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/api/ads', [
            'methods' => 'GET',
            'callback' => [self::class, 'adsApi'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/api/campaigns', [
            'methods' => 'GET',
            'callback' => [self::class, 'campaignsApi'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/api/campaigns', [
            'methods' => 'POST',
            'callback' => [self::class, 'campaignsSaveApi'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/api/campaigns/(?P<campaign_id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [self::class, 'campaignsDeleteApi'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/api/campaigns/(?P<campaign_id>\d+)/pause', [
            'methods' => 'POST',
            'callback' => [self::class, 'campaignsPauseApi'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/api/stats', [
            'methods' => 'GET',
            'callback' => [self::class, 'statsApi'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);

        register_rest_route('poradnik/v1', '/api/payments', [
            'methods' => 'GET',
            'callback' => [self::class, 'paymentsApi'],
            'permission_callback' => [self::class, 'canAccess'],
        ]);
    }

    public static function canAccess(): bool
    {
        if (Capabilities::canManagePlatform()) {
            return true;
        }

        if (! is_user_logged_in()) {
            return false;
        }

        return current_user_can('reklamodawca') || current_user_can('advertiser') || current_user_can('edit_posts') || current_user_can('publish_posts');
    }

    public static function overview(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);

        return new WP_REST_Response(StatsService::overview($advertiserId), 200);
    }

    public static function campaigns(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);

        return new WP_REST_Response(['items' => StatsService::campaigns($advertiserId)], 200);
    }

    public static function statistics(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);

        return new WP_REST_Response(StatsService::statistics($advertiserId), 200);
    }

    public static function payments(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);

        return new WP_REST_Response(StatsService::payments($advertiserId), 200);
    }

    public static function adsApi(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);

        return new WP_REST_Response([
            'overview' => StatsService::overview($advertiserId),
            'statistics' => StatsService::statistics($advertiserId),
        ], 200);
    }

    public static function campaignsApi(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);

        return new WP_REST_Response([
            'items' => StatsService::campaigns($advertiserId),
        ], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function campaignsSaveApi(WP_REST_Request $request)
    {
        $campaignId = absint($request->get_param('id'));
        $advertiserId = self::resolveAdvertiserId($request);
        $location = sanitize_key((string) $request->get_param('location'));

        if ($advertiserId < 1) {
            return new WP_Error('poradnik_campaign_forbidden', 'Advertiser account is required.', ['status' => 403]);
        }

        if ($campaignId > 0) {
            $campaignAccess = self::ensureCampaignAccess($campaignId);
            if ($campaignAccess instanceof WP_Error) {
                return $campaignAccess;
            }
        }

        $slotMap = [
            'homepage' => 'homepage-hero',
            'artykul' => 'inline-article',
            'article' => 'inline-article',
            'sidebar' => 'sidebar-banner',
        ];

        $slotKey = $slotMap[$location] ?? 'sidebar-banner';
        $slot = SlotRepository::findByKey($slotKey);
        if (! is_array($slot) || ! isset($slot['id'])) {
            return new WP_Error('poradnik_campaign_invalid_slot', 'Invalid ad slot.', ['status' => 400]);
        }

        $name = sanitize_text_field((string) $request->get_param('name'));
        if ($name === '') {
            $name = sanitize_text_field((string) $request->get_param('title'));
        }
        if ($name === '') {
            $name = 'Kampania reklamowa';
        }

        $payload = [
            'name' => $name,
            'advertiser_id' => $advertiserId,
            'slot_id' => absint($slot['id']),
            'status' => sanitize_key((string) ($request->get_param('status') ?: 'draft')),
            'start_date' => sanitize_text_field((string) $request->get_param('start')),
            'end_date' => sanitize_text_field((string) $request->get_param('end')),
            'budget' => (float) $request->get_param('budget'),
            'destination_url' => esc_url_raw((string) $request->get_param('destination_url')),
            'creative_text' => sanitize_text_field((string) $request->get_param('type')),
        ];

        $saved = CampaignRepository::save($payload, $campaignId);
        if ($saved < 1) {
            return new WP_Error('poradnik_campaign_save_failed', 'Campaign could not be saved.', ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true, 'campaign_id' => $saved], 200);
    }

    public static function campaignsDeleteApi(WP_REST_Request $request): WP_REST_Response
    {
        $campaignId = absint($request->get_param('campaign_id'));
        $campaignAccess = self::ensureCampaignAccess($campaignId);
        if ($campaignAccess instanceof WP_Error) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $campaignAccess->get_error_message(),
            ], (int) ($campaignAccess->get_error_data()['status'] ?? 403));
        }

        $deleted = CampaignRepository::delete($campaignId);

        return new WP_REST_Response(['success' => $deleted], $deleted ? 200 : 404);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function campaignsPauseApi(WP_REST_Request $request)
    {
        $campaignId = absint($request->get_param('campaign_id'));
        $campaignAccess = self::ensureCampaignAccess($campaignId);
        if ($campaignAccess instanceof WP_Error) {
            return $campaignAccess;
        }

        $campaign = CampaignRepository::findById($campaignId);
        if (! is_array($campaign)) {
            return new WP_Error('poradnik_campaign_not_found', 'Campaign not found.', ['status' => 404]);
        }

        $campaign['status'] = 'paused';
        $saved = CampaignRepository::save($campaign, $campaignId);
        if ($saved < 1) {
            return new WP_Error('poradnik_campaign_pause_failed', 'Campaign pause failed.', ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true, 'campaign_id' => $saved, 'status' => 'paused'], 200);
    }

    public static function statsApi(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);
        $period = sanitize_key((string) $request->get_param('period'));
        if ($period === '') {
            $period = 'daily';
        }

        return new WP_REST_Response([
            'metrics' => StatsService::statistics($advertiserId),
            'series' => StatsService::series($advertiserId, $period),
        ], 200);
    }

    public static function paymentsApi(WP_REST_Request $request): WP_REST_Response
    {
        $advertiserId = self::resolveAdvertiserId($request);

        return new WP_REST_Response([
            'summary' => StatsService::payments($advertiserId),
            'items' => StatsService::paymentItems($advertiserId),
        ], 200);
    }

    private static function resolveAdvertiserId(WP_REST_Request $request): int
    {
        if (Capabilities::canManagePlatform()) {
            return absint($request->get_param('advertiser_id'));
        }

        return get_current_user_id();
    }

    /**
     * @return true|WP_Error
     */
    private static function ensureCampaignAccess(int $campaignId)
    {
        if ($campaignId < 1) {
            return new WP_Error('poradnik_campaign_not_found', 'Campaign not found.', ['status' => 404]);
        }

        $campaign = CampaignRepository::findById($campaignId);
        if (! is_array($campaign)) {
            return new WP_Error('poradnik_campaign_not_found', 'Campaign not found.', ['status' => 404]);
        }

        if (Capabilities::canManagePlatform()) {
            return true;
        }

        if ((int) ($campaign['advertiser_id'] ?? 0) !== get_current_user_id()) {
            return new WP_Error('poradnik_campaign_forbidden', 'You cannot manage this campaign.', ['status' => 403]);
        }

        return true;
    }
}
