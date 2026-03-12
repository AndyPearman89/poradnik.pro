<?php

namespace Poradnik\AfilacjaAdsense\Admin;

use Poradnik\AfilacjaAdsense\Affiliate\Infrastructure\AffiliateRepository;

class AdminMenu
{
    private AffiliateLinksPage $linksPage;

    public function __construct(AffiliateLinksPage $linksPage)
    {
        $this->linksPage = $linksPage;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('peartree.pro Monetization', 'peartree-pro-afiliacja-adsense'),
            __('peartree.pro Monetization', 'peartree-pro-afiliacja-adsense'),
            'manage_options',
            'paa-monetization',
            [$this, 'renderOverviewPage'],
            'dashicons-chart-line',
            58
        );

        add_submenu_page(
            'paa-monetization',
            __('Ustawienia AdSense', 'peartree-pro-afiliacja-adsense'),
            __('Ustawienia AdSense', 'peartree-pro-afiliacja-adsense'),
            'manage_options',
            'paa-settings',
            [SettingsPage::class, 'renderPage']
        );

        add_submenu_page(
            'paa-monetization',
            __('Linki afiliacyjne', 'peartree-pro-afiliacja-adsense'),
            __('Linki afiliacyjne', 'peartree-pro-afiliacja-adsense'),
            'manage_options',
            'paa-affiliate-links',
            [$this->linksPage, 'renderPage']
        );

        add_submenu_page(
            'paa-monetization',
            __('Statystyki kliknięć', 'peartree-pro-afiliacja-adsense'),
            __('Statystyki kliknięć', 'peartree-pro-afiliacja-adsense'),
            'manage_options',
            'paa-click-statistics',
            [$this->linksPage, 'renderStatsPage']
        );
    }

    public function renderOverviewPage(): void
    {
        $repository = new AffiliateRepository();
        $stats = $repository->getStats();
        $statsEndpoint = rest_url('peartree/v1/affiliate/stats');
        $healthEndpoint = rest_url('peartree/v1/affiliate/health');

        echo '<div class="wrap"><h1>' . esc_html__('peartree.pro Monetization', 'peartree-pro-afiliacja-adsense') . '</h1>';
        echo '<p>' . esc_html__('Lekki silnik monetyzacji dla portali SEO: AdSense i afiliacja.', 'peartree-pro-afiliacja-adsense') . '</p>';

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;max-width:900px;margin:12px 0 16px 0">';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('Linki', 'peartree-pro-afiliacja-adsense') . ':</strong><br>' . esc_html((string) ((int) ($stats['total_links'] ?? 0))) . '</div>';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('Kliknięcia', 'peartree-pro-afiliacja-adsense') . ':</strong><br>' . esc_html((string) ((int) ($stats['total_clicks'] ?? 0))) . '</div>';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('Top linki', 'peartree-pro-afiliacja-adsense') . ':</strong><br>' . esc_html((string) (is_array($stats['top_links'] ?? null) ? count((array) $stats['top_links']) : 0)) . '</div>';
        echo '</div>';

        echo '<p><strong>' . esc_html__('REST (admin):', 'peartree-pro-afiliacja-adsense') . '</strong> <code>' . esc_html($statsEndpoint) . '</code> | <code>' . esc_html($healthEndpoint) . '</code></p>';
        echo '</div>';
    }
}

