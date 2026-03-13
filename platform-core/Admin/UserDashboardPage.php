<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * User Dashboard – registered user panel.
 *
 * Sections: Profile | Saved Articles | Comments | Notifications
 */
final class UserDashboardPage
{
    private const PAGE_SLUG = 'peartree-user-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_menu_page(
            __('My Dashboard', 'poradnik-platform'),
            __('My Dashboard', 'poradnik-platform'),
            'read',
            self::PAGE_SLUG,
            [self::class, 'renderPage'],
            'dashicons-id',
            4
        );
    }

    public static function renderPage(): void
    {
        if (! is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'profile';
        $currentUser = wp_get_current_user();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html(sprintf(__('Welcome, %s', 'poradnik-platform'), $currentUser->display_name)) . '</h1>';

        self::renderTabs($tab, $currentUser);

        echo '</div>';
    }

    private static function renderTabs(string $activeTab, \WP_User $user): void
    {
        $tabs = [
            'profile' => __('Profile', 'poradnik-platform'),
            'saved' => __('Saved Articles', 'poradnik-platform'),
            'comments' => __('Comments', 'poradnik-platform'),
            'notifications' => __('Notifications', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('admin.php'));
            $class = $activeTab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($activeTab) {
            case 'saved':
                self::renderSavedArticlesTab($user);
                break;
            case 'comments':
                self::renderCommentsTab($user);
                break;
            case 'notifications':
                self::renderNotificationsTab($user);
                break;
            default:
                self::renderProfileTab($user);
                break;
        }
    }

    private static function renderProfileTab(\WP_User $user): void
    {
        echo '<table class="widefat striped" style="max-width:600px;">';
        echo '<tbody>';
        echo '<tr><th>' . esc_html__('Username', 'poradnik-platform') . '</th><td>' . esc_html($user->user_login) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Display Name', 'poradnik-platform') . '</th><td>' . esc_html($user->display_name) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Email', 'poradnik-platform') . '</th><td>' . esc_html($user->user_email) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Role', 'poradnik-platform') . '</th><td>' . esc_html(implode(', ', (array) $user->roles)) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Member since', 'poradnik-platform') . '</th><td>' . esc_html($user->user_registered) . '</td></tr>';
        echo '</tbody></table>';
        echo '<p><a class="button" href="' . esc_url(admin_url('profile.php')) . '">' . esc_html__('Edit Profile', 'poradnik-platform') . '</a></p>';
    }

    private static function renderSavedArticlesTab(\WP_User $user): void
    {
        $saved = get_user_meta($user->ID, 'peartree_saved_articles', true);
        $savedIds = is_array($saved) ? $saved : [];

        if ($savedIds === []) {
            echo '<p>' . esc_html__('No saved articles yet.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:960px;">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Type', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach (array_map('absint', $savedIds) as $postId) {
            $post = get_post($postId);
            if (! $post instanceof \WP_Post) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) $post->ID) . '</td>';
            echo '<td><a href="' . esc_url(get_permalink($post)) . '">' . esc_html($post->post_title) . '</a></td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderCommentsTab(\WP_User $user): void
    {
        $comments = get_comments([
            'user_id' => $user->ID,
            'number' => 20,
            'status' => 'approve',
        ]);

        if (! is_array($comments) || $comments === []) {
            echo '<p>' . esc_html__('No comments yet.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:960px;">';
        echo '<thead><tr><th>' . esc_html__('Comment', 'poradnik-platform') . '</th><th>' . esc_html__('Post', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($comments as $comment) {
            if (! $comment instanceof \WP_Comment) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html(wp_trim_words((string) $comment->comment_content, 15)) . '</td>';
            echo '<td><a href="' . esc_url((string) get_permalink((int) $comment->comment_post_ID)) . '">' . esc_html(get_the_title((int) $comment->comment_post_ID)) . '</a></td>';
            echo '<td>' . esc_html((string) $comment->comment_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderNotificationsTab(\WP_User $user): void
    {
        $notifications = get_user_meta($user->ID, 'peartree_notifications', true);
        $items = is_array($notifications) ? $notifications : [];

        if ($items === []) {
            echo '<p>' . esc_html__('No notifications.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<ul style="max-width:720px;">';
        foreach ($items as $item) {
            echo '<li>' . esc_html((string) ($item['message'] ?? '')) . ' <em>(' . esc_html((string) ($item['date'] ?? '')) . ')</em></li>';
        }
        echo '</ul>';
    }
}
