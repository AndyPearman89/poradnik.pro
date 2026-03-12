<?php

namespace PearTree\ProgrammaticAffiliate\Rest;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;
use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;
use WP_REST_Request;
use WP_REST_Response;

class CatalogController
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
            register_rest_route('ppae/v1', '/products', [
                'methods' => 'GET',
                'callback' => [$this, 'getProducts'],
                'permission_callback' => [$this, 'canManage'],
            ]);

            register_rest_route('ppae/v1', '/seo-pages', [
                'methods' => 'GET',
                'callback' => [$this, 'getSeoPages'],
                'permission_callback' => [$this, 'canManage'],
            ]);
        });
    }

    public function getProducts(WP_REST_Request $request): WP_REST_Response
    {
        $page = max(1, (int) $request->get_param('page'));
        $perPage = max(1, min(200, (int) $request->get_param('per_page')));
        if ($perPage <= 0) {
            $perPage = 20;
        }

        $result = $this->affiliateRepository->getProductsPaginated($page, $perPage);
        return new WP_REST_Response($result, 200);
    }

    public function getSeoPages(WP_REST_Request $request): WP_REST_Response
    {
        $page = max(1, (int) $request->get_param('page'));
        $perPage = max(1, min(200, (int) $request->get_param('per_page')));
        if ($perPage <= 0) {
            $perPage = 20;
        }

        $result = $this->seoRepository->allPaginated($page, $perPage);
        return new WP_REST_Response($result, 200);
    }

    public function canManage(): bool
    {
        return current_user_can('manage_options');
    }
}
