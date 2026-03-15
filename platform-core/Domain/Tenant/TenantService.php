<?php

namespace Poradnik\Platform\Domain\Tenant;

use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Business logic for provisioning and managing marketplace portal tenants.
 *
 * A "tenant" is a self-contained marketplace portal running within the
 * WordPress Multisite network or as a logical partition in single-site mode.
 * Each tenant may have one owner and multiple vendors.
 */
final class TenantService
{
    /** Allowed tenant status values. */
    private const STATUSES = ['pending', 'active', 'suspended', 'archived'];

    /** Allowed tenant plan keys (mirrors SaasPlans). */
    private const PLANS = ['free', 'pro', 'business', 'enterprise'];

    // ------------------------------------------------------------------
    // Provisioning
    // ------------------------------------------------------------------

    /**
     * Create and provision a new tenant portal.
     *
     * @param array<string, mixed> $params
     *   Required: name (string), slug (string), domain (string), owner_id (int)
     *   Optional: plan (string), status (string)
     * @return array{success: bool, tenant?: Tenant, error?: string}
     */
    public static function provision(array $params): array
    {
        $name     = sanitize_text_field((string) ($params['name']     ?? ''));
        $slug     = sanitize_key((string) ($params['slug']     ?? ''));
        $domain   = sanitize_text_field((string) ($params['domain']   ?? ''));
        $ownerId  = (int) ($params['owner_id'] ?? 0);
        $plan     = sanitize_key((string) ($params['plan']     ?? 'free'));
        $status   = sanitize_key((string) ($params['status']   ?? 'pending'));

        // Validate required fields.
        if ($name === '' || $slug === '' || $domain === '' || $ownerId < 1) {
            return ['success' => false, 'error' => 'Missing required fields: name, slug, domain, owner_id.'];
        }

        if (! in_array($plan, self::PLANS, true)) {
            $plan = 'free';
        }

        if (! in_array($status, self::STATUSES, true)) {
            $status = 'pending';
        }

        // Ensure slug is unique.
        if (TenantRepository::findBySlug($slug) !== null) {
            return ['success' => false, 'error' => "A tenant with slug '{$slug}' already exists."];
        }

        // Ensure the owner WordPress user exists.
        if (! get_userdata($ownerId)) {
            return ['success' => false, 'error' => "WordPress user {$ownerId} not found."];
        }

        $tenant = TenantRepository::create([
            'name'     => $name,
            'slug'     => $slug,
            'domain'   => $domain,
            'plan'     => $plan,
            'status'   => $status,
            'owner_id' => $ownerId,
        ]);

        if ($tenant === null) {
            return ['success' => false, 'error' => 'Database error: could not create tenant.'];
        }

        // Automatically assign owner as a tenant_admin vendor.
        TenantRepository::addVendor($tenant->id, $ownerId, 'tenant_admin');

        /**
         * Fires after a tenant has been successfully provisioned.
         *
         * @param Tenant $tenant The newly created tenant.
         */
        do_action('poradnik_tenant_provisioned', $tenant);

        EventLogger::dispatch('poradnik_tenant_provisioned', ['tenant_id' => $tenant->id, 'slug' => $tenant->slug]);

        return ['success' => true, 'tenant' => $tenant];
    }

    // ------------------------------------------------------------------
    // Lifecycle management
    // ------------------------------------------------------------------

    /**
     * Activate a pending or suspended tenant.
     */
    public static function activate(int $tenantId): bool
    {
        return self::setStatus($tenantId, 'active');
    }

    /**
     * Suspend an active tenant (read-only mode for its users).
     */
    public static function suspend(int $tenantId): bool
    {
        return self::setStatus($tenantId, 'suspended');
    }

    /**
     * Archive a tenant (soft delete; data is retained).
     */
    public static function archive(int $tenantId): bool
    {
        return self::setStatus($tenantId, 'archived');
    }

    /**
     * Permanently remove a tenant and all its vendor assignments.
     */
    public static function destroy(int $tenantId): bool
    {
        $tenant = TenantRepository::find($tenantId);
        if ($tenant === null) {
            return false;
        }

        $deleted = TenantRepository::delete($tenantId);

        if ($deleted) {
            do_action('poradnik_tenant_destroyed', $tenantId);
            EventLogger::dispatch('poradnik_tenant_destroyed', ['tenant_id' => $tenantId]);
        }

        return $deleted;
    }

    // ------------------------------------------------------------------
    // Plan management
    // ------------------------------------------------------------------

    /**
     * Upgrade or downgrade the SaaS plan for a tenant.
     */
    public static function changePlan(int $tenantId, string $plan): bool
    {
        $plan = sanitize_key($plan);

        if (! in_array($plan, self::PLANS, true)) {
            return false;
        }

        $updated = TenantRepository::update($tenantId, ['plan' => $plan]);

        if ($updated) {
            do_action('poradnik_tenant_plan_changed', $tenantId, $plan);
            EventLogger::dispatch('poradnik_tenant_plan_changed', ['tenant_id' => $tenantId, 'plan' => $plan]);
        }

        return $updated;
    }

    // ------------------------------------------------------------------
    // Vendor management
    // ------------------------------------------------------------------

    /**
     * Add or update a vendor assignment for a tenant.
     */
    public static function addVendor(int $tenantId, int $userId, string $role = 'vendor'): bool
    {
        if (! get_userdata($userId)) {
            return false;
        }

        $added = TenantRepository::addVendor($tenantId, $userId, $role);

        if ($added) {
            do_action('poradnik_tenant_vendor_added', $tenantId, $userId, $role);
            EventLogger::dispatch('poradnik_tenant_vendor_added', ['tenant_id' => $tenantId, 'user_id' => $userId, 'role' => $role]);
        }

        return $added;
    }

    /**
     * Remove a vendor from a tenant.
     */
    public static function removeVendor(int $tenantId, int $userId): bool
    {
        $removed = TenantRepository::removeVendor($tenantId, $userId);

        if ($removed) {
            do_action('poradnik_tenant_vendor_removed', $tenantId, $userId);
            EventLogger::dispatch('poradnik_tenant_vendor_removed', ['tenant_id' => $tenantId, 'user_id' => $userId]);
        }

        return $removed;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return string[]
     */
    public static function allowedStatuses(): array
    {
        return self::STATUSES;
    }

    /**
     * @return string[]
     */
    public static function allowedPlans(): array
    {
        return self::PLANS;
    }

    private static function setStatus(int $tenantId, string $status): bool
    {
        $updated = TenantRepository::update($tenantId, ['status' => $status]);

        if ($updated) {
            do_action('poradnik_tenant_status_changed', $tenantId, $status);
            EventLogger::dispatch('poradnik_tenant_status_changed', ['tenant_id' => $tenantId, 'status' => $status]);
        }

        return $updated;
    }
}
