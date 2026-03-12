<?php

namespace PearTree\ProgrammaticAffiliate\Rest;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;
use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;
use WP_REST_Request;
use WP_REST_Response;

class StatsController
{
    private AffiliateRepository $affiliateRepository;
    private SeoPageRepository $seoRepository;

    public function __construct(AffiliateRepository $affiliateRepository, SeoPageRepository $seoRepository)
    {
        $this->affiliateRepository = $affiliateRepository;
        $this->seoRepository = $seoRepository;
    }

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('ppae/v1', '/stats', [
                'methods' => 'GET',
                'callback' => [$this, 'getStats'],
                'permission_callback' => [$this, 'canManage'],
            ]);
        });
    }

    public function getStats(WP_REST_Request $request): WP_REST_Response
    {
        $payload = [
            'overview' => $this->affiliateRepository->getOverviewMetrics(),
            'statistics' => $this->affiliateRepository->getStatistics(),
            'seo_pages_count' => $this->seoRepository->countAll(),
            'generated_at' => current_time('mysql'),
        ];

        return new WP_REST_Response($payload, 200);
    }

    public function canManage(): bool
    {
        return current_user_can('manage_options');
    }
}
