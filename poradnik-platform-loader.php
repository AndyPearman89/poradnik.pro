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
    $defaultPath = __DIR__ . '/backend';
    $legacyPath = __DIR__ . '/platform-core';

    if (is_dir($defaultPath)) {
        define('PORADNIK_PLATFORM_MU_PATH', $defaultPath);
    } elseif (is_dir($legacyPath)) {
        define('PORADNIK_PLATFORM_MU_PATH', $legacyPath);
    } else {
        define('PORADNIK_PLATFORM_MU_PATH', $defaultPath);
    }
}

$bootstrap_file = PORADNIK_PLATFORM_MU_PATH . '/Core/Bootstrap.php';

if (is_readable($bootstrap_file)) {
    require_once $bootstrap_file;
    \Poradnik\Platform\Core\Bootstrap::init();
}
