<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Tenant\TenantRepository;
use Poradnik\Platform\Domain\Tenant\TenantService;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * WordPress admin page for multi-tenancy and vendor management.
 *
 * Accessible at: WP Admin → PearTree → Tenants
 */
final class TenantManagementPage
{
    private const PAGE_SLUG   = 'peartree-tenants';
    private const PARENT_SLUG = 'peartree-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_peartree_provision_tenant',  [self::class, 'handleProvision']);
        add_action('admin_post_peartree_update_tenant',     [self::class, 'handleUpdate']);
        add_action('admin_post_peartree_destroy_tenant',    [self::class, 'handleDestroy']);
        add_action('admin_post_peartree_activate_tenant',   [self::class, 'handleActivate']);
        add_action('admin_post_peartree_suspend_tenant',    [self::class, 'handleSuspend']);
        add_action('admin_post_peartree_add_vendor',        [self::class, 'handleAddVendor']);
        add_action('admin_post_peartree_remove_vendor',     [self::class, 'handleRemoveVendor']);
    }

    public static function registerPage(): void
    {
        add_submenu_page(
            self::PARENT_SLUG,
            __('Tenant Management', 'poradnik-platform'),
            __('Tenants', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $tab      = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'list';
        $tenantId = isset($_GET['tenant_id']) ? absint($_GET['tenant_id']) : 0;
        $message  = isset($_GET['message']) ? sanitize_key((string) wp_unslash($_GET['message'])) : '';
        $error    = isset($_GET['error']) ? sanitize_text_field((string) wp_unslash($_GET['error'])) : '';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('PearTree — Tenant Management', 'poradnik-platform') . '</h1>';

        if ($message !== '') {
            $messages = [
                'provisioned'    => __('Tenant provisioned successfully.', 'poradnik-platform'),
                'updated'        => __('Tenant updated.', 'poradnik-platform'),
                'deleted'        => __('Tenant deleted.', 'poradnik-platform'),
                'activated'      => __('Tenant activated.', 'poradnik-platform'),
                'suspended'      => __('Tenant suspended.', 'poradnik-platform'),
                'vendor_added'   => __('Vendor added.', 'poradnik-platform'),
                'vendor_removed' => __('Vendor removed.', 'poradnik-platform'),
            ];
            $text = $messages[$message] ?? '';
            if ($text !== '') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($text) . '</p></div>';
            }
        }

        if ($error !== '') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($error)) . '</p></div>';
        }

        $tabs = [
            'list'      => __('All Tenants', 'poradnik-platform'),
            'new'       => __('New Tenant', 'poradnik-platform'),
        ];

        if ($tenantId > 0) {
            $tabs['vendors'] = __('Vendors', 'poradnik-platform');
            $tabs['edit']    = __('Edit', 'poradnik-platform');
        }

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            $url    = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key, 'tenant_id' => $tenantId ?: ''], admin_url('admin.php'));
            $active = $tab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($active) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';

        echo '<div style="margin-top:20px">';

        switch ($tab) {
            case 'new':
                self::renderNewTenantForm();
                break;
            case 'vendors':
                self::renderVendorTab($tenantId);
                break;
            case 'edit':
                self::renderEditTenantForm($tenantId);
                break;
            default:
                self::renderTenantList();
                break;
        }

        echo '</div></div>';
    }

    // ------------------------------------------------------------------
    // Tab renderers
    // ------------------------------------------------------------------

    private static function renderTenantList(): void
    {
        $tenants = TenantRepository::all();
        $total   = TenantRepository::count();
        $active  = TenantRepository::count('active');
        $pending = TenantRepository::count('pending');

        echo '<p><strong>' . esc_html__('Total:', 'poradnik-platform') . '</strong> ' . esc_html((string) $total)
            . ' &nbsp;|&nbsp; <strong>' . esc_html__('Active:', 'poradnik-platform') . '</strong> ' . esc_html((string) $active)
            . ' &nbsp;|&nbsp; <strong>' . esc_html__('Pending:', 'poradnik-platform') . '</strong> ' . esc_html((string) $pending)
            . '</p>';

        if ($tenants === []) {
            echo '<p>' . esc_html__('No tenants found. Use the "New Tenant" tab to provision the first portal.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        foreach ([__('ID', 'poradnik-platform'), __('Name', 'poradnik-platform'), __('Slug', 'poradnik-platform'),
                  __('Domain', 'poradnik-platform'), __('Status', 'poradnik-platform'), __('Plan', 'poradnik-platform'),
                  __('Owner', 'poradnik-platform'), __('Created', 'poradnik-platform'), __('Actions', 'poradnik-platform')] as $h) {
            echo '<th>' . esc_html($h) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($tenants as $tenant) {
            $owner    = get_userdata($tenant->owner_id);
            $ownerStr = $owner ? esc_html($owner->user_login) : esc_html((string) $tenant->owner_id);

            $editUrl    = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'edit', 'tenant_id' => $tenant->id], admin_url('admin.php'));
            $vendorsUrl = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'vendors', 'tenant_id' => $tenant->id], admin_url('admin.php'));

            echo '<tr>';
            echo '<td>' . esc_html((string) $tenant->id) . '</td>';
            echo '<td><a href="' . esc_url($editUrl) . '">' . esc_html($tenant->name) . '</a></td>';
            echo '<td><code>' . esc_html($tenant->slug) . '</code></td>';
            echo '<td>' . esc_html($tenant->domain) . '</td>';
            echo '<td>' . esc_html($tenant->status) . '</td>';
            echo '<td>' . esc_html($tenant->plan) . '</td>';
            echo '<td>' . $ownerStr . '</td>';
            echo '<td>' . esc_html($tenant->created_at) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url($vendorsUrl) . '" class="button button-small">' . esc_html__('Vendors', 'poradnik-platform') . '</a> ';

            if ($tenant->status !== 'active') {
                $activateUrl = wp_nonce_url(
                    admin_url('admin-post.php?action=peartree_activate_tenant&tenant_id=' . $tenant->id),
                    'peartree_activate_tenant_' . $tenant->id
                );
                echo '<a href="' . esc_url($activateUrl) . '" class="button button-small">' . esc_html__('Activate', 'poradnik-platform') . '</a> ';
            }

            if ($tenant->status === 'active') {
                $suspendUrl = wp_nonce_url(
                    admin_url('admin-post.php?action=peartree_suspend_tenant&tenant_id=' . $tenant->id),
                    'peartree_suspend_tenant_' . $tenant->id
                );
                echo '<a href="' . esc_url($suspendUrl) . '" class="button button-small">' . esc_html__('Suspend', 'poradnik-platform') . '</a> ';
            }

            $deleteUrl = wp_nonce_url(
                admin_url('admin-post.php?action=peartree_destroy_tenant&tenant_id=' . $tenant->id),
                'peartree_destroy_tenant_' . $tenant->id
            );
            echo '<a href="' . esc_url($deleteUrl) . '" class="button button-small button-link-delete" onclick="return confirm(\'' . esc_attr__('Delete this tenant and all its data?', 'poradnik-platform') . '\')">' . esc_html__('Delete', 'poradnik-platform') . '</a>';

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderNewTenantForm(): void
    {
        $actionUrl = admin_url('admin-post.php');

        echo '<form method="post" action="' . esc_url($actionUrl) . '">';
        echo '<input type="hidden" name="action" value="peartree_provision_tenant">';
        wp_nonce_field('peartree_provision_tenant');

        echo '<table class="form-table"><tbody>';

        self::formRow(__('Name', 'poradnik-platform'), '<input type="text" name="name" class="regular-text" required>');
        self::formRow(__('Slug', 'poradnik-platform'), '<input type="text" name="slug" class="regular-text" required placeholder="my-portal">');
        self::formRow(__('Domain', 'poradnik-platform'), '<input type="text" name="domain" class="regular-text" required placeholder="portal.example.com">');
        self::formRow(__('Owner (user ID)', 'poradnik-platform'), '<input type="number" name="owner_id" class="small-text" min="1" required>');

        $planOptions = '<select name="plan">';
        foreach (TenantService::allowedPlans() as $plan) {
            $planOptions .= '<option value="' . esc_attr($plan) . '">' . esc_html(strtoupper($plan)) . '</option>';
        }
        $planOptions .= '</select>';
        self::formRow(__('Plan', 'poradnik-platform'), $planOptions);

        $statusOptions = '<select name="status">';
        foreach (TenantService::allowedStatuses() as $status) {
            $statusOptions .= '<option value="' . esc_attr($status) . '">' . esc_html(ucfirst($status)) . '</option>';
        }
        $statusOptions .= '</select>';
        self::formRow(__('Initial Status', 'poradnik-platform'), $statusOptions);

        echo '</tbody></table>';
        echo '<p class="submit"><input type="submit" class="button button-primary" value="' . esc_attr__('Provision Tenant', 'poradnik-platform') . '"></p>';
        echo '</form>';
    }

    private static function renderEditTenantForm(int $tenantId): void
    {
        $tenant = TenantRepository::find($tenantId);
        if ($tenant === null) {
            echo '<p>' . esc_html__('Tenant not found.', 'poradnik-platform') . '</p>';
            return;
        }

        $actionUrl = admin_url('admin-post.php');

        echo '<form method="post" action="' . esc_url($actionUrl) . '">';
        echo '<input type="hidden" name="action" value="peartree_update_tenant">';
        echo '<input type="hidden" name="tenant_id" value="' . esc_attr((string) $tenantId) . '">';
        wp_nonce_field('peartree_update_tenant_' . $tenantId);

        echo '<table class="form-table"><tbody>';

        self::formRow(__('Name', 'poradnik-platform'), '<input type="text" name="name" class="regular-text" value="' . esc_attr($tenant->name) . '" required>');
        self::formRow(__('Domain', 'poradnik-platform'), '<input type="text" name="domain" class="regular-text" value="' . esc_attr($tenant->domain) . '" required>');

        $planOptions = '<select name="plan">';
        foreach (TenantService::allowedPlans() as $plan) {
            $sel = $tenant->plan === $plan ? ' selected' : '';
            $planOptions .= '<option value="' . esc_attr($plan) . '"' . $sel . '>' . esc_html(strtoupper($plan)) . '</option>';
        }
        $planOptions .= '</select>';
        self::formRow(__('Plan', 'poradnik-platform'), $planOptions);

        $statusOptions = '<select name="status">';
        foreach (TenantService::allowedStatuses() as $status) {
            $sel = $tenant->status === $status ? ' selected' : '';
            $statusOptions .= '<option value="' . esc_attr($status) . '"' . $sel . '>' . esc_html(ucfirst($status)) . '</option>';
        }
        $statusOptions .= '</select>';
        self::formRow(__('Status', 'poradnik-platform'), $statusOptions);

        echo '</tbody></table>';
        echo '<p class="submit"><input type="submit" class="button button-primary" value="' . esc_attr__('Update Tenant', 'poradnik-platform') . '"></p>';
        echo '</form>';
    }

    private static function renderVendorTab(int $tenantId): void
    {
        $tenant = TenantRepository::find($tenantId);
        if ($tenant === null) {
            echo '<p>' . esc_html__('Tenant not found.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<h2>' . esc_html(sprintf(__('Vendors for: %s', 'poradnik-platform'), $tenant->name)) . '</h2>';

        $vendors = TenantRepository::getVendors($tenantId);

        if ($vendors === []) {
            echo '<p>' . esc_html__('No vendors assigned yet.', 'poradnik-platform') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            foreach ([__('User ID', 'poradnik-platform'), __('Login / Email', 'poradnik-platform'), __('Role', 'poradnik-platform'), __('Status', 'poradnik-platform'), __('Since', 'poradnik-platform'), __('Actions', 'poradnik-platform')] as $h) {
                echo '<th>' . esc_html($h) . '</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($vendors as $vendor) {
                $removeUrl = wp_nonce_url(
                    admin_url('admin-post.php?action=peartree_remove_vendor&tenant_id=' . $tenantId . '&user_id=' . absint($vendor['user_id'] ?? 0)),
                    'peartree_remove_vendor_' . $tenantId . '_' . absint($vendor['user_id'] ?? 0)
                );

                echo '<tr>';
                echo '<td>' . esc_html((string) ($vendor['user_id'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($vendor['user_login'] ?? $vendor['user_email'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($vendor['role'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($vendor['status'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($vendor['created_at'] ?? '')) . '</td>';
                echo '<td><a href="' . esc_url($removeUrl) . '" class="button button-small button-link-delete" onclick="return confirm(\'' . esc_attr__('Remove this vendor?', 'poradnik-platform') . '\')">' . esc_html__('Remove', 'poradnik-platform') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<h3>' . esc_html__('Add Vendor', 'poradnik-platform') . '</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="peartree_add_vendor">';
        echo '<input type="hidden" name="tenant_id" value="' . esc_attr((string) $tenantId) . '">';
        wp_nonce_field('peartree_add_vendor_' . $tenantId);

        echo '<table class="form-table"><tbody>';
        self::formRow(__('User ID', 'poradnik-platform'), '<input type="number" name="user_id" class="small-text" min="1" required>');

        $roleOptions = '<select name="role">';
        foreach (['vendor', 'tenant_admin', 'moderator'] as $role) {
            $roleOptions .= '<option value="' . esc_attr($role) . '">' . esc_html(ucfirst(str_replace('_', ' ', $role))) . '</option>';
        }
        $roleOptions .= '</select>';
        self::formRow(__('Role', 'poradnik-platform'), $roleOptions);

        echo '</tbody></table>';
        echo '<p class="submit"><input type="submit" class="button button-primary" value="' . esc_attr__('Add Vendor', 'poradnik-platform') . '"></p>';
        echo '</form>';
    }

    // ------------------------------------------------------------------
    // Form handlers (admin-post)
    // ------------------------------------------------------------------

    public static function handleProvision(): void
    {
        check_admin_referer('peartree_provision_tenant');

        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('Permission denied.', 'poradnik-platform'));
        }

        $result = TenantService::provision([
            'name'     => sanitize_text_field((string) wp_unslash($_POST['name']     ?? '')),
            'slug'     => sanitize_key((string) wp_unslash($_POST['slug']     ?? '')),
            'domain'   => sanitize_text_field((string) wp_unslash($_POST['domain']   ?? '')),
            'owner_id' => absint($_POST['owner_id'] ?? 0),
            'plan'     => sanitize_key((string) wp_unslash($_POST['plan']     ?? 'free')),
            'status'   => sanitize_key((string) wp_unslash($_POST['status']   ?? 'pending')),
        ]);

        if (! $result['success']) {
            wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'new', 'error' => rawurlencode($result['error'] ?? '')], admin_url('admin.php')));
            exit;
        }

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'message' => 'provisioned'], admin_url('admin.php')));
        exit;
    }

    public static function handleUpdate(): void
    {
        $tenantId = absint($_POST['tenant_id'] ?? 0);
        check_admin_referer('peartree_update_tenant_' . $tenantId);

        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('Permission denied.', 'poradnik-platform'));
        }

        TenantRepository::update($tenantId, [
            'name'   => sanitize_text_field((string) wp_unslash($_POST['name']   ?? '')),
            'domain' => sanitize_text_field((string) wp_unslash($_POST['domain'] ?? '')),
            'plan'   => sanitize_key((string) wp_unslash($_POST['plan']   ?? '')),
            'status' => sanitize_key((string) wp_unslash($_POST['status'] ?? '')),
        ]);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'list', 'message' => 'updated'], admin_url('admin.php')));
        exit;
    }

    public static function handleDestroy(): void
    {
        $tenantId = absint($_GET['tenant_id'] ?? 0);
        check_admin_referer('peartree_destroy_tenant_' . $tenantId);

        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('Permission denied.', 'poradnik-platform'));
        }

        TenantService::destroy($tenantId);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'message' => 'deleted'], admin_url('admin.php')));
        exit;
    }

    public static function handleActivate(): void
    {
        $tenantId = absint($_GET['tenant_id'] ?? 0);
        check_admin_referer('peartree_activate_tenant_' . $tenantId);

        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('Permission denied.', 'poradnik-platform'));
        }

        TenantService::activate($tenantId);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'message' => 'activated'], admin_url('admin.php')));
        exit;
    }

    public static function handleSuspend(): void
    {
        $tenantId = absint($_GET['tenant_id'] ?? 0);
        check_admin_referer('peartree_suspend_tenant_' . $tenantId);

        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('Permission denied.', 'poradnik-platform'));
        }

        TenantService::suspend($tenantId);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'message' => 'suspended'], admin_url('admin.php')));
        exit;
    }

    public static function handleAddVendor(): void
    {
        $tenantId = absint($_POST['tenant_id'] ?? 0);
        check_admin_referer('peartree_add_vendor_' . $tenantId);

        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('Permission denied.', 'poradnik-platform'));
        }

        $userId = absint($_POST['user_id'] ?? 0);
        $role   = sanitize_key((string) wp_unslash($_POST['role'] ?? 'vendor'));

        TenantService::addVendor($tenantId, $userId, $role);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'vendors', 'tenant_id' => $tenantId, 'message' => 'vendor_added'], admin_url('admin.php')));
        exit;
    }

    public static function handleRemoveVendor(): void
    {
        $tenantId = absint($_GET['tenant_id'] ?? 0);
        $userId   = absint($_GET['user_id']   ?? 0);
        check_admin_referer('peartree_remove_vendor_' . $tenantId . '_' . $userId);

        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('Permission denied.', 'poradnik-platform'));
        }

        TenantService::removeVendor($tenantId, $userId);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'vendors', 'tenant_id' => $tenantId, 'message' => 'vendor_removed'], admin_url('admin.php')));
        exit;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private static function formRow(string $label, string $fieldHtml): void
    {
        echo '<tr>';
        echo '<th scope="row"><label>' . esc_html($label) . '</label></th>';
        echo '<td>' . $fieldHtml . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</tr>';
    }
}
