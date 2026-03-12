<?php

namespace PearTree\ProgrammaticAffiliate\Frontend;

use PearTree\ProgrammaticAffiliate\Adsense\AdsenseRenderer;
use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;

class Shortcodes
{
    private AffiliateRepository $repository;
    private AdsenseRenderer $adsenseRenderer;
    private AffiliateBox $affiliateBox;
    private ComparisonTable $comparisonTable;
    private RankingList $rankingList;
    private SeoPageRenderer $seoPageRenderer;

    public function __construct(
        AffiliateRepository $repository,
        AdsenseRenderer $adsenseRenderer,
        AffiliateBox $affiliateBox,
        ComparisonTable $comparisonTable,
        RankingList $rankingList,
        SeoPageRenderer $seoPageRenderer
    ) {
        $this->repository = $repository;
        $this->adsenseRenderer = $adsenseRenderer;
        $this->affiliateBox = $affiliateBox;
        $this->comparisonTable = $comparisonTable;
        $this->rankingList = $rankingList;
        $this->seoPageRenderer = $seoPageRenderer;
    }

    public function register(): void
    {
        add_shortcode('peartree_adsense', [$this, 'adsense']);
        add_shortcode('peartree_affiliate_box', [$this, 'affiliateBox']);
        add_shortcode('peartree_comparison', [$this, 'comparison']);
        add_shortcode('peartree_ranking', [$this, 'ranking']);
        add_shortcode('peartree_seo_page', [$this, 'seoPage']);
    }

    public function adsense(array $atts): string
    {
        $atts = shortcode_atts(['placement' => 'article_top'], $atts);
        wp_enqueue_script('ppae-affiliate-js', PPAE_URL . 'assets/js/affiliate.js', [], PPAE_VERSION, true);
        return $this->adsenseRenderer->render(sanitize_text_field((string) $atts['placement']));
    }

    public function affiliateBox(array $atts): string
    {
        $atts = shortcode_atts(['id' => 0], $atts);
        $id = (int) $atts['id'];
        if ($id <= 0) {
            return '';
        }

        $product = $this->repository->getProductById($id);
        if (!is_array($product)) {
            return '';
        }

        wp_enqueue_style('ppae-affiliate-css', PPAE_URL . 'assets/css/affiliate.css', [], PPAE_VERSION);
        wp_enqueue_script('ppae-affiliate-js', PPAE_URL . 'assets/js/affiliate.js', [], PPAE_VERSION, true);
        return $this->affiliateBox->render($product);
    }

    public function comparison(array $atts): string
    {
        $atts = shortcode_atts(['ids' => ''], $atts);
        $ids = array_filter(array_map('intval', explode(',', (string) $atts['ids'])));
        $products = $this->repository->getProductsByIds($ids);
        if (empty($products)) {
            return '';
        }

        wp_enqueue_style('ppae-comparison-css', PPAE_URL . 'assets/css/comparison.css', [], PPAE_VERSION);
        return $this->comparisonTable->render($products);
    }

    public function ranking(array $atts): string
    {
        $atts = shortcode_atts(['ids' => ''], $atts);
        $ids = array_filter(array_map('intval', explode(',', (string) $atts['ids'])));
        $products = $this->repository->getProductsByIds($ids);
        if (empty($products)) {
            return '';
        }

        usort($products, static fn(array $a, array $b): int => (float) ($b['rating'] ?? 0) <=> (float) ($a['rating'] ?? 0));
        wp_enqueue_style('ppae-ranking-css', PPAE_URL . 'assets/css/ranking.css', [], PPAE_VERSION);
        return $this->rankingList->render($products);
    }

    public function seoPage(array $atts): string
    {
        $atts = shortcode_atts(['slug' => ''], $atts);
        $slug = sanitize_title((string) $atts['slug']);
        if ($slug === '') {
            return '';
        }

        wp_enqueue_style('ppae-affiliate-css', PPAE_URL . 'assets/css/affiliate.css', [], PPAE_VERSION);
        wp_enqueue_style('ppae-comparison-css', PPAE_URL . 'assets/css/comparison.css', [], PPAE_VERSION);
        wp_enqueue_style('ppae-ranking-css', PPAE_URL . 'assets/css/ranking.css', [], PPAE_VERSION);
        return $this->seoPageRenderer->renderBySlug($slug);
    }
}
