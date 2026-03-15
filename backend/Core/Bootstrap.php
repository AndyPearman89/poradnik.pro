<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Bootstrap
{
    public static function init(): void
    {
        self::registerAutoloader();
        Runtime::init();
        self::bootAdmin();
        self::loadModuleBootstraps();

        do_action('poradnik_platform_bootstrapped');
    }

    private static function bootAdmin(): void
    {
        if (! is_admin()) {
            return;
        }

        \Poradnik\Platform\Admin\ModuleFlagsPage::init();
        \Poradnik\Platform\Admin\StripeSettingsPage::init();
    }

    private static function registerAutoloader(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'Poradnik\\Platform\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = str_replace('\\\\', '/', $relative);
            $file = PORADNIK_PLATFORM_MU_PATH . '/' . $path . '.php';

            if (is_readable($file)) {
                require_once $file;
            }
        });
    }

    private static function loadModuleBootstraps(): void
    {
        $modules = ModuleRegistry::discoverModules();
        $flags = ModuleRegistry::getFlags();

        foreach ($modules as $module) {
            $enabled = $flags[$module] ?? true;

            if (! $enabled) {
                do_action('poradnik_platform_module_skipped', $module);
                continue;
            }

            $file = PORADNIK_PLATFORM_MU_PATH . '/Modules/' . $module . '/bootstrap.php';

            if (! is_readable($file)) {
                do_action('poradnik_platform_module_missing_bootstrap', $module, $file);
                continue;
            }

            require_once $file;
            do_action('poradnik_platform_module_loaded_file', $module, $file);
        }

        do_action('poradnik_platform_modules_loaded');
    }
}
