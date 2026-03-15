<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST controller for multisite / site configuration management.
 */
final class SiteConfigController
{
    private const NAMESPACE = 'peartree/v1';
    private const OPTION_KEY = 'poradnik_platform_site_config';

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/site-config', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'get'],
                'permission_callback' => [self::class, 'canManage'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'save'],
                'permission_callback' => [self::class, 'canManage'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/site-config/reset', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'reset'],
            'permission_callback' => [self::class, 'canManage'],
        ]);

        register_rest_route(self::NAMESPACE, '/sites', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'sites'],
            'permission_callback' => [self::class, 'canManage'],
        ]);

        register_rest_route(self::NAMESPACE, '/sites/(?P<site_id>[0-9]+)/config', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'getSiteConfig'],
                'permission_callback' => [self::class, 'canManage'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'saveSiteConfig'],
                'permission_callback' => [self::class, 'canManage'],
            ],
        ]);
    }

    public static function canManage(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function get(WP_REST_Request $request): WP_REST_Response
    {
        unset($request);
        $config = self::getConfig();

        return new WP_REST_Response($config, 200);
    }

    public static function save(WP_REST_Request $request): WP_REST_Response
    {
        $body   = $request->get_json_params();
        $config = is_array($body) ? self::sanitizeConfig($body) : [];

        update_option(self::OPTION_KEY, $config, false);

        return new WP_REST_Response(['saved' => true, 'config' => $config], 200);
    }

    public static function reset(WP_REST_Request $request): WP_REST_Response
    {
        unset($request);
        delete_option(self::OPTION_KEY);

        return new WP_REST_Response(['reset' => true], 200);
    }

    public static function sites(WP_REST_Request $request): WP_REST_Response
    {
        unset($request);

        if (! function_exists('get_sites')) {
            return new WP_REST_Response(['items' => []], 200);
        }

        $sites = get_sites(['fields' => 'all', 'number' => 100]);
        $items = [];

        foreach ($sites as $site) {
            $items[] = [
                'id'     => (int) $site->blog_id,
                'domain' => $site->domain,
                'path'   => $site->path,
                'name'   => get_blog_option((int) $site->blog_id, 'blogname'),
                'active' => (int) $site->deleted === 0 && (int) $site->archived === 0,
            ];
        }

        return new WP_REST_Response(['items' => $items], 200);
    }

    public static function getSiteConfig(WP_REST_Request $request): WP_REST_Response
    {
        $siteId = absint($request->get_param('site_id'));
        $key    = self::OPTION_KEY . '_site_' . $siteId;
        $config = get_option($key, []);

        return new WP_REST_Response(is_array($config) ? $config : [], 200);
    }

    public static function saveSiteConfig(WP_REST_Request $request): WP_REST_Response
    {
        $siteId = absint($request->get_param('site_id'));
        $body   = $request->get_json_params();
        $config = is_array($body) ? self::sanitizeConfig($body) : [];
        $key    = self::OPTION_KEY . '_site_' . $siteId;

        update_option($key, $config, false);

        return new WP_REST_Response(['saved' => true, 'site_id' => $siteId], 200);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private static function getConfig(): array
    {
        $stored = get_option(self::OPTION_KEY, []);

        if (! is_array($stored)) {
            return [];
        }

        return $stored;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private static function sanitizeConfig(array $raw): array
    {
        $config = [];

        $stringFields = ['site_name', 'site_tagline', 'language', 'timezone', 'theme', 'primary_color', 'admin_email'];
        foreach ($stringFields as $field) {
            if (isset($raw[$field])) {
                $config[$field] = sanitize_text_field((string) $raw[$field]);
            }
        }

        if (isset($raw['site_url'])) {
            $config['site_url'] = esc_url_raw((string) $raw['site_url']);
        }

        if (isset($raw['logo_url'])) {
            $config['logo_url'] = esc_url_raw((string) $raw['logo_url']);
        }

        // Modules: key => bool
        if (isset($raw['modules']) && is_array($raw['modules'])) {
            $config['modules'] = [];
            foreach ($raw['modules'] as $key => $value) {
                $config['modules'][sanitize_key((string) $key)] = (bool) $value;
            }
        }

        // Multisite settings
        if (isset($raw['multisite']) && is_array($raw['multisite'])) {
            $ms = $raw['multisite'];
            $config['multisite'] = [
                'enabled'           => (bool) ($ms['enabled'] ?? false),
                'subdomain_install' => (bool) ($ms['subdomain_install'] ?? true),
                'max_sites'         => absint($ms['max_sites'] ?? 10),
            ];
        }

        return $config;
    }
}
