<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Tenant\TenantRepository;
use Poradnik\Platform\Domain\Tenant\TenantService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST endpoints for multi-tenancy and vendor management.
 *
 * Namespace : peartree/v1
 * Prefix    : /tenants
 *
 * Routes:
 *   GET    /tenants                          – List all tenants (admin only)
 *   POST   /tenants                          – Provision a new tenant (admin only)
 *   GET    /tenants/{id}                     – Get single tenant
 *   PUT    /tenants/{id}                     – Update tenant (admin only)
 *   DELETE /tenants/{id}                     – Destroy tenant (admin only)
 *   POST   /tenants/{id}/activate            – Activate tenant (admin only)
 *   POST   /tenants/{id}/suspend             – Suspend tenant (admin only)
 *   POST   /tenants/{id}/plan                – Change plan (admin only)
 *   GET    /tenants/{id}/vendors             – List vendors
 *   POST   /tenants/{id}/vendors             – Add vendor (admin / tenant_admin)
 *   DELETE /tenants/{id}/vendors/{user_id}   – Remove vendor (admin / tenant_admin)
 */
final class TenantController
{
    private const NAMESPACE = 'peartree/v1';
    private const BASE      = '/tenants';

    public static function registerRoutes(): void
    {
        // Collection routes.
        register_rest_route(self::NAMESPACE, self::BASE, [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'index'],
                'permission_callback' => [self::class, 'requireAdmin'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'store'],
                'permission_callback' => [self::class, 'requireAdmin'],
            ],
        ]);

        // Single tenant routes.
        register_rest_route(self::NAMESPACE, self::BASE . '/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'show'],
                'permission_callback' => [self::class, 'canViewTenant'],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [self::class, 'update'],
                'permission_callback' => [self::class, 'requireAdmin'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [self::class, 'destroy'],
                'permission_callback' => [self::class, 'requireAdmin'],
            ],
        ]);

        // Lifecycle transitions.
        register_rest_route(self::NAMESPACE, self::BASE . '/(?P<id>\d+)/activate', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'activate'],
            'permission_callback' => [self::class, 'requireAdmin'],
        ]);

        register_rest_route(self::NAMESPACE, self::BASE . '/(?P<id>\d+)/suspend', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'suspend'],
            'permission_callback' => [self::class, 'requireAdmin'],
        ]);

        // Plan change.
        register_rest_route(self::NAMESPACE, self::BASE . '/(?P<id>\d+)/plan', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'changePlan'],
            'permission_callback' => [self::class, 'requireAdmin'],
        ]);

        // Vendor management.
        register_rest_route(self::NAMESPACE, self::BASE . '/(?P<id>\d+)/vendors', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'listVendors'],
                'permission_callback' => [self::class, 'canViewTenant'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'addVendor'],
                'permission_callback' => [self::class, 'canManageTenantVendors'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, self::BASE . '/(?P<id>\d+)/vendors/(?P<user_id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [self::class, 'removeVendor'],
            'permission_callback' => [self::class, 'canManageTenantVendors'],
        ]);
    }

    // ------------------------------------------------------------------
    // Permission callbacks
    // ------------------------------------------------------------------

    public static function requireAdmin(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function canViewTenant(WP_REST_Request $request): bool
    {
        if (Capabilities::canManagePlatform()) {
            return true;
        }

        if (! is_user_logged_in()) {
            return false;
        }

        $tenantId = (int) $request->get_param('id');
        $userId   = get_current_user_id();

        $tenant = TenantRepository::find($tenantId);
        if ($tenant === null) {
            return false;
        }

        if ($tenant->owner_id === $userId) {
            return true;
        }

        $vendors = TenantRepository::getVendors($tenantId);
        foreach ($vendors as $vendor) {
            if ((int) ($vendor['user_id'] ?? 0) === $userId) {
                return true;
            }
        }

        return false;
    }

    public static function canManageTenantVendors(WP_REST_Request $request): bool
    {
        if (Capabilities::canManagePlatform()) {
            return true;
        }

        if (! is_user_logged_in()) {
            return false;
        }

        $tenantId = (int) $request->get_param('id');
        $userId   = get_current_user_id();

        $tenant = TenantRepository::find($tenantId);
        if ($tenant === null) {
            return false;
        }

        if ($tenant->owner_id === $userId) {
            return true;
        }

        $vendors = TenantRepository::getVendors($tenantId);
        foreach ($vendors as $vendor) {
            if ((int) ($vendor['user_id'] ?? 0) === $userId && ($vendor['role'] ?? '') === 'tenant_admin') {
                return true;
            }
        }

        return false;
    }

    // ------------------------------------------------------------------
    // Handlers
    // ------------------------------------------------------------------

    public static function index(WP_REST_Request $request): WP_REST_Response
    {
        $status  = sanitize_key((string) ($request->get_param('status') ?? ''));
        $page    = max(1, absint($request->get_param('page') ?: 1));
        $perPage = max(1, min(100, absint($request->get_param('per_page') ?: 20)));
        $offset  = ($page - 1) * $perPage;

        $total = TenantRepository::count($status);
        $items = TenantRepository::paginate($perPage, $offset, $status);

        return new WP_REST_Response([
            'items' => array_map(static fn($t) => $t->toArray(), $items),
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
            'page'  => $page,
        ], 200);
    }

    public static function show(WP_REST_Request $request): WP_REST_Response
    {
        $tenant = TenantRepository::find((int) $request->get_param('id'));

        if ($tenant === null) {
            return new WP_REST_Response(['code' => 'not_found', 'message' => 'Tenant not found.'], 404);
        }

        return new WP_REST_Response($tenant->toArray(), 200);
    }

    public static function store(WP_REST_Request $request): WP_REST_Response
    {
        $params = (array) $request->get_json_params();

        $result = TenantService::provision($params);

        if (! $result['success']) {
            return new WP_REST_Response(['code' => 'provision_failed', 'message' => $result['error'] ?? 'Unknown error.'], 422);
        }

        return new WP_REST_Response($result['tenant']->toArray(), 201);
    }

    public static function update(WP_REST_Request $request): WP_REST_Response
    {
        $id     = (int) $request->get_param('id');
        $params = (array) $request->get_json_params();

        $tenant = TenantRepository::find($id);
        if ($tenant === null) {
            return new WP_REST_Response(['code' => 'not_found', 'message' => 'Tenant not found.'], 404);
        }

        $updated = TenantRepository::update($id, $params);

        if (! $updated) {
            return new WP_REST_Response(['code' => 'update_failed', 'message' => 'Could not update tenant.'], 422);
        }

        return new WP_REST_Response(TenantRepository::find($id)?->toArray() ?? [], 200);
    }

    public static function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');

        if (TenantRepository::find($id) === null) {
            return new WP_REST_Response(['code' => 'not_found', 'message' => 'Tenant not found.'], 404);
        }

        $deleted = TenantService::destroy($id);

        if (! $deleted) {
            return new WP_REST_Response(['code' => 'delete_failed', 'message' => 'Could not delete tenant.'], 422);
        }

        return new WP_REST_Response(['deleted' => true], 200);
    }

    public static function activate(WP_REST_Request $request): WP_REST_Response
    {
        return self::lifecycleTransition((int) $request->get_param('id'), 'activate');
    }

    public static function suspend(WP_REST_Request $request): WP_REST_Response
    {
        return self::lifecycleTransition((int) $request->get_param('id'), 'suspend');
    }

    public static function changePlan(WP_REST_Request $request): WP_REST_Response
    {
        $id   = (int) $request->get_param('id');
        $plan = sanitize_key((string) ($request->get_json_params()['plan'] ?? ''));

        if ($plan === '') {
            return new WP_REST_Response(['code' => 'missing_plan', 'message' => 'plan is required.'], 422);
        }

        $ok = TenantService::changePlan($id, $plan);

        if (! $ok) {
            return new WP_REST_Response(['code' => 'plan_change_failed', 'message' => 'Invalid plan or tenant not found.'], 422);
        }

        return new WP_REST_Response(['updated' => true, 'plan' => $plan], 200);
    }

    public static function listVendors(WP_REST_Request $request): WP_REST_Response
    {
        $tenantId = (int) $request->get_param('id');

        if (TenantRepository::find($tenantId) === null) {
            return new WP_REST_Response(['code' => 'not_found', 'message' => 'Tenant not found.'], 404);
        }

        return new WP_REST_Response(['vendors' => TenantRepository::getVendors($tenantId)], 200);
    }

    public static function addVendor(WP_REST_Request $request): WP_REST_Response
    {
        $tenantId = (int) $request->get_param('id');
        $params   = (array) $request->get_json_params();
        $userId   = (int) ($params['user_id'] ?? 0);
        $role     = sanitize_key((string) ($params['role'] ?? 'vendor'));

        if ($userId < 1) {
            return new WP_REST_Response(['code' => 'missing_user_id', 'message' => 'user_id is required.'], 422);
        }

        $ok = TenantService::addVendor($tenantId, $userId, $role);

        if (! $ok) {
            return new WP_REST_Response(['code' => 'add_vendor_failed', 'message' => 'Could not add vendor. Check tenant and user IDs.'], 422);
        }

        return new WP_REST_Response(['added' => true, 'user_id' => $userId, 'role' => $role], 201);
    }

    public static function removeVendor(WP_REST_Request $request): WP_REST_Response
    {
        $tenantId = (int) $request->get_param('id');
        $userId   = (int) $request->get_param('user_id');

        $ok = TenantService::removeVendor($tenantId, $userId);

        if (! $ok) {
            return new WP_REST_Response(['code' => 'remove_vendor_failed', 'message' => 'Could not remove vendor.'], 422);
        }

        return new WP_REST_Response(['removed' => true], 200);
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private static function lifecycleTransition(int $id, string $action): WP_REST_Response
    {
        if (TenantRepository::find($id) === null) {
            return new WP_REST_Response(['code' => 'not_found', 'message' => 'Tenant not found.'], 404);
        }

        $ok = match ($action) {
            'activate' => TenantService::activate($id),
            'suspend'  => TenantService::suspend($id),
            default    => false,
        };

        if (! $ok) {
            return new WP_REST_Response(['code' => 'transition_failed', 'message' => "Could not {$action} tenant."], 422);
        }

        return new WP_REST_Response(['status' => $action === 'activate' ? 'active' : 'suspended'], 200);
    }
}
