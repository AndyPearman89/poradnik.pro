<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;

class StatisticsPage
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = $this->repository->getStatistics();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Statystyki', 'peartree-pro-programmatic-affiliate'); ?></h1>

            <h2><?php echo esc_html__('Najczęściej klikane produkty', 'peartree-pro-programmatic-affiliate'); ?></h2>
            <table class="widefat striped"><thead><tr><th>Produkt</th><th>Kliknięcia</th></tr></thead><tbody>
            <?php foreach (($stats['top_products'] ?? []) as $item) : ?>
                <tr><td><?php echo esc_html((string) ($item['title'] ?? '')); ?></td><td><?php echo esc_html((string) (int) ($item['clicks'] ?? 0)); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

            <h2><?php echo esc_html__('Trend kliknięć (30 dni)', 'peartree-pro-programmatic-affiliate'); ?></h2>
            <table class="widefat striped"><thead><tr><th>Data</th><th>Kliknięcia</th></tr></thead><tbody>
            <?php foreach (($stats['click_trends'] ?? []) as $item) : ?>
                <tr><td><?php echo esc_html((string) ($item['day'] ?? '')); ?></td><td><?php echo esc_html((string) (int) ($item['clicks'] ?? 0)); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

            <h2><?php echo esc_html__('Najskuteczniejsze słowa kluczowe', 'peartree-pro-programmatic-affiliate'); ?></h2>
            <table class="widefat striped"><thead><tr><th>Słowo kluczowe</th><th>Suma kliknięć</th></tr></thead><tbody>
            <?php foreach (($stats['best_keywords'] ?? []) as $item) : ?>
                <tr><td><?php echo esc_html((string) ($item['keyword'] ?? '')); ?></td><td><?php echo esc_html((string) (int) ($item['total_clicks'] ?? 0)); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
        <?php
    }
}

