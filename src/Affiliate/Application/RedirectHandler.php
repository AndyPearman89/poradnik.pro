<?php

namespace Poradnik\AfilacjaAdsense\Affiliate\Application;

use Poradnik\AfilacjaAdsense\Affiliate\Infrastructure\AffiliateRepository;

class RedirectHandler
{
    private AffiliateRepository $repository;
    private TrackClick $trackClick;

    public function __construct(AffiliateRepository $repository, TrackClick $trackClick)
    {
        $this->repository = $repository;
        $this->trackClick = $trackClick;
    }

    public function register(): void
    {
        add_filter('query_vars', [$this, 'registerQueryVars']);
        add_action('template_redirect', [$this, 'handle']);
    }

    public function registerQueryVars(array $vars): array
    {
        $vars[] = 'paa_go_slug';
        return $vars;
    }

    public function handle(): void
    {
        $slug = (string) get_query_var('paa_go_slug', '');
        $slug = sanitize_title($slug);

        if ($slug === '') {
            return;
        }

        $link = $this->repository->findBySlug($slug);
        if ($link === null) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            return;
        }

        $data = $link->toArray();
        $url = esc_url_raw((string) ($data['destination_url'] ?? ''));
        if ($url === '') {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        $this->trackClick->execute((int) ($data['id'] ?? 0));
        wp_safe_redirect($url, 302);
        exit;
    }
}
