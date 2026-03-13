<?php

namespace Poradnik\Platform\Modules\SaasPackages;

use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Domain\Saas\PackageService;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_filter('peartree_saas_packages', [self::class, 'providePackages']);

        EventLogger::dispatch('peartree_saas_packages_module_loaded');
    }

    /**
     * @param array<string, array<string, mixed>> $packages
     * @return array<string, array<string, mixed>>
     */
    public static function providePackages(array $packages): array
    {
        return $packages;
    }
}
