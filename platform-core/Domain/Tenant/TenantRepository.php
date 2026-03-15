<?php

namespace Poradnik\Platform\Domain\Tenant;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Persistent storage layer for marketplace portal tenants.
 */
final class TenantRepository
{
    private static function table(): string
    {
        return Migrator::tableName('tenants');
    }

    private static function vendorsTable(): string
    {
        return Migrator::tableName('tenant_vendors');
    }

    /**
     * Return all tenants, optionally filtered by status.
     *
     * @return Tenant[]
     */
    public static function all(string $status = ''): array
    {
        global $wpdb;

        if ($status !== '') {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM %i WHERE status = %s ORDER BY created_at DESC',
                    self::table(),
                    $status
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare('SELECT * FROM %i ORDER BY created_at DESC', self::table()),
                ARRAY_A
            );
        }

        if (! is_array($rows)) {
            return [];
        }

        return array_map(static fn(array $row) => new Tenant($row), $rows);
    }

    /**
     * Return a paginated slice of tenants.
     *
     * @return Tenant[]
     */
    public static function paginate(int $perPage, int $offset, string $status = ''): array
    {
        global $wpdb;

        if ($status !== '') {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM %i WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d',
                    self::table(),
                    $status,
                    $perPage,
                    $offset
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d',
                    self::table(),
                    $perPage,
                    $offset
                ),
                ARRAY_A
            );
        }

        if (! is_array($rows)) {
            return [];
        }

        return array_map(static fn(array $row) => new Tenant($row), $rows);
    }

    /**
     * Find a single tenant by its primary key.
     */
    public static function find(int $id): ?Tenant
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM %i WHERE id = %d LIMIT 1',
                self::table(),
                $id
            ),
            ARRAY_A
        );

        if (! is_array($row)) {
            return null;
        }

        return new Tenant($row);
    }

    /**
     * Find a tenant by its unique slug.
     */
    public static function findBySlug(string $slug): ?Tenant
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM %i WHERE slug = %s LIMIT 1',
                self::table(),
                $slug
            ),
            ARRAY_A
        );

        if (! is_array($row)) {
            return null;
        }

        return new Tenant($row);
    }

    /**
     * Return all tenants owned by a specific WordPress user.
     *
     * @return Tenant[]
     */
    public static function findByOwner(int $ownerId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM %i WHERE owner_id = %d ORDER BY created_at DESC',
                self::table(),
                $ownerId
            ),
            ARRAY_A
        );

        if (! is_array($rows)) {
            return [];
        }

        return array_map(static fn(array $row) => new Tenant($row), $rows);
    }

    /**
     * Insert a new tenant record and return the created Tenant or null on failure.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): ?Tenant
    {
        global $wpdb;

        $now = current_time('mysql', true);

        $inserted = $wpdb->insert(
            self::table(),
            [
                'slug'       => sanitize_key((string) ($data['slug']     ?? '')),
                'name'       => sanitize_text_field((string) ($data['name']     ?? '')),
                'domain'     => sanitize_text_field((string) ($data['domain']   ?? '')),
                'status'     => sanitize_key((string) ($data['status']   ?? 'pending')),
                'plan'       => sanitize_key((string) ($data['plan']     ?? 'free')),
                'owner_id'   => (int) ($data['owner_id'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($inserted === false || $wpdb->insert_id === 0) {
            return null;
        }

        return self::find((int) $wpdb->insert_id);
    }

    /**
     * Update an existing tenant record.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        global $wpdb;

        $allowed = ['name', 'domain', 'status', 'plan', 'owner_id'];
        $payload = [];
        $formats = [];

        foreach ($allowed as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            if ($field === 'owner_id') {
                $payload[$field] = (int) $data[$field];
                $formats[]       = '%d';
            } else {
                $payload[$field] = sanitize_text_field((string) $data[$field]);
                $formats[]       = '%s';
            }
        }

        if ($payload === []) {
            return false;
        }

        $payload['updated_at'] = current_time('mysql', true);
        $formats[]             = '%s';

        return (bool) $wpdb->update(self::table(), $payload, ['id' => $id], $formats, ['%d']);
    }

    /**
     * Delete a tenant and all its associated vendor assignments.
     */
    public static function delete(int $id): bool
    {
        global $wpdb;

        $wpdb->delete(self::vendorsTable(), ['tenant_id' => $id], ['%d']);

        return (bool) $wpdb->delete(self::table(), ['id' => $id], ['%d']);
    }

    // ------------------------------------------------------------------
    // Vendor management
    // ------------------------------------------------------------------

    /**
     * Assign a vendor (WordPress user) to a tenant with a given role.
     */
    public static function addVendor(int $tenantId, int $userId, string $role = 'vendor'): bool
    {
        global $wpdb;

        $now = current_time('mysql', true);

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT id FROM %i WHERE tenant_id = %d AND user_id = %d LIMIT 1',
                self::vendorsTable(),
                $tenantId,
                $userId
            )
        );

        if ($exists) {
            return (bool) $wpdb->update(
                self::vendorsTable(),
                ['role' => sanitize_key($role), 'updated_at' => $now],
                ['tenant_id' => $tenantId, 'user_id' => $userId],
                ['%s', '%s'],
                ['%d', '%d']
            );
        }

        return (bool) $wpdb->insert(
            self::vendorsTable(),
            [
                'tenant_id'  => $tenantId,
                'user_id'    => $userId,
                'role'       => sanitize_key($role),
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Remove a vendor assignment from a tenant.
     */
    public static function removeVendor(int $tenantId, int $userId): bool
    {
        global $wpdb;

        return (bool) $wpdb->delete(
            self::vendorsTable(),
            ['tenant_id' => $tenantId, 'user_id' => $userId],
            ['%d', '%d']
        );
    }

    /**
     * Return all vendor rows for a tenant.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getVendors(int $tenantId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT tv.*, u.user_email, u.user_login, u.display_name
                 FROM %i tv
                 LEFT JOIN %i u ON u.ID = tv.user_id
                 WHERE tv.tenant_id = %d
                 ORDER BY tv.created_at ASC',
                self::vendorsTable(),
                $wpdb->users,
                $tenantId
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * Total number of registered tenants.
     */
    public static function count(string $status = ''): int
    {
        global $wpdb;

        if ($status !== '') {
            $result = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(*) FROM %i WHERE status = %s',
                    self::table(),
                    $status
                )
            );
        } else {
            $result = $wpdb->get_var(
                $wpdb->prepare('SELECT COUNT(*) FROM %i', self::table())
            );
        }

        return (int) $result;
    }
}
