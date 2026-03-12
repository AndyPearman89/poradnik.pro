<?php

namespace PearTree\ProgrammaticAffiliate\Core;

use PearTree\ProgrammaticAffiliate\Admin\AdminMenu;
use PearTree\ProgrammaticAffiliate\Admin\DashboardPage;
use PearTree\ProgrammaticAffiliate\Admin\KeywordsPage;
use PearTree\ProgrammaticAffiliate\Admin\ProductsPage;
use PearTree\ProgrammaticAffiliate\Admin\SeoPagesPage;
use PearTree\ProgrammaticAffiliate\Admin\SettingsPage;
use PearTree\ProgrammaticAffiliate\Admin\StatisticsPage;
use PearTree\ProgrammaticAffiliate\Admin\ToolsPage;
use PearTree\ProgrammaticAffiliate\Adsense\AdsenseManager;
use PearTree\ProgrammaticAffiliate\Adsense\AdsenseRenderer;
use PearTree\ProgrammaticAffiliate\Affiliate\Application\AutoLinkEngine;
use PearTree\ProgrammaticAffiliate\Affiliate\Application\TrackClick;
use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;
use PearTree\ProgrammaticAffiliate\Frontend\AffiliateBox;
use PearTree\ProgrammaticAffiliate\Frontend\ComparisonTable;
use PearTree\ProgrammaticAffiliate\Frontend\RankingList;
use PearTree\ProgrammaticAffiliate\Frontend\SeoPageRenderer;
use PearTree\ProgrammaticAffiliate\Frontend\Shortcodes;
use PearTree\ProgrammaticAffiliate\Rest\CatalogController;
use PearTree\ProgrammaticAffiliate\Rest\StatsController;
use PearTree\ProgrammaticAffiliate\SEO\Application\SeoPageGenerator;
use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;

class ServiceProvider
{
    public function register(): void
    {
        (new DataMigrator())->maybeMigrate();

        $affiliateRepository = new AffiliateRepository();
        $seoRepository = new SeoPageRepository();

        $adsenseManager = new AdsenseManager();
        $adsenseRenderer = new AdsenseRenderer($adsenseManager);

        $trackClick = new TrackClick($affiliateRepository);
        $autoLinkEngine = new AutoLinkEngine($affiliateRepository);
        $seoGenerator = new SeoPageGenerator($seoRepository);

        $settingsPage = new SettingsPage($adsenseManager);
        $productsPage = new ProductsPage($affiliateRepository);
        $keywordsPage = new KeywordsPage($affiliateRepository);
        $seoPagesPage = new SeoPagesPage($seoRepository, $seoGenerator);
        $statisticsPage = new StatisticsPage($affiliateRepository);
        $dashboardPage = new DashboardPage($affiliateRepository, $seoRepository);
        $toolsPage = new ToolsPage($affiliateRepository, $seoRepository);

        (new AdminMenu($dashboardPage, $settingsPage, $productsPage, $keywordsPage, $seoPagesPage, $statisticsPage, $toolsPage))->register();
        $dashboardPage->register();
        $settingsPage->register();
        $productsPage->register();
        $keywordsPage->register();
        $seoPagesPage->register();
        $statisticsPage->register();
        $toolsPage->register();

        (new StatsController($affiliateRepository, $seoRepository))->register();
        (new CatalogController($affiliateRepository, $seoRepository))->register();

        (new Shortcodes(
            $affiliateRepository,
            $adsenseRenderer,
            new AffiliateBox(),
            new ComparisonTable(),
            new RankingList(),
            new SeoPageRenderer($seoRepository, $affiliateRepository, $adsenseRenderer)
        ))->register();

        $trackClick->register();
        $autoLinkEngine->register();

        add_action('init', [new Kernel(), 'registerRewrite']);

        add_filter('pre_get_document_title', static function (string $title): string {
            if (!is_singular('page')) {
                return $title;
            }

            $pageId = get_queried_object_id();
            if ($pageId <= 0) {
                return $title;
            }

            $metaTitle = (string) get_post_meta($pageId, '_ppae_meta_title', true);
            return $metaTitle !== '' ? $metaTitle : $title;
        });

        add_action('wp_head', static function (): void {
            if (!is_singular('page')) {
                return;
            }

            $pageId = get_queried_object_id();
            if ($pageId <= 0) {
                return;
            }

            $metaDescription = (string) get_post_meta($pageId, '_ppae_meta_description', true);
            if ($metaDescription !== '') {
                echo '<meta name="description" content="' . esc_attr($metaDescription) . '">';
            }
        }, 5);
    }
}
