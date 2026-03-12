<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

use PearTree\ProgrammaticAffiliate\Adsense\AdsenseManager;

class SettingsPage
{
    private AdsenseManager $adsenseManager;

    public function __construct(AdsenseManager $adsenseManager)
    {
        $this->adsenseManager = $adsenseManager;
    }

    public function register(): void
    {
        add_action('admin_init', [$this->adsenseManager, 'registerSettings']);
        register_setting('ppae_general_group', 'ppae_general_settings', [
            'type' => 'array',
            'sanitize_callback' => static function (array $input): array {
                return [
                    'autolink_enabled' => empty($input['autolink_enabled']) ? 0 : 1,
                ];
            },
            'default' => ['autolink_enabled' => 1],
        ]);
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $adsense = $this->adsenseManager->getSettings();
        $general = get_option('ppae_general_settings', ['autolink_enabled' => 1]);
        $currentPage = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : 'ppae-adsense';
        $tab = $currentPage === 'ppae-settings' ? 'general' : 'adsense';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ustawienia', 'peartree-pro-programmatic-affiliate'); ?></h1>
            <h2 class="nav-tab-wrapper" style="margin-bottom:12px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=ppae-adsense')); ?>" class="nav-tab <?php echo $tab === 'adsense' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('AdSense', 'peartree-pro-programmatic-affiliate'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ppae-settings')); ?>" class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Ustawienia ogólne', 'peartree-pro-programmatic-affiliate'); ?></a>
            </h2>

            <?php if ($tab === 'adsense') : ?>
                <form method="post" action="options.php">
                    <?php settings_fields('ppae_adsense_group'); ?>
                    <table class="form-table">
                        <tr><th><?php echo esc_html__('ID wydawcy AdSense', 'peartree-pro-programmatic-affiliate'); ?></th><td><input class="regular-text" type="text" name="ppae_adsense_settings[publisher_id]" value="<?php echo esc_attr((string) ($adsense['publisher_id'] ?? '')); ?>"></td></tr>
                        <tr><th><?php echo esc_html__('Skrypt AdSense', 'peartree-pro-programmatic-affiliate'); ?></th><td><textarea class="large-text code" rows="6" name="ppae_adsense_settings[script]"><?php echo esc_textarea((string) ($adsense['script'] ?? '')); ?></textarea></td></tr>
                        <tr><th><?php echo esc_html__('Auto Ads', 'peartree-pro-programmatic-affiliate'); ?></th><td><label><input type="checkbox" name="ppae_adsense_settings[auto_ads]" value="1" <?php checked((int) ($adsense['auto_ads'] ?? 0), 1); ?>> <?php echo esc_html__('Włącz', 'peartree-pro-programmatic-affiliate'); ?></label></td></tr>
                    </table>
                    <?php submit_button(__('Zapisz ustawienia AdSense', 'peartree-pro-programmatic-affiliate')); ?>
                </form>
            <?php else : ?>
                <form method="post" action="options.php">
                    <?php settings_fields('ppae_general_group'); ?>
                    <table class="form-table">
                        <tr><th><?php echo esc_html__('Automatyczne linkowanie afiliacyjne', 'peartree-pro-programmatic-affiliate'); ?></th><td><label><input type="checkbox" name="ppae_general_settings[autolink_enabled]" value="1" <?php checked((int) ($general['autolink_enabled'] ?? 1), 1); ?>> <?php echo esc_html__('Włącz', 'peartree-pro-programmatic-affiliate'); ?></label></td></tr>
                    </table>
                    <?php submit_button(__('Zapisz ustawienia ogólne', 'peartree-pro-programmatic-affiliate')); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}

