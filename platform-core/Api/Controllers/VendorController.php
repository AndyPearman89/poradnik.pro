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
 * REST controller for vendor management.
 */
final class VendorController
{
    private const NAMESPACE = 'peartree/v1';
    private const OPTION_KEY = 'poradnik_platform_vendors';

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/vendors', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'list'],
                'permission_callback' => [self::class, 'canAccess'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'create'],
                'permission_callback' => [self::class, 'canManage'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/vendors/(?P<id>[0-9]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'get'],
                'permission_callback' => [self::class, 'canAccessOwn'],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [self::class, 'update'],
                'permission_callback' => [self::class, 'canAccessOwn'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [self::class, 'delete'],
                'permission_callback' => [self::class, 'canManage'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/vendors/(?P<id>[0-9]+)/approve', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'approve'],
            'permission_callback' => [self::class, 'canManage'],
        ]);

        register_rest_route(self::NAMESPACE, '/vendors/(?P<id>[0-9]+)/suspend', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'suspend'],
            'permission_callback' => [self::class, 'canManage'],
        ]);

        register_rest_route(self::NAMESPACE, '/vendors/(?P<id>[0-9]+)/metrics', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'metrics'],
            'permission_callback' => [self::class, 'canAccessOwn'],
        ]);
    }

    public static function canManage(): bool
    {
        return Capabilities::canManagePlatform();
    }

    public static function canAccess(): bool
    {
        return Capabilities::canManagePlatform()
            || current_user_can('poradnik_specialist')
            || current_user_can('poradnik_advertiser');
    }

    public static function canAccessOwn(): bool
    {
        return is_user_logged_in();
    }

    public static function list(WP_REST_Request $request): WP_REST_Response
    {
        $vendors = self::getVendors();
        $search  = sanitize_text_field((string) $request->get_param('search'));
        $status  = sanitize_key((string) $request->get_param('status'));

        if ($search !== '') {
            $vendors = array_filter($vendors, static function (array $v) use ($search): bool {
                return stripos((string) ($v['name'] ?? ''), $search) !== false
                    || stripos((string) ($v['email'] ?? ''), $search) !== false
                    || stripos((string) ($v['category'] ?? ''), $search) !== false;
            });
        }

        if ($status !== '') {
            $vendors = array_filter($vendors, static fn (array $v): bool => ($v['status'] ?? '') === $status);
        }

        // Non-admins only see their own vendor record.
        if (! Capabilities::canManagePlatform()) {
            $userId  = get_current_user_id();
            $vendors = array_filter($vendors, static fn (array $v): bool => (int) ($v['user_id'] ?? 0) === $userId);
        }

        return new WP_REST_Response(['items' => array_values($vendors)], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function get(WP_REST_Request $request)
    {
        $id     = absint($request->get_param('id'));
        $vendor = self::findById($id);

        if ($vendor === null) {
            return new WP_Error('poradnik_vendor_not_found', 'Vendor not found.', ['status' => 404]);
        }

        if (! Capabilities::canManagePlatform() && (int) ($vendor['user_id'] ?? 0) !== get_current_user_id()) {
            return new WP_Error('poradnik_vendor_forbidden', 'Access denied.', ['status' => 403]);
        }

        return new WP_REST_Response($vendor, 200);
    }

    public static function create(WP_REST_Request $request): WP_REST_Response
    {
        $vendors = self::getVendors();
        $id      = count($vendors) > 0 ? max(array_column($vendors, 'id')) + 1 : 1;

        $vendor = self::buildVendorFromRequest($request, $id);
        $vendor['user_id']    = get_current_user_id();
        $vendor['created_at'] = current_time('mysql', true);
        $vendor['status']     = 'pending';

        $vendors[] = $vendor;
        self::saveVendors($vendors);

        return new WP_REST_Response($vendor, 201);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function update(WP_REST_Request $request)
    {
        $id      = absint($request->get_param('id'));
        $vendors = self::getVendors();
        $found   = false;

        foreach ($vendors as &$vendor) {
            if ((int) ($vendor['id'] ?? 0) === $id) {
                if (! Capabilities::canManagePlatform() && (int) ($vendor['user_id'] ?? 0) !== get_current_user_id()) {
                    return new WP_Error('poradnik_vendor_forbidden', 'Access denied.', ['status' => 403]);
                }
                $updated = self::buildVendorFromRequest($request, $id);
                $vendor  = array_merge($vendor, $updated);
                $found   = true;
                break;
            }
        }
        unset($vendor);

        if (! $found) {
            return new WP_Error('poradnik_vendor_not_found', 'Vendor not found.', ['status' => 404]);
        }

        self::saveVendors($vendors);

        return new WP_REST_Response(self::findById($id), 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function delete(WP_REST_Request $request)
    {
        $id      = absint($request->get_param('id'));
        $vendors = self::getVendors();
        $initial = count($vendors);

        $vendors = array_filter($vendors, static fn (array $v): bool => (int) ($v['id'] ?? 0) !== $id);

        if (count($vendors) === $initial) {
            return new WP_Error('poradnik_vendor_not_found', 'Vendor not found.', ['status' => 404]);
        }

        self::saveVendors(array_values($vendors));

        return new WP_REST_Response(['deleted' => true, 'id' => $id], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function approve(WP_REST_Request $request)
    {
        return self::setStatus($request, 'approved');
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function suspend(WP_REST_Request $request)
    {
        return self::setStatus($request, 'suspended');
    }

    public static function metrics(WP_REST_Request $request): WP_REST_Response
    {
        $id     = absint($request->get_param('id'));
        $vendor = self::findById($id);

        if ($vendor === null) {
            return new WP_REST_Response(['error' => 'Not found'], 404);
        }

        return new WP_REST_Response([
            'vendor_id'   => $id,
            'impressions' => 0,
            'clicks'      => 0,
            'conversions' => 0,
            'revenue'     => 0.0,
        ], 200);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * @return WP_REST_Response|WP_Error
     */
    private static function setStatus(WP_REST_Request $request, string $status)
    {
        $id      = absint($request->get_param('id'));
        $vendors = self::getVendors();
        $found   = false;

        foreach ($vendors as &$vendor) {
            if ((int) ($vendor['id'] ?? 0) === $id) {
                $vendor['status'] = $status;
                $found = true;
                break;
            }
        }
        unset($vendor);

        if (! $found) {
            return new WP_Error('poradnik_vendor_not_found', 'Vendor not found.', ['status' => 404]);
        }

        self::saveVendors($vendors);

        return new WP_REST_Response(['id' => $id, 'status' => $status], 200);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getVendors(): array
    {
        $value = get_option(self::OPTION_KEY, []);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<int, array<string, mixed>> $vendors
     */
    private static function saveVendors(array $vendors): void
    {
        update_option(self::OPTION_KEY, $vendors, false);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findById(int $id): ?array
    {
        foreach (self::getVendors() as $vendor) {
            if ((int) ($vendor['id'] ?? 0) === $id) {
                return $vendor;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildVendorFromRequest(WP_REST_Request $request, int $id): array
    {
        $commissionRate = $request->get_param('commission_rate');

        return [
            'id'              => $id,
            'name'            => sanitize_text_field((string) $request->get_param('name')),
            'email'           => sanitize_email((string) $request->get_param('email')),
            'website'         => esc_url_raw((string) $request->get_param('website')),
            'category'        => sanitize_text_field((string) $request->get_param('category')),
            'description'     => sanitize_textarea_field((string) $request->get_param('description')),
            'commission_rate' => $commissionRate !== null ? (float) $commissionRate : null,
            'status'          => in_array($request->get_param('status'), ['pending', 'active', 'approved', 'suspended', 'inactive'], true)
                                 ? (string) $request->get_param('status') : 'pending',
        ];
    }
}
