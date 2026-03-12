<?php

namespace Poradnik\AfilacjaAdsense\Admin;

use Poradnik\AfilacjaAdsense\Adsense\AdsenseManager;

class SettingsPage
{
    private static ?AdsenseManager $manager = null;

    public function __construct(AdsenseManager $manager)
    {
        self::$manager = $manager;
    }

    public function register(): void
    {
        add_action('admin_init', [self::$manager, 'registerSettings']);
    }

    public static function renderPage(): void
    {
        if (!current_user_can('manage_options') || self::$manager === null) {
            return;
        }

        $settings = self::$manager->getSettings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ustawienia AdSense', 'peartree-pro-afiliacja-adsense'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('paa_adsense_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('ID wydawcy AdSense', 'peartree-pro-afiliacja-adsense'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo esc_attr(AdsenseManager::OPTION_KEY); ?>[publisher_id]" value="<?php echo esc_attr((string) ($settings['publisher_id'] ?? '')); ?>" placeholder="ca-pub-XXXXXXXX" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Skrypt AdSense', 'peartree-pro-afiliacja-adsense'); ?></th>
                        <td>
                            <textarea rows="7" class="large-text code" name="<?php echo esc_attr(AdsenseManager::OPTION_KEY); ?>[adsense_script]"><?php echo esc_textarea((string) ($settings['adsense_script'] ?? '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Auto Ads', 'peartree-pro-afiliacja-adsense'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" value="1" name="<?php echo esc_attr(AdsenseManager::OPTION_KEY); ?>[auto_ads]" <?php checked((int) ($settings['auto_ads'] ?? 0), 1); ?> />
                                <?php echo esc_html__('Włącz Auto Ads', 'peartree-pro-afiliacja-adsense'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="description"><?php echo esc_html__('Shortcode ręcznego osadzenia: [peartree_adsense placement="article_top"]', 'peartree-pro-afiliacja-adsense'); ?></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

