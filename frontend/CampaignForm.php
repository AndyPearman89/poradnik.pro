<?php

namespace PPAM\Frontend;

use PPAM\Core\CampaignManager;
use PPAM\Payments\PayPal;
use PPAM\Payments\Stripe;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignForm
{
    public static function init(): void
    {
        add_shortcode('ppam_campaign_form', [self::class, 'renderShortcode']);
    }

    public static function renderShortcode(): string
    {
        if (!is_user_logged_in()) {
            return '<div class="ppam-box"><p>' . esc_html__('Zaloguj siÄ™, aby utworzyÄ‡ kampaniÄ™ reklamowÄ….', 'peartree-pro-ads-marketplace') . '</p></div>';
        }

        $message = '';
        $checkout = [];

        if (isset($_POST['ppam_create_campaign'])) {
            $nonce = isset($_POST['ppam_campaign_nonce']) ? (string) wp_unslash($_POST['ppam_campaign_nonce']) : '';
            if ($nonce === '' || !wp_verify_nonce($nonce, 'ppam_create_campaign_action')) {
                $message = '<div class="ppam-alert ppam-alert-error">' . esc_html__('NieprawidĹ‚owy token bezpieczeĹ„stwa.', 'peartree-pro-ads-marketplace') . '</div>';
            } else {
                $requestData = wp_unslash($_POST);
                $campaignData = [
                    'name' => isset($requestData['name']) ? (string) $requestData['name'] : '',
                    'slot' => isset($requestData['slot']) ? (string) $requestData['slot'] : '',
                    'target_url' => isset($requestData['target_url']) ? (string) $requestData['target_url'] : '',
                    'banner_url' => isset($requestData['banner_url']) ? (string) $requestData['banner_url'] : '',
                    'duration_days' => isset($requestData['duration_days']) ? (string) $requestData['duration_days'] : '',
                    'budget' => isset($requestData['budget']) ? (string) $requestData['budget'] : '',
                ];

                $campaignId = CampaignManager::createCampaign($campaignData, get_current_user_id());
                if ($campaignId > 0) {
                    $returnUrl = remove_query_arg(['ppam_pay', 'paid', 'campaign', 'ppam_nonce']);
                    $checkout  = [
                        'stripe' => Stripe::getCheckoutUrl($campaignId, $returnUrl),
                        'paypal' => PayPal::getCheckoutUrl($campaignId, $returnUrl),
                    ];
                    $message = '<div class="ppam-alert ppam-alert-success">' . esc_html__('Kampania zostaĹ‚a utworzona. Wybierz metodÄ™ pĹ‚atnoĹ›ci.', 'peartree-pro-ads-marketplace') . '</div>';
                } else {
                    $message = '<div class="ppam-alert ppam-alert-error">' . esc_html__('Nie udaĹ‚o siÄ™ utworzyÄ‡ kampanii. SprawdĹş pola formularza.', 'peartree-pro-ads-marketplace') . '</div>';
                }
            }
        }

        $slots = CampaignManager::getSlots();

        ob_start();
        ?>
        <div class="ppam-box">
            <h3><?php esc_html_e('UtwĂłrz kampaniÄ™ reklamowÄ…', 'peartree-pro-ads-marketplace'); ?></h3>
            <?php echo wp_kses_post($message); ?>
            <form method="post" class="ppam-form">
                <?php wp_nonce_field('ppam_create_campaign_action', 'ppam_campaign_nonce'); ?>
                <p>
                    <label><?php esc_html_e('Nazwa kampanii', 'peartree-pro-ads-marketplace'); ?></label>
                    <input type="text" name="name" required>
                </p>
                <p>
                    <label><?php esc_html_e('Miejsce reklamy', 'peartree-pro-ads-marketplace'); ?></label>
                    <select name="slot" required>
                        <option value=""><?php esc_html_e('Wybierz slot', 'peartree-pro-ads-marketplace'); ?></option>
                        <?php foreach ($slots as $slotKey => $slotData) : ?>
                            <option value="<?php echo esc_attr($slotKey); ?>"><?php echo esc_html((string) $slotData['label'] . ' â€” ' . number_format((float) $slotData['price'], 2, ',', ' ') . ' PLN'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label><?php esc_html_e('URL docelowy', 'peartree-pro-ads-marketplace'); ?></label>
                    <input type="url" name="target_url" required>
                </p>
                <p>
                    <label><?php esc_html_e('URL banera (opcjonalnie)', 'peartree-pro-ads-marketplace'); ?></label>
                    <input type="url" name="banner_url">
                </p>
                <p>
                    <label><?php esc_html_e('Czas trwania (dni)', 'peartree-pro-ads-marketplace'); ?></label>
                    <input type="number" min="1" max="90" name="duration_days" value="30" required>
                </p>
                <p>
                    <label><?php esc_html_e('BudĹĽet (PLN, opcjonalnie)', 'peartree-pro-ads-marketplace'); ?></label>
                    <input type="number" min="0" step="0.01" name="budget" placeholder="<?php esc_attr_e('Automatycznie z cennika', 'peartree-pro-ads-marketplace'); ?>">
                </p>
                <p>
                    <button type="submit" class="button button-primary" name="ppam_create_campaign" value="1"><?php esc_html_e('UtwĂłrz kampaniÄ™', 'peartree-pro-ads-marketplace'); ?></button>
                </p>
            </form>

            <?php if (!empty($checkout)) : ?>
                <div class="ppam-payments">
                    <h4><?php esc_html_e('PĹ‚atnoĹ›Ä‡', 'peartree-pro-ads-marketplace'); ?></h4>
                    <a class="button button-primary" href="<?php echo esc_url($checkout['stripe']); ?>"><?php esc_html_e('OpĹ‚aÄ‡ Stripe', 'peartree-pro-ads-marketplace'); ?></a>
                    <a class="button" href="<?php echo esc_url($checkout['paypal']); ?>"><?php esc_html_e('OpĹ‚aÄ‡ PayPal', 'peartree-pro-ads-marketplace'); ?></a>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}

