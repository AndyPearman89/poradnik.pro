<?php

namespace PPAM\Frontend;

use PPAM\Analytics\Stats;
use PPAM\Core\CampaignManager;
use PPAM\Payments\PayPal;
use PPAM\Payments\Stripe;

if (!defined('ABSPATH')) {
    exit;
}

class AdvertiserPanel
{
    public static function init(): void
    {
        add_shortcode('ppam_advertiser_panel', [self::class, 'render']);
    }

    public static function render(): string
    {
        if (!is_user_logged_in()) {
            return '<div class="ppam-box"><p>' . esc_html__('Panel reklamodawcy jest dostÄ™pny po zalogowaniu.', 'peartree-pro-ads-marketplace') . '</p></div>';
        }

        $notice = '';
        if (isset($_POST['ppam_campaign_action'], $_POST['ppam_campaign_id'])) {
            $nonce = isset($_POST['ppam_campaign_action_nonce']) ? (string) wp_unslash($_POST['ppam_campaign_action_nonce']) : '';
            if ($nonce === '' || !wp_verify_nonce($nonce, 'ppam_campaign_action_nonce')) {
                $notice = '<div class="ppam-alert ppam-alert-error">' . esc_html__('BĹ‚Ä…d bezpieczeĹ„stwa akcji kampanii.', 'peartree-pro-ads-marketplace') . '</div>';
            } else {
                $action = isset($_POST['ppam_campaign_action'])
                    ? sanitize_key((string) wp_unslash($_POST['ppam_campaign_action']))
                    : '';
                $campaignId = isset($_POST['ppam_campaign_id'])
                    ? max(0, (int) wp_unslash($_POST['ppam_campaign_id']))
                    : 0;
                $ok         = CampaignManager::advertiserAction($campaignId, get_current_user_id(), $action);
                $notice     = $ok
                    ? '<div class="ppam-alert ppam-alert-success">' . esc_html__('Akcja kampanii zostaĹ‚a zapisana.', 'peartree-pro-ads-marketplace') . '</div>'
                    : '<div class="ppam-alert ppam-alert-error">' . esc_html__('Nie udaĹ‚o siÄ™ wykonaÄ‡ akcji dla kampanii.', 'peartree-pro-ads-marketplace') . '</div>';
            }
        }

        $tab = isset($_GET['ppam_tab']) ? sanitize_key((string) wp_unslash($_GET['ppam_tab'])) : 'dashboard';
        $tabs = [
            'dashboard' => __('Dashboard', 'peartree-pro-ads-marketplace'),
            'campaigns' => __('Kampanie', 'peartree-pro-ads-marketplace'),
            'banners'   => __('Banery', 'peartree-pro-ads-marketplace'),
            'stats'     => __('Statystyki', 'peartree-pro-ads-marketplace'),
            'payments'  => __('PĹ‚atnoĹ›ci', 'peartree-pro-ads-marketplace'),
        ];
        if (!isset($tabs[$tab])) {
            $tab = 'dashboard';
        }

        $campaigns = CampaignManager::getUserCampaigns(get_current_user_id());
        $activeCampaigns = 0;
        $totalImpressions = 0;
        $totalClicks = 0;

        foreach ($campaigns as $campaign) {
            $campaignId = (int) $campaign->ID;
            $status = (string) get_post_meta($campaignId, '_ppam_status', true);
            if ($status === 'active') {
                $activeCampaigns++;
            }
            $totalImpressions += (int) get_post_meta($campaignId, '_ppam_impressions', true);
            $totalClicks += (int) get_post_meta($campaignId, '_ppam_clicks', true);
        }

        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0;

        ob_start();
        ?>
        <div class="ppam-panel">
            <h2><?php esc_html_e('Panel reklamodawcy', 'peartree-pro-ads-marketplace'); ?></h2>
            <?php echo wp_kses_post($notice); ?>
            <div class="ppam-tabs">
                <?php foreach ($tabs as $key => $label) : ?>
                    <?php $tabUrl = esc_url(add_query_arg('ppam_tab', $key)); ?>
                    <a class="ppam-tab <?php echo $tab === $key ? 'is-active' : ''; ?>" href="<?php echo $tabUrl; ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($tab === 'dashboard') : ?>
                <div class="ppam-kpis">
                    <div class="ppam-kpi"><strong><?php echo esc_html((string) $activeCampaigns); ?></strong><span><?php esc_html_e('Aktywne kampanie', 'peartree-pro-ads-marketplace'); ?></span></div>
                    <div class="ppam-kpi"><strong><?php echo esc_html(number_format($totalImpressions, 0, ',', ' ')); ?></strong><span><?php esc_html_e('WyĹ›wietlenia reklam', 'peartree-pro-ads-marketplace'); ?></span></div>
                    <div class="ppam-kpi"><strong><?php echo esc_html(number_format($totalClicks, 0, ',', ' ')); ?></strong><span><?php esc_html_e('KlikniÄ™cia', 'peartree-pro-ads-marketplace'); ?></span></div>
                    <div class="ppam-kpi"><strong><?php echo esc_html(number_format($ctr, 2, ',', ' ')); ?>%</strong><span><?php esc_html_e('CTR', 'peartree-pro-ads-marketplace'); ?></span></div>
                </div>
                <div class="ppam-box"><p><?php esc_html_e('UtwĂłrz kampaniÄ™ w sekcji Kampanie i opĹ‚aÄ‡ jÄ… w sekcji PĹ‚atnoĹ›ci.', 'peartree-pro-ads-marketplace'); ?></p></div>
            <?php endif; ?>

            <?php if ($tab === 'campaigns') : ?>
                <div class="ppam-box"><?php echo do_shortcode('[ppam_campaign_form]'); ?></div>
                <?php echo self::renderCampaignTable($campaigns, false, false, true); ?>
            <?php endif; ?>

            <?php if ($tab === 'banners') : ?>
                <div class="ppam-box">
                    <h3><?php esc_html_e('Banery', 'peartree-pro-ads-marketplace'); ?></h3>
                    <p><?php esc_html_e('Banery dodajesz na poziomie kampanii: pole URL banera podczas tworzenia kampanii.', 'peartree-pro-ads-marketplace'); ?></p>
                </div>
                <?php echo self::renderCampaignTable($campaigns, true); ?>
            <?php endif; ?>

            <?php if ($tab === 'stats') : ?>
                <div class="ppam-box">
                    <h3><?php esc_html_e('Statystyki reklam', 'peartree-pro-ads-marketplace'); ?></h3>
                    <table class="ppam-table">
                        <thead><tr>
                            <th><?php esc_html_e('Kampania', 'peartree-pro-ads-marketplace'); ?></th>
                            <th><?php esc_html_e('WyĹ›wietlenia', 'peartree-pro-ads-marketplace'); ?></th>
                            <th><?php esc_html_e('KlikniÄ™cia', 'peartree-pro-ads-marketplace'); ?></th>
                            <th><?php esc_html_e('CTR', 'peartree-pro-ads-marketplace'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($campaigns as $campaign) : ?>
                            <?php $id = (int) $campaign->ID; ?>
                            <tr>
                                <td><?php echo esc_html((string) $campaign->post_title); ?></td>
                                <td><?php echo esc_html((string) (int) get_post_meta($id, '_ppam_impressions', true)); ?></td>
                                <td><?php echo esc_html((string) (int) get_post_meta($id, '_ppam_clicks', true)); ?></td>
                                <td><?php echo esc_html(number_format(Stats::getCtr($id), 2, ',', ' ') . '%'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'payments') : ?>
                <div class="ppam-box">
                    <h3><?php esc_html_e('PĹ‚atnoĹ›ci', 'peartree-pro-ads-marketplace'); ?></h3>
                    <p><?php esc_html_e('OpĹ‚aÄ‡ kampanie ze statusem â€žOczekuje na pĹ‚atnoĹ›Ä‡â€ś. Po pĹ‚atnoĹ›ci status przechodzi na â€žOczekuje na akceptacjÄ™â€ś, a aktywacjÄ™ uruchamia administrator.', 'peartree-pro-ads-marketplace'); ?></p>
                </div>
                <?php echo self::renderCampaignTable($campaigns, false, true); ?>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function renderCampaignTable(array $campaigns, bool $showBanner = false, bool $showPayments = false, bool $showControls = false): string
    {
        ob_start();
        ?>
        <div class="ppam-box">
            <table class="ppam-table">
                <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'peartree-pro-ads-marketplace'); ?></th>
                    <th><?php esc_html_e('Nazwa', 'peartree-pro-ads-marketplace'); ?></th>
                    <th><?php esc_html_e('Slot', 'peartree-pro-ads-marketplace'); ?></th>
                    <th><?php esc_html_e('Status', 'peartree-pro-ads-marketplace'); ?></th>
                    <th><?php esc_html_e('Czas trwania', 'peartree-pro-ads-marketplace'); ?></th>
                    <?php if ($showBanner) : ?><th><?php esc_html_e('Baner', 'peartree-pro-ads-marketplace'); ?></th><?php endif; ?>
                    <?php if ($showPayments) : ?><th><?php esc_html_e('PĹ‚atnoĹ›Ä‡', 'peartree-pro-ads-marketplace'); ?></th><?php endif; ?>
                    <?php if ($showControls) : ?><th><?php esc_html_e('ZarzÄ…dzanie', 'peartree-pro-ads-marketplace'); ?></th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($campaigns as $campaign) : ?>
                    <?php
                    $id = (int) $campaign->ID;
                    $slot = (string) get_post_meta($id, '_ppam_slot', true);
                    $status = (string) get_post_meta($id, '_ppam_status', true);
                    $days = (int) get_post_meta($id, '_ppam_duration_days', true);
                    $banner = (string) get_post_meta($id, '_ppam_banner_url', true);
                    $returnUrl = remove_query_arg(['ppam_pay', 'campaign', 'ppam_nonce', 'paid']);
                    ?>
                    <tr>
                        <td><?php echo esc_html((string) $id); ?></td>
                        <td><?php echo esc_html((string) $campaign->post_title); ?></td>
                        <td><?php echo esc_html($slot); ?></td>
                        <td><?php echo esc_html(CampaignManager::getStatusLabel($status)); ?></td>
                        <td><?php
                            /* translators: %d: number of days */
                            echo esc_html(sprintf(_n('%d dzieĹ„', '%d dni', $days, 'peartree-pro-ads-marketplace'), $days));
                        ?></td>
                        <?php if ($showBanner) : ?>
                            <td>
                                <?php if ($banner !== '') : ?>
                                    <a href="<?php echo esc_url($banner); ?>" target="_blank" rel="noopener"><?php esc_html_e('PodglÄ…d', 'peartree-pro-ads-marketplace'); ?></a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if ($showPayments) : ?>
                            <td>
                                <?php if ($status === 'pending_payment') : ?>
                                    <a class="button button-small" href="<?php echo esc_url(Stripe::getCheckoutUrl($id, $returnUrl)); ?>">Stripe</a>
                                    <a class="button button-small" href="<?php echo esc_url(PayPal::getCheckoutUrl($id, $returnUrl)); ?>">PayPal</a>
                                <?php elseif ($status === 'active') : ?>
                                    <span><?php esc_html_e('OpĹ‚acona', 'peartree-pro-ads-marketplace'); ?></span>
                                <?php elseif ($status === 'pending_approval') : ?>
                                    <span><?php esc_html_e('OpĹ‚acona (oczekuje akceptacji)', 'peartree-pro-ads-marketplace'); ?></span>
                                <?php else : ?>
                                    <span>&mdash;</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if ($showControls) : ?>
                            <td>
                                <?php if ($status === 'active' || $status === 'paused') : ?>
                                    <form method="post" action="" style="display:flex;gap:6px;flex-wrap:wrap">
                                        <?php wp_nonce_field('ppam_campaign_action_nonce', 'ppam_campaign_action_nonce'); ?>
                                        <input type="hidden" name="ppam_campaign_id" value="<?php echo esc_attr((string) $id); ?>">
                                        <?php if ($status === 'active') : ?>
                                            <button type="submit" class="button button-small" name="ppam_campaign_action" value="pause"><?php esc_html_e('Wstrzymaj', 'peartree-pro-ads-marketplace'); ?></button>
                                        <?php else : ?>
                                            <button type="submit" class="button button-small button-primary" name="ppam_campaign_action" value="resume"><?php esc_html_e('WznĂłw', 'peartree-pro-ads-marketplace'); ?></button>
                                        <?php endif; ?>
                                    </form>
                                <?php else : ?>
                                    <span>â€”</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}

