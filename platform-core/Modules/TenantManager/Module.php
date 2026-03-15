<?php

namespace Poradnik\Platform\Modules\TenantManager;

use Poradnik\Platform\Admin\TenantManagementPage;
use Poradnik\Platform\Domain\Tenant\TenantRepository;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * TenantManager module: multi-tenancy and marketplace portal management.
 *
 * Responsibilities:
 *  - Register admin menu entries for tenant CRUD and vendor management.
 *  - Expose WordPress hooks for third-party customisation.
 *  - Integrate tenant context into WP requests (optional multisite routing).
 */
final class Module
{
    private static bool $booted = false;

    public static function init(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        TenantManagementPage::init();
        self::registerHooks();

        do_action('poradnik_tenant_manager_module_booted');
    }

    private static function registerHooks(): void
    {
        // Expose tenant context in REST responses for authenticated users.
        add_filter('rest_prepare_post', [self::class, 'injectTenantContext'], 10, 3);

        // Register a summary endpoint widget for the admin dashboard.
        add_action('wp_dashboard_setup', [self::class, 'registerDashboardWidget']);
    }

    /**
     * Optionally inject the current user's tenant membership into post REST responses.
     *
     * @param \WP_REST_Response $response
     * @param \WP_Post          $post
     * @param \WP_REST_Request  $request
     * @return \WP_REST_Response
     */
    public static function injectTenantContext($response, $post, $request): \WP_REST_Response
    {
        if (! is_user_logged_in()) {
            return $response;
        }

        $userId  = get_current_user_id();
        $tenants = TenantRepository::findByOwner($userId);

        if ($tenants !== []) {
            $data = $response->get_data();
            if (is_array($data)) {
                $data['_tenant_ids'] = array_map(static fn($t) => $t->id, $tenants);
                $response->set_data($data);
            }
        }

        return $response;
    }

    /**
     * Add a summary widget to the WP Admin Dashboard.
     */
    public static function registerDashboardWidget(): void
    {
        wp_add_dashboard_widget(
            'peartree_tenant_summary',
            __('PearTree — Tenant Summary', 'poradnik-platform'),
            [self::class, 'renderDashboardWidget']
        );
    }

    /**
     * Render the dashboard widget content.
     */
    public static function renderDashboardWidget(): void
    {
        $total     = TenantRepository::count();
        $active    = TenantRepository::count('active');
        $pending   = TenantRepository::count('pending');
        $suspended = TenantRepository::count('suspended');

        echo '<ul>';
        echo '<li><strong>' . esc_html__('Total portals:', 'poradnik-platform') . '</strong> ' . esc_html((string) $total) . '</li>';
        echo '<li><strong>' . esc_html__('Active:', 'poradnik-platform') . '</strong> ' . esc_html((string) $active) . '</li>';
        echo '<li><strong>' . esc_html__('Pending:', 'poradnik-platform') . '</strong> ' . esc_html((string) $pending) . '</li>';
        echo '<li><strong>' . esc_html__('Suspended:', 'poradnik-platform') . '</strong> ' . esc_html((string) $suspended) . '</li>';
        echo '</ul>';

        $manageUrl = add_query_arg(['page' => 'peartree-tenants'], admin_url('admin.php'));
        echo '<p><a href="' . esc_url($manageUrl) . '" class="button">' . esc_html__('Manage Tenants', 'poradnik-platform') . '</a></p>';
    }
}
