<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Sponsored\OrderRepository;
use Poradnik\Platform\Domain\Sponsored\Workflow;

if (! defined('ABSPATH')) {
    exit;
}

final class SponsoredOrdersPage
{
    private const PAGE_SLUG = 'poradnik-sponsored-orders';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_poradnik_sponsored_save_order', [self::class, 'handleSave']);
        add_action('admin_post_poradnik_sponsored_transition', [self::class, 'handleTransition']);
    }

    public static function registerPage(): void
    {
        add_submenu_page(
            PlatformAdminPanel::MENU_SLUG,
            __('Poradnik Sponsored Orders', 'poradnik-platform'),
            __('Sponsored Orders', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleSave(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to manage sponsored orders.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_sponsored_save_order');

        $payload = [
            'advertiser_id' => isset($_POST['advertiser_id']) ? absint($_POST['advertiser_id']) : 0,
            'advertiser_email' => isset($_POST['advertiser_email']) ? wp_unslash($_POST['advertiser_email']) : '',
            'title' => isset($_POST['title']) ? wp_unslash($_POST['title']) : '',
            'content' => isset($_POST['content']) ? wp_unslash($_POST['content']) : '',
            'package_key' => isset($_POST['package_key']) ? wp_unslash($_POST['package_key']) : 'basic',
            'amount' => isset($_POST['amount']) ? wp_unslash($_POST['amount']) : '0',
            'currency' => isset($_POST['currency']) ? wp_unslash($_POST['currency']) : 'PLN',
            'desired_publish_at' => isset($_POST['desired_publish_at']) ? wp_unslash($_POST['desired_publish_at']) : '',
        ];

        $orderId = Workflow::submit($payload);

        $redirect = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                $orderId > 0 ? 'updated' : 'error' => '1',
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function handleTransition(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to update sponsored orders.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_sponsored_transition');

        $orderId = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $action = isset($_GET['workflow']) ? sanitize_key((string) $_GET['workflow']) : '';

        $ok = false;
        if ($action === 'review') {
            $ok = Workflow::review($orderId);
        } elseif ($action === 'paid') {
            $ok = Workflow::markPaid($orderId);
        } elseif ($action === 'publish') {
            $ok = Workflow::publish($orderId);
        }

        $redirect = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                $ok ? 'updated' : 'error' => '1',
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $orders = OrderRepository::findAll();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Sponsored Orders', 'poradnik-platform') . '</h1>';

        if (isset($_GET['updated']) && $_GET['updated'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Operation completed.', 'poradnik-platform') . '</p></div>';
        }
        if (isset($_GET['error']) && $_GET['error'] === '1') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Operation failed.', 'poradnik-platform') . '</p></div>';
        }

        self::renderForm();
        self::renderTable($orders);

        echo '</div>';
    }

    private static function renderForm(): void
    {
        echo '<h2>' . esc_html__('Create Sponsored Order', 'poradnik-platform') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="max-width: 900px; margin-bottom: 24px;">';
        wp_nonce_field('poradnik_sponsored_save_order');
        echo '<input type="hidden" name="action" value="poradnik_sponsored_save_order" />';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="sponsored-title">Title</label></th><td><input id="sponsored-title" name="title" type="text" class="large-text" required /></td></tr>';
        echo '<tr><th scope="row"><label for="sponsored-email">Advertiser Email</label></th><td><input id="sponsored-email" name="advertiser_email" type="email" class="regular-text" required /></td></tr>';
        echo '<tr><th scope="row"><label for="sponsored-advertiser-id">Advertiser ID</label></th><td><input id="sponsored-advertiser-id" name="advertiser_id" type="number" min="0" class="small-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="sponsored-package">Package</label></th><td><select id="sponsored-package" name="package_key"><option value="basic">basic</option><option value="featured">featured</option><option value="homepage">homepage</option></select></td></tr>';
        echo '<tr><th scope="row"><label for="sponsored-amount">Amount</label></th><td><input id="sponsored-amount" name="amount" type="number" step="0.01" min="0" class="small-text" value="0" /></td></tr>';
        echo '<tr><th scope="row"><label for="sponsored-currency">Currency</label></th><td><input id="sponsored-currency" name="currency" type="text" class="small-text" value="PLN" /></td></tr>';
        echo '<tr><th scope="row"><label for="sponsored-date">Desired Publish At</label></th><td><input id="sponsored-date" name="desired_publish_at" type="text" class="regular-text" placeholder="YYYY-mm-dd HH:ii:ss" /></td></tr>';
        echo '<tr><th scope="row"><label for="sponsored-content">Content</label></th><td><textarea id="sponsored-content" name="content" rows="8" class="large-text"></textarea></td></tr>';
        echo '</table>';

        submit_button(__('Create Sponsored Order', 'poradnik-platform'));
        echo '</form>';
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     */
    private static function renderTable(array $orders): void
    {
        echo '<h2>' . esc_html__('Order List', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width: 1200px;">';
        echo '<thead><tr><th>ID</th><th>Title</th><th>Package</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead><tbody>';

        if ($orders === []) {
            echo '<tr><td colspan="6">' . esc_html__('No sponsored orders found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($orders as $order) {
            $id = absint($order['id'] ?? 0);
            $reviewUrl = self::transitionUrl($id, 'review');
            $paidUrl = self::transitionUrl($id, 'paid');
            $publishUrl = self::transitionUrl($id, 'publish');

            echo '<tr>';
            echo '<td>' . esc_html((string) $id) . '</td>';
            echo '<td>' . esc_html((string) ($order['title'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($order['package_key'] ?? 'basic')) . '</td>';
            echo '<td>' . esc_html((string) ($order['status'] ?? 'pending')) . '</td>';
            echo '<td>' . esc_html((string) ($order['payment_status'] ?? 'pending')) . '</td>';
            echo '<td><a href="' . esc_url($reviewUrl) . '">Review</a> | <a href="' . esc_url($paidUrl) . '">Mark paid</a> | <a href="' . esc_url($publishUrl) . '">Publish</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function transitionUrl(int $orderId, string $workflow): string
    {
        return wp_nonce_url(
            add_query_arg(
                [
                    'action' => 'poradnik_sponsored_transition',
                    'order_id' => $orderId,
                    'workflow' => $workflow,
                ],
                admin_url('admin-post.php')
            ),
            'poradnik_sponsored_transition'
        );
    }
}
