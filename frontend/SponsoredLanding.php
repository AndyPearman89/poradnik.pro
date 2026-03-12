<?php

namespace PPAM\Frontend;

use PPAM\Core\CampaignManager;

if (!defined('ABSPATH')) {
    exit;
}

class SponsoredLanding
{
    public static function init(): void
    {
        add_shortcode('ppam_sponsored_landing', [self::class, 'render']);
    }

    public static function render(): string
    {
        $copy = self::getCopy();
        $slots = CampaignManager::getSlots();
        $sponsoredSlots = [
            'sponsored_article',
            'sponsored_link',
            'homepage_promo',
            'ranking_top_routers',
            'ranking_top_hosting',
            'ranking_top_laptops',
            'ranking_top_tools',
        ];

        $panelUrl = home_url('/panel-reklamodawcy/');
        if (is_user_logged_in()) {
            $startUrl = add_query_arg('ppam_tab', 'campaigns', $panelUrl);
        } else {
            $startUrl = wp_login_url($panelUrl);
        }

        $themeContainerClass = self::getThemeContainerClass();
        $themeSlugClass = self::getThemeSlugClass();

        ob_start();
        ?>
        <div class="ppam-landing-wrap <?php echo esc_attr($themeContainerClass); ?> <?php echo esc_attr($themeSlugClass); ?>">
        <div class="ppam-landing">
            <section class="ppam-box ppam-landing-hero">
                <h1><?php echo esc_html($copy['hero_title']); ?></h1>
                <p><?php echo esc_html($copy['hero_text']); ?></p>
                <div class="ppam-payments">
                    <a class="button button-primary" href="<?php echo esc_url($startUrl); ?>"><?php echo esc_html($copy['hero_cta_primary']); ?></a>
                    <a class="button" href="<?php echo esc_url($panelUrl); ?>"><?php echo esc_html($copy['hero_cta_secondary']); ?></a>
                </div>
            </section>

            <section class="ppam-box">
                <h2><?php esc_html_e('Dlaczego warto', 'peartree-pro-ads-marketplace'); ?></h2>
                <div class="ppam-kpis">
                    <div class="ppam-kpi">
                        <strong><?php echo esc_html($copy['value_1_title']); ?></strong>
                        <span><?php echo esc_html($copy['value_1_text']); ?></span>
                    </div>
                    <div class="ppam-kpi">
                        <strong><?php echo esc_html($copy['value_2_title']); ?></strong>
                        <span><?php echo esc_html($copy['value_2_text']); ?></span>
                    </div>
                    <div class="ppam-kpi">
                        <strong><?php echo esc_html($copy['value_3_title']); ?></strong>
                        <span><?php echo esc_html($copy['value_3_text']); ?></span>
                    </div>
                </div>
            </section>

            <section class="ppam-box">
                <h2><?php esc_html_e('Oferta i cennik', 'peartree-pro-ads-marketplace'); ?></h2>
                <table class="ppam-table ppam-landing-table">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Slot', 'peartree-pro-ads-marketplace'); ?></th>
                        <th><?php esc_html_e('Format', 'peartree-pro-ads-marketplace'); ?></th>
                        <th><?php esc_html_e('Cena bazowa', 'peartree-pro-ads-marketplace'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($slots as $slotKey => $slotData) : ?>
                        <tr>
                            <td><?php echo esc_html((string) $slotData['label']); ?></td>
                            <td><?php echo esc_html($slotKey); ?></td>
                            <td><?php echo esc_html(number_format((float) $slotData['price'], 2, ',', ' ') . ' PLN'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section class="ppam-box">
                <h2><?php esc_html_e('Aktywne oferty sponsorowane', 'peartree-pro-ads-marketplace'); ?></h2>
                <div class="ppam-landing-offers">
                    <?php $hasAnyOffer = false; ?>
                    <?php foreach ($sponsoredSlots as $slot) : ?>
                        <?php $campaign = CampaignManager::getActiveCampaignForSlot($slot); ?>
                        <?php if ($campaign) : ?>
                            <?php
                            $hasAnyOffer = true;
                            $id = (int) $campaign->ID;
                            $url = (string) get_post_meta($id, '_ppam_target_url', true);
                            $slotLabel = isset($slots[$slot]['label']) ? (string) $slots[$slot]['label'] : $slot;
                            ?>
                            <div class="ppam-sponsored-item">
                                <h3><?php echo esc_html($slotLabel); ?></h3>
                                <a href="<?php echo esc_url($url); ?>" rel="sponsored nofollow noopener" target="_blank"><?php echo esc_html((string) $campaign->post_title); ?></a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (!$hasAnyOffer) : ?>
                    <p><?php echo esc_html($copy['empty_offers_text']); ?></p>
                <?php endif; ?>
            </section>

            <section class="ppam-box ppam-landing-cta">
                <h2><?php esc_html_e('Jak zaczÄ…Ä‡', 'peartree-pro-ads-marketplace'); ?></h2>
                <ol class="ppam-landing-steps">
                    <li><?php echo esc_html($copy['step_1']); ?></li>
                    <li><?php echo esc_html($copy['step_2']); ?></li>
                    <li><?php echo esc_html($copy['step_3']); ?></li>
                </ol>
                <div class="ppam-payments">
                    <a class="button button-primary" href="<?php echo esc_url($startUrl); ?>"><?php echo esc_html($copy['final_cta']); ?></a>
                </div>
            </section>
        </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function getCopy(): array
    {
        $variant = (string) apply_filters('ppam_landing_copy_variant', 'performance');

        $performance = [
            'hero_title' => __('Marketplace reklam peartree.pro', 'peartree-pro-ads-marketplace'),
            'hero_text' => __('Docieraj do klientĂłw w momencie decyzji zakupowej â€” w poradnikach, rankingach i slotach premium. Uruchom kampaniÄ™ w kilka minut i skaluj widocznoĹ›Ä‡ marki.', 'peartree-pro-ads-marketplace'),
            'hero_cta_primary' => __('Uruchom kampaniÄ™ teraz', 'peartree-pro-ads-marketplace'),
            'hero_cta_secondary' => __('PrzejdĹş do panelu reklamodawcy', 'peartree-pro-ads-marketplace'),
            'value_1_title' => __('Precyzyjny target', 'peartree-pro-ads-marketplace'),
            'value_1_text' => __('ObecnoĹ›Ä‡ przy treĹ›ciach i kategoriach, ktĂłre realnie konwertujÄ….', 'peartree-pro-ads-marketplace'),
            'value_2_title' => __('PeĹ‚na kontrola wynikĂłw', 'peartree-pro-ads-marketplace'),
            'value_2_text' => __('Na bieĹĽÄ…co monitorujesz klikniÄ™cia, status i przebieg kampanii.', 'peartree-pro-ads-marketplace'),
            'value_3_title' => __('Elastyczne formaty', 'peartree-pro-ads-marketplace'),
            'value_3_text' => __('Baner, link sponsorowany, artykuĹ‚ i ranking â€” dobierasz format do celu kampanii.', 'peartree-pro-ads-marketplace'),
            'empty_offers_text' => __('Aktualnie brak aktywnych ofert sponsorowanych. To dobry moment, aby zarezerwowaÄ‡ najlepsze sloty dla swojej marki.', 'peartree-pro-ads-marketplace'),
            'step_1' => __('UtwĂłrz kampaniÄ™, wybierz format i dopasuj budĹĽet do celu.', 'peartree-pro-ads-marketplace'),
            'step_2' => __('Sfinalizuj pĹ‚atnoĹ›Ä‡ wygodnie przez Stripe lub PayPal.', 'peartree-pro-ads-marketplace'),
            'step_3' => __('Po akceptacji kampania startuje automatycznie i zbiera wyniki w panelu.', 'peartree-pro-ads-marketplace'),
            'final_cta' => __('Rozpocznij kampaniÄ™ reklamowÄ…', 'peartree-pro-ads-marketplace'),
        ];

        $premium = [
            'hero_title' => __('Premium Marketplace dla marek technologicznych', 'peartree-pro-ads-marketplace'),
            'hero_text' => __('Buduj prestiĹĽ marki i zwiÄ™kszaj udziaĹ‚ w rynku dziÄ™ki obecnoĹ›ci w jakoĹ›ciowych poradnikach, rankingach i formatach sponsorowanych.', 'peartree-pro-ads-marketplace'),
            'hero_cta_primary' => __('Zarezerwuj ekspozycjÄ™ premium', 'peartree-pro-ads-marketplace'),
            'hero_cta_secondary' => __('OtwĂłrz panel partnera', 'peartree-pro-ads-marketplace'),
            'value_1_title' => __('Wysoka jakoĹ›Ä‡ kontekstu', 'peartree-pro-ads-marketplace'),
            'value_1_text' => __('Twoja oferta pojawia siÄ™ obok treĹ›ci eksperckich i zakupowych.', 'peartree-pro-ads-marketplace'),
            'value_2_title' => __('Transparentne wyniki', 'peartree-pro-ads-marketplace'),
            'value_2_text' => __('Masz staĹ‚y wglÄ…d w status kampanii, klikniÄ™cia i efektywnoĹ›Ä‡.', 'peartree-pro-ads-marketplace'),
            'value_3_title' => __('Formaty dla rĂłĹĽnych celĂłw', 'peartree-pro-ads-marketplace'),
            'value_3_text' => __('Od szybkiej ekspozycji po rozbudowane publikacje sponsorowane i rankingi.', 'peartree-pro-ads-marketplace'),
            'empty_offers_text' => __('Brak aktywnych ofert sponsorowanych. Zarezerwuj slot i bÄ…dĹş pierwszÄ… widocznÄ… markÄ… w tej przestrzeni.', 'peartree-pro-ads-marketplace'),
            'step_1' => __('Skonfiguruj kampaniÄ™ i wybierz najbardziej dopasowane placementy.', 'peartree-pro-ads-marketplace'),
            'step_2' => __('ZatwierdĹş budĹĽet i opĹ‚aÄ‡ kampaniÄ™ online.', 'peartree-pro-ads-marketplace'),
            'step_3' => __('Po akceptacji uruchamiamy emisjÄ™ i raportowanie wynikĂłw.', 'peartree-pro-ads-marketplace'),
            'final_cta' => __('Uruchom kampaniÄ™ premium', 'peartree-pro-ads-marketplace'),
        ];

        return $variant === 'premium' ? $premium : $performance;
    }

    private static function getThemeContainerClass(): string
    {
        $stylesheet = strtolower((string) wp_get_theme()->get_stylesheet());

        if ($stylesheet === 'poradnik-pro-portal') {
            return 'pp-container';
        }

        if ($stylesheet === 'generatepress-child-poradnik-pro') {
            return 'container';
        }

        if ($stylesheet === 'generatepress') {
            return 'grid-container';
        }

        return '';
    }

    private static function getThemeSlugClass(): string
    {
        $stylesheet = strtolower((string) wp_get_theme()->get_stylesheet());
        return 'ppam-theme-' . sanitize_html_class(str_replace('_', '-', $stylesheet));
    }
}


