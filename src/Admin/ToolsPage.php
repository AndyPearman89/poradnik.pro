<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;
use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;

class ToolsPage
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

        $notice = '';
        if (isset($_POST['ppae_tools_action'])) {
            check_admin_referer('ppae_tools_action_nonce');

            $action = sanitize_key((string) wp_unslash($_POST['ppae_tools_action']));
            if ($action === 'flush_cache') {
                $this->affiliateRepository->clearAllCaches();
                $this->seoRepository->clearCaches();
                $notice = __('Cache został wyczyszczony.', 'peartree-pro-programmatic-affiliate');
            }

            if ($action === 'flush_rewrite') {
                flush_rewrite_rules();
                $notice = __('Reguły rewrite zostały odświeżone.', 'peartree-pro-programmatic-affiliate');
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Narzędzia', 'peartree-pro-programmatic-affiliate'); ?></h1>

            <?php if ($notice !== '') : ?>
                <div class="notice notice-success"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <div class="postbox" style="padding:16px;max-width:840px;">
                <h2><?php echo esc_html__('Utrzymanie systemu', 'peartree-pro-programmatic-affiliate'); ?></h2>
                <p><?php echo esc_html__('Narzędzia administracyjne do utrzymania wydajności i spójności działania pluginu.', 'peartree-pro-programmatic-affiliate'); ?></p>

                <form method="post" style="margin-bottom:10px">
                    <?php wp_nonce_field('ppae_tools_action_nonce'); ?>
                    <input type="hidden" name="ppae_tools_action" value="flush_cache">
                    <?php submit_button(__('Wyczyść cache pluginu', 'peartree-pro-programmatic-affiliate'), 'secondary', '', false); ?>
                </form>

                <form method="post">
                    <?php wp_nonce_field('ppae_tools_action_nonce'); ?>
                    <input type="hidden" name="ppae_tools_action" value="flush_rewrite">
                    <?php submit_button(__('Odśwież rewrite rules', 'peartree-pro-programmatic-affiliate'), 'secondary', '', false); ?>
                </form>
            </div>
        </div>
        <?php
    }
}
