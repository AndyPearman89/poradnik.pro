<?php
/**
 * Plugin Name: peartree.pro Afilacja i AdSense
 * Plugin URI: https://poradnik.pro
 * Description: Lekki silnik monetyzacji: Google AdSense + afiliacja + tracking klikniÄ™Ä‡ + shortcode.
 * Version: 1.0.0
 * Author: peartree.pro
 * Text Domain: peartree-pro-afiliacja-adsense
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PAA_FILE', __FILE__);
define('PAA_PATH', plugin_dir_path(__FILE__));
define('PAA_URL', plugin_dir_url(__FILE__));
define('PAA_VERSION', '1.0.0');

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('peartree-pro-afiliacja-adsense', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 5);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Poradnik\\AfilacjaAdsense\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = PAA_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, static function (): void {
    Poradnik\AfilacjaAdsense\Core\Kernel::activate();
});

register_deactivation_hook(__FILE__, static function (): void {
    Poradnik\AfilacjaAdsense\Core\Kernel::deactivate();
});

add_action('plugins_loaded', static function (): void {
    (new Poradnik\AfilacjaAdsense\Core\Kernel())->boot();
});

