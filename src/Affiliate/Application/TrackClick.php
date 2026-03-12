<?php

namespace PearTree\ProgrammaticAffiliate\Affiliate\Application;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;

class TrackClick
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_filter('query_vars', [$this, 'registerQueryVars']);
        add_action('template_redirect', [$this, 'handleRedirect']);
    }

    public function registerQueryVars(array $vars): array
    {
        $vars[] = 'ppae_go_slug';
        return $vars;
    }

    public function handleRedirect(): void
    {
        $slug = sanitize_title((string) get_query_var('ppae_go_slug', ''));
        if ($slug === '') {
            return;
        }

        $product = $this->repository->getProductBySlug($slug);
        if (!is_array($product)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            return;
        }

        $url = esc_url_raw((string) ($product['destination_url'] ?? ''));
        if ($url === '') {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? (string) wp_unslash($_SERVER['HTTP_REFERER']) : '';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';

        $this->repository->trackClick((int) ($product['id'] ?? 0), $ip, $referrer, $userAgent);

        wp_safe_redirect($url, 302);
        exit;
    }
}
