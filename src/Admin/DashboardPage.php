<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;
use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;

class DashboardPage
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
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $metrics = $this->affiliateRepository->getOverviewMetrics();
        $recentActivity = $this->affiliateRepository->getRecentActivity(8);
        $seoPagesCount = $this->seoRepository->countAll();

        $moduleStatus = [
            'Programmatic Engine' => true,
            'SEO Engine' => defined('PSE_VERSION') || function_exists('pse_get_settings'),
            'Ads Marketplace' => defined('PPAM_VERSION') || class_exists('PPAM\\Core\\Marketplace'),
            'Monetyzacja (PAA)' => defined('PAA_VERSION') || class_exists('Poradnik\\AfilacjaAdsense\\Core\\Kernel'),
        ];

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Dashboard', 'peartree-pro-programmatic-affiliate'); ?></h1>

            <style>
                .ppae-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:12px 0}
                .ppae-card{background:#fff;border:1px solid #d7deea;border-radius:10px;padding:14px}
                .ppae-kpi{font-size:26px;font-weight:700;color:#0b5bd3}
                .ppae-flex{display:flex;flex-wrap:wrap;gap:8px}
                .ppae-status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700}
                .ppae-status-ok{background:#e7f7ee;color:#12663b}
                .ppae-status-off{background:#eef2f7;color:#344054}
            </style>

            <div class="ppae-grid">
                <div class="ppae-card"><div class="ppae-kpi"><?php echo esc_html((string) (int) ($metrics['products_count'] ?? 0)); ?></div><div><?php echo esc_html__('Produkty afiliacyjne', 'peartree-pro-programmatic-affiliate'); ?></div></div>
                <div class="ppae-card"><div class="ppae-kpi"><?php echo esc_html((string) (int) ($metrics['keywords_count'] ?? 0)); ?></div><div><?php echo esc_html__('Słowa kluczowe', 'peartree-pro-programmatic-affiliate'); ?></div></div>
                <div class="ppae-card"><div class="ppae-kpi"><?php echo esc_html((string) (int) ($metrics['clicks_30d'] ?? 0)); ?></div><div><?php echo esc_html__('Kliknięcia (30 dni)', 'peartree-pro-programmatic-affiliate'); ?></div></div>
                <div class="ppae-card"><div class="ppae-kpi"><?php echo esc_html((string) (int) $seoPagesCount); ?></div><div><?php echo esc_html__('Strony SEO', 'peartree-pro-programmatic-affiliate'); ?></div></div>
            </div>

            <div class="ppae-card" style="margin-bottom:12px">
                <h2><?php echo esc_html__('Szybkie akcje', 'peartree-pro-programmatic-affiliate'); ?></h2>
                <div class="ppae-flex">
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ppae-products')); ?>"><?php echo esc_html__('Produkty afiliacyjne', 'peartree-pro-programmatic-affiliate'); ?></a>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ppae-keywords')); ?>"><?php echo esc_html__('Słowa kluczowe', 'peartree-pro-programmatic-affiliate'); ?></a>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ppae-seo-pages')); ?>"><?php echo esc_html__('Strony SEO', 'peartree-pro-programmatic-affiliate'); ?></a>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ppae-tools')); ?>"><?php echo esc_html__('Narzędzia', 'peartree-pro-programmatic-affiliate'); ?></a>
                </div>
            </div>

            <div class="ppae-card" style="margin-bottom:12px">
                <h2><?php echo esc_html__('Status modułów', 'peartree-pro-programmatic-affiliate'); ?></h2>
                <p>
                    <?php foreach ($moduleStatus as $module => $status) : ?>
                        <span style="margin-right:10px">
                            <?php echo esc_html($module); ?>:
                            <span class="ppae-status <?php echo $status ? 'ppae-status-ok' : 'ppae-status-off'; ?>"><?php echo $status ? esc_html__('aktywny', 'peartree-pro-programmatic-affiliate') : esc_html__('nieaktywny', 'peartree-pro-programmatic-affiliate'); ?></span>
                        </span>
                    <?php endforeach; ?>
                </p>
            </div>

            <div class="ppae-card">
                <h2><?php echo esc_html__('Ostatnia aktywność', 'peartree-pro-programmatic-affiliate'); ?></h2>
                <table class="widefat striped">
                    <thead><tr><th><?php echo esc_html__('Data', 'peartree-pro-programmatic-affiliate'); ?></th><th><?php echo esc_html__('Produkt', 'peartree-pro-programmatic-affiliate'); ?></th><th><?php echo esc_html__('Referrer', 'peartree-pro-programmatic-affiliate'); ?></th></tr></thead>
                    <tbody>
                    <?php if (empty($recentActivity)) : ?>
                        <tr><td colspan="3"><?php echo esc_html__('Brak aktywności.', 'peartree-pro-programmatic-affiliate'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($recentActivity as $row) : ?>
                            <tr>
                                <td><?php echo esc_html((string) ($row['date'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($row['product_title'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($row['referrer'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
