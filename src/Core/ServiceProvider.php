<?php

namespace Poradnik\AfilacjaAdsense\Core;

use Poradnik\AfilacjaAdsense\Admin\AdminMenu;
use Poradnik\AfilacjaAdsense\Admin\AffiliateLinksPage;
use Poradnik\AfilacjaAdsense\Admin\SettingsPage;
use Poradnik\AfilacjaAdsense\Adsense\AdsenseManager;
use Poradnik\AfilacjaAdsense\Adsense\AdsenseRenderer;
use Poradnik\AfilacjaAdsense\Affiliate\Application\RedirectHandler;
use Poradnik\AfilacjaAdsense\Affiliate\Application\TrackClick;
use Poradnik\AfilacjaAdsense\Affiliate\Infrastructure\AffiliateRepository;
use Poradnik\AfilacjaAdsense\Api\StatsEndpoint;
use Poradnik\AfilacjaAdsense\Frontend\AffiliateBox;
use Poradnik\AfilacjaAdsense\Frontend\Shortcodes;

class ServiceProvider
{
    public function register(): void
    {
        $repository = new AffiliateRepository();
        $trackClick = new TrackClick($repository);

        $adsenseManager = new AdsenseManager();
        $adsenseRenderer = new AdsenseRenderer($adsenseManager);

        (new SettingsPage($adsenseManager))->register();
        $linksPage = new AffiliateLinksPage($repository);
        $linksPage->register();

        (new AdminMenu($linksPage))->register();
        (new Shortcodes($repository, $adsenseRenderer, new AffiliateBox()))->register();
        (new RedirectHandler($repository, $trackClick))->register();
        (new StatsEndpoint($repository))->register();

        add_action('init', [new Kernel(), 'registerRewrite']);
    }
}
