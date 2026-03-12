<?php

namespace Poradnik\AfilacjaAdsense\Frontend;

use Poradnik\AfilacjaAdsense\Adsense\AdsenseRenderer;
use Poradnik\AfilacjaAdsense\Affiliate\Infrastructure\AffiliateRepository;

class Shortcodes
{
    private AffiliateRepository $repository;
    private AdsenseRenderer $adsenseRenderer;
    private AffiliateBox $affiliateBox;

    public function __construct(AffiliateRepository $repository, AdsenseRenderer $adsenseRenderer, AffiliateBox $affiliateBox)
    {
        $this->repository = $repository;
        $this->adsenseRenderer = $adsenseRenderer;
        $this->affiliateBox = $affiliateBox;
    }

    public function register(): void
    {
        add_shortcode('peartree_adsense', [$this, 'renderAdsense']);
        add_shortcode('peartree_affiliate', [$this, 'renderAffiliateButton']);
        add_shortcode('peartree_affiliate_box', [$this, 'renderAffiliateBox']);

        add_shortcode('paa_adsense', [$this, 'renderAdsense']);
        add_shortcode('paa_affiliate', [$this, 'renderAffiliateButton']);
        add_shortcode('paa_affiliate_box', [$this, 'renderAffiliateBox']);
    }

    public function renderAdsense(array $atts): string
    {
        $atts = shortcode_atts([
            'placement' => 'article_top',
        ], $atts);

        wp_enqueue_script('paa-affiliate-js', PAA_URL . 'assets/js/affiliate.js', [], PAA_VERSION, true);
        return $this->adsenseRenderer->render(sanitize_text_field((string) $atts['placement']));
    }

    public function renderAffiliateButton(array $atts): string
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $id = (int) $atts['id'];
        if ($id <= 0) {
            return '';
        }

        $entity = $this->repository->findById($id);
        if ($entity === null) {
            return '';
        }

        $data = $entity->toArray();
        $title = esc_html((string) ($data['title'] ?? ''));
        $label = esc_html((string) ($data['button_text'] ?? 'Sprawdź ofertę'));
        $slug = sanitize_title((string) ($data['slug'] ?? ''));
        $url = esc_url(home_url('/go/' . $slug));

        wp_enqueue_style('paa-affiliate-css', PAA_URL . 'assets/css/affiliate.css', [], PAA_VERSION);
        wp_enqueue_script('paa-affiliate-js', PAA_URL . 'assets/js/affiliate.js', [], PAA_VERSION, true);

        return '<a class="paa-affiliate-btn" href="' . $url . '" target="_blank" rel="sponsored nofollow noopener" data-affiliate-id="' . (int) ($data['id'] ?? 0) . '">' . $label . (!empty($title) ? ' - ' . $title : '') . '</a>';
    }

    public function renderAffiliateBox(array $atts): string
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $id = (int) $atts['id'];
        if ($id <= 0) {
            return '';
        }

        $entity = $this->repository->findById($id);
        if ($entity === null) {
            return '';
        }

        wp_enqueue_style('paa-affiliate-css', PAA_URL . 'assets/css/affiliate.css', [], PAA_VERSION);
        wp_enqueue_script('paa-affiliate-js', PAA_URL . 'assets/js/affiliate.js', [], PAA_VERSION, true);

        return $this->affiliateBox->render($entity->toArray());
    }
}
