<?php

namespace Poradnik\Platform\Domain\Saas;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Defines and manages SaaS subscription packages for the Dashboard.PRO platform.
 *
 * Packages: FREE | PRO | BUSINESS | ENTERPRISE
 */
final class PackageService
{
    public const PACKAGE_FREE = 'free';
    public const PACKAGE_PRO = 'pro';
    public const PACKAGE_BUSINESS = 'business';
    public const PACKAGE_ENTERPRISE = 'enterprise';

    private const OPTION_KEY = 'peartree_user_package';

    /**
     * Returns all available package definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function packages(): array
    {
        return apply_filters('peartree_saas_packages', [
            self::PACKAGE_FREE => [
                'id' => self::PACKAGE_FREE,
                'name' => __('Free', 'poradnik-platform'),
                'price' => 0,
                'currency' => 'PLN',
                'features' => [
                    'dashboard_access' => true,
                    'articles_limit' => 5,
                    'ai_tools' => false,
                    'ads_marketplace' => false,
                    'analytics' => false,
                    'affiliate' => false,
                ],
            ],
            self::PACKAGE_PRO => [
                'id' => self::PACKAGE_PRO,
                'name' => __('Pro', 'poradnik-platform'),
                'price' => 99,
                'currency' => 'PLN',
                'features' => [
                    'dashboard_access' => true,
                    'articles_limit' => 50,
                    'ai_tools' => true,
                    'ads_marketplace' => true,
                    'analytics' => true,
                    'affiliate' => false,
                ],
            ],
            self::PACKAGE_BUSINESS => [
                'id' => self::PACKAGE_BUSINESS,
                'name' => __('Business', 'poradnik-platform'),
                'price' => 299,
                'currency' => 'PLN',
                'features' => [
                    'dashboard_access' => true,
                    'articles_limit' => 500,
                    'ai_tools' => true,
                    'ads_marketplace' => true,
                    'analytics' => true,
                    'affiliate' => true,
                ],
            ],
            self::PACKAGE_ENTERPRISE => [
                'id' => self::PACKAGE_ENTERPRISE,
                'name' => __('Enterprise', 'poradnik-platform'),
                'price' => 0,
                'currency' => 'PLN',
                'features' => [
                    'dashboard_access' => true,
                    'articles_limit' => -1,
                    'ai_tools' => true,
                    'ads_marketplace' => true,
                    'analytics' => true,
                    'affiliate' => true,
                ],
            ],
        ]);
    }

    /**
     * Returns the package definition for a given package ID.
     *
     * @return array<string, mixed>|null
     */
    public static function getPackage(string $packageId): ?array
    {
        $all = self::packages();

        return $all[$packageId] ?? null;
    }

    /**
     * Returns the active package ID for a user (defaults to FREE).
     */
    public static function getUserPackage(int $userId = 0): string
    {
        $id = $userId > 0 ? $userId : get_current_user_id();
        $stored = get_user_meta($id, self::OPTION_KEY, true);
        $package = is_string($stored) && $stored !== '' ? $stored : self::PACKAGE_FREE;

        $all = self::packages();

        return array_key_exists($package, $all) ? $package : self::PACKAGE_FREE;
    }

    /**
     * Assigns a package to a user.
     */
    public static function setUserPackage(int $userId, string $packageId): bool
    {
        $all = self::packages();

        if (! array_key_exists($packageId, $all)) {
            return false;
        }

        update_user_meta($userId, self::OPTION_KEY, $packageId);

        do_action('peartree_user_package_changed', $userId, $packageId);

        return true;
    }

    /**
     * Returns all valid package IDs.
     *
     * @return array<int, string>
     */
    public static function packageIds(): array
    {
        return array_keys(self::packages());
    }
}
