<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class ModuleRegistry
{
    private const OPTION_KEY = 'poradnik_platform_module_flags';

    /**
     * @return array<int, string>
     */
    public static function discoverModules(): array
    {
        $directories = glob(PORADNIK_PLATFORM_MU_PATH . '/Modules/*', GLOB_ONLYDIR);

        if ($directories === false) {
            return [];
        }

        $modules = [];

        foreach ($directories as $directory) {
            $module = basename($directory);
            if (is_string($module) && $module !== '') {
                $modules[] = $module;
            }
        }

        sort($modules);

        return $modules;
    }

    /**
     * @return array<string, bool>
     */
    public static function getFlags(): array
    {
        $modules = self::discoverModules();

        $defaultFlags = [];
        foreach ($modules as $module) {
            $defaultFlags[$module] = true;
        }

        $storedFlags = get_option(self::OPTION_KEY, []);
        if (! is_array($storedFlags)) {
            $storedFlags = [];
        }

        $storedFlags = array_intersect_key($storedFlags, $defaultFlags);

        foreach ($storedFlags as $module => $enabled) {
            $defaultFlags[$module] = (bool) $enabled;
        }

        $flags = apply_filters('poradnik_platform_module_flags', $defaultFlags, $modules);

        if (! is_array($flags)) {
            return $defaultFlags;
        }

        $flags = array_intersect_key($flags, $defaultFlags);

        foreach ($flags as $module => $enabled) {
            $defaultFlags[$module] = (bool) $enabled;
        }

        return $defaultFlags;
    }

    public static function isEnabled(string $module): bool
    {
        $flags = self::getFlags();

        if (! array_key_exists($module, $flags)) {
            return false;
        }

        return $flags[$module] === true;
    }
}
