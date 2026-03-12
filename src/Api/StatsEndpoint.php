<?php

namespace Poradnik\AfilacjaAdsense\Api;

use Poradnik\AfilacjaAdsense\Affiliate\Infrastructure\AffiliateRepository;
use WP_REST_Request;
use WP_REST_Response;

class StatsEndpoint
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('peartree/v1', '/affiliate/stats', [
                'methods' => 'GET',
                'callback' => [$this, 'handleGetStats'],
                'permission_callback' => static function (): bool {
                    return current_user_can('manage_options');
                },
            ]);

            register_rest_route('peartree/v1', '/affiliate/health', [
                'methods' => 'GET',
                'callback' => [$this, 'handleGetHealth'],
                'permission_callback' => static function (): bool {
                    return current_user_can('manage_options');
                },
            ]);
        });
    }

    public function handleGetStats(WP_REST_Request $request): WP_REST_Response
    {
        unset($request);
        return new WP_REST_Response($this->repository->getStats(), 200);
    }

    public function handleGetHealth(WP_REST_Request $request): WP_REST_Response
    {
        unset($request);

        $stats = $this->repository->getStats();

        return new WP_REST_Response([
            'ok' => true,
            'time' => current_time('mysql'),
            'links_total' => (int) ($stats['total_links'] ?? 0),
            'clicks_total' => (int) ($stats['total_clicks'] ?? 0),
            'top_links_count' => is_array($stats['top_links'] ?? null) ? count((array) $stats['top_links']) : 0,
        ], 200);
    }
}
