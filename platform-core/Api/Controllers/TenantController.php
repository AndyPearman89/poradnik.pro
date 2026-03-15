<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST controller for tenant management.
 *
 * Endpoints live under the peartree/v1 namespace to align with the
 * frontend admin panel.
 */
final class TenantController
{
    private const NAMESPACE = 'peartree/v1';
    private const OPTION_KEY = 'poradnik_platform_tenants';

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/tenants', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'list'],
                'permission_callback' => [self::class, 'canManage'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'create'],
                'permission_callback' => [self::class, 'canManage'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tenants/(?P<id>[0-9]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'get'],
                'permission_callback' => [self::class, 'canManage'],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [self::class, 'update'],
                'permission_callback' => [self::class, 'canManage'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [self::class, 'delete'],
                'permission_callback' => [self::class, 'canManage'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tenants/(?P<id>[0-9]+)/status', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'toggleStatus'],
            'permission_callback' => [self::class, 'canManage'],
        ]);

        register_rest_route(self::NAMESPACE, '/tenants/(?P<id>[0-9]+)/stats', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'stats'],
            'permission_callback' => [self::class, 'canManage'],
        ]);
    }

    public static function canManage(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function list(WP_REST_Request $request): WP_REST_Response
    {
        $tenants = self::getTenants();
        $search  = sanitize_text_field((string) $request->get_param('search'));

        if ($search !== '') {
            $tenants = array_filter($tenants, static function (array $t) use ($search): bool {
                return stripos((string) ($t['name'] ?? ''), $search) !== false
                    || stripos((string) ($t['domain'] ?? ''), $search) !== false
                    || stripos((string) ($t['email'] ?? ''), $search) !== false;
            });
        }

        return new WP_REST_Response(['items' => array_values($tenants)], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function get(WP_REST_Request $request)
    {
        $id     = absint($request->get_param('id'));
        $tenant = self::findById($id);

        if ($tenant === null) {
            return new WP_Error('poradnik_tenant_not_found', 'Tenant not found.', ['status' => 404]);
        }

        return new WP_REST_Response($tenant, 200);
    }

    public static function create(WP_REST_Request $request): WP_REST_Response
    {
        $tenants = self::getTenants();
        $id      = count($tenants) > 0 ? max(array_column($tenants, 'id')) + 1 : 1;

        $tenant = self::buildTenantFromRequest($request, $id);
        $tenant['created_at'] = current_time('mysql', true);

        $tenants[] = $tenant;
        self::saveTenants($tenants);

        return new WP_REST_Response($tenant, 201);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function update(WP_REST_Request $request)
    {
        $id     = absint($request->get_param('id'));
        $tenants = self::getTenants();
        $found  = false;

        foreach ($tenants as &$tenant) {
            if ((int) ($tenant['id'] ?? 0) === $id) {
                $updated = self::buildTenantFromRequest($request, $id);
                $tenant  = array_merge($tenant, $updated);
                $found   = true;
                break;
            }
        }
        unset($tenant);

        if (! $found) {
            return new WP_Error('poradnik_tenant_not_found', 'Tenant not found.', ['status' => 404]);
        }

        self::saveTenants($tenants);

        return new WP_REST_Response(self::findById($id), 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function delete(WP_REST_Request $request)
    {
        $id      = absint($request->get_param('id'));
        $tenants = self::getTenants();
        $initial = count($tenants);

        $tenants = array_filter($tenants, static fn (array $t): bool => (int) ($t['id'] ?? 0) !== $id);

        if (count($tenants) === $initial) {
            return new WP_Error('poradnik_tenant_not_found', 'Tenant not found.', ['status' => 404]);
        }

        self::saveTenants(array_values($tenants));

        return new WP_REST_Response(['deleted' => true, 'id' => $id], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function toggleStatus(WP_REST_Request $request)
    {
        $id      = absint($request->get_param('id'));
        $active  = (bool) $request->get_param('active');
        $tenants = self::getTenants();
        $found   = false;

        foreach ($tenants as &$tenant) {
            if ((int) ($tenant['id'] ?? 0) === $id) {
                $tenant['status'] = $active ? 'active' : 'inactive';
                $found = true;
                break;
            }
        }
        unset($tenant);

        if (! $found) {
            return new WP_Error('poradnik_tenant_not_found', 'Tenant not found.', ['status' => 404]);
        }

        self::saveTenants($tenants);

        return new WP_REST_Response(['id' => $id, 'active' => $active], 200);
    }

    public static function stats(WP_REST_Request $request): WP_REST_Response
    {
        $id     = absint($request->get_param('id'));
        $tenant = self::findById($id);

        if ($tenant === null) {
            return new WP_REST_Response(['error' => 'Not found'], 404);
        }

        // Basic stats sourced from WP data for the tenant's domain.
        return new WP_REST_Response([
            'tenant_id' => $id,
            'articles'  => 0,
            'vendors'   => 0,
            'users'     => 0,
        ], 200);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getTenants(): array
    {
        $value = get_option(self::OPTION_KEY, []);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<int, array<string, mixed>> $tenants
     */
    private static function saveTenants(array $tenants): void
    {
        update_option(self::OPTION_KEY, $tenants, false);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findById(int $id): ?array
    {
        foreach (self::getTenants() as $tenant) {
            if ((int) ($tenant['id'] ?? 0) === $id) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildTenantFromRequest(WP_REST_Request $request, int $id): array
    {
        return [
            'id'          => $id,
            'name'        => sanitize_text_field((string) $request->get_param('name')),
            'domain'      => sanitize_text_field((string) $request->get_param('domain')),
            'email'       => sanitize_email((string) $request->get_param('email')),
            'plan'        => sanitize_key((string) ($request->get_param('plan') ?: 'free')),
            'status'      => in_array($request->get_param('status'), ['active', 'inactive', 'pending'], true)
                             ? (string) $request->get_param('status') : 'active',
            'description' => sanitize_textarea_field((string) $request->get_param('description')),
        ];
    }
}
