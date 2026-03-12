<?php
/**
 * Plugin Name: peartree.pro Programmatic SEO Affiliate Engine
 * Description: Automatyczny silnik monetyzacji SEO: AdSense, afiliacja, cloaking, rankingi, porównania i strony programmatic SEO.
 * Version: 1.0.0
 * Author: PearTree
 * Text Domain: peartree-pro-programmatic-affiliate
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PPAE_FILE', __FILE__);
define('PPAE_PATH', plugin_dir_path(__FILE__));
define('PPAE_URL', plugin_dir_url(__FILE__));
define('PPAE_VERSION', '1.0.0');

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('peartree-pro-programmatic-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 5);

spl_autoload_register(static function (string $class): void {
    $prefix = 'PearTree\\ProgrammaticAffiliate\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = PPAE_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, static function (): void {
    PearTree\ProgrammaticAffiliate\Core\Kernel::activate();
});

register_deactivation_hook(__FILE__, static function (): void {
    PearTree\ProgrammaticAffiliate\Core\Kernel::deactivate();
});

add_action('plugins_loaded', static function (): void {
    (new PearTree\ProgrammaticAffiliate\Core\Kernel())->boot();
});
