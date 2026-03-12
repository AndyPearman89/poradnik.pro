<?php

namespace PearTree\ProgrammaticAffiliate\Frontend;

use PearTree\ProgrammaticAffiliate\Adsense\AdsenseRenderer;
use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;
use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;

class SeoPageRenderer
{
    private SeoPageRepository $seoRepository;
    private AffiliateRepository $affiliateRepository;
    private AdsenseRenderer $adsenseRenderer;

    public function __construct(SeoPageRepository $seoRepository, AffiliateRepository $affiliateRepository, AdsenseRenderer $adsenseRenderer)
    {
        $this->seoRepository = $seoRepository;
        $this->affiliateRepository = $affiliateRepository;
        $this->adsenseRenderer = $adsenseRenderer;
    }

    public function renderBySlug(string $slug): string
    {
        $seoPage = $this->seoRepository->findBySlug($slug);
        if (!is_array($seoPage)) {
            return '';
        }

        $products = array_slice($this->affiliateRepository->getProducts(), 0, 5);
        $adsTop = $this->adsenseRenderer->render('article_top');
        $adsBottom = $this->adsenseRenderer->render('article_bottom');

        add_action('wp_head', static function () use ($seoPage): void {
            $metaTitle = esc_html((string) ($seoPage['title'] ?? ''));
            $metaDescription = esc_attr('Porównanie i ranking dla: ' . (string) ($seoPage['keyword'] ?? ''));
            echo '<meta name="description" content="' . $metaDescription . '">';
            echo '<meta property="og:title" content="' . $metaTitle . '">';
        }, 20);

        ob_start();
        include PPAE_PATH . 'templates/seo-page.php';
        return (string) ob_get_clean();
    }
}
