<?php
/**
 * Plugin Name: Poradnik Platform Loader (MU)
 * Description: Bootstrap loader for Poradnik.PRO platform modules.
 * Version: 0.1.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('PORADNIK_PLATFORM_MU_PATH')) {
    define('PORADNIK_PLATFORM_MU_PATH', __DIR__ . '/platform-core');
}

$bootstrap_file = PORADNIK_PLATFORM_MU_PATH . '/Core/Bootstrap.php';

if (is_readable($bootstrap_file)) {
    require_once $bootstrap_file;
    \Poradnik\Platform\Core\Bootstrap::init();
}
