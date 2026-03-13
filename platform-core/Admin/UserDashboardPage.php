<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;

if (! defined('ABSPATH')) {
    exit;
}

final class UserDashboardPage
{
    private const PAGE_SLUG = 'poradnik-user-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_dashboard_page(
            __('My Dashboard', 'poradnik-platform'),
            __('My Dashboard', 'poradnik-platform'),
            Capabilities::userCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canAccessUserDashboard()) {
            wp_die(esc_html__('You must be logged in to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'profile';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('My Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab);

        echo '</div>';
    }

    private static function renderTabs(string $tab): void
    {
        $tabs = [
            'profile'       => __('Profile', 'poradnik-platform'),
            'saved'         => __('Saved Articles', 'poradnik-platform'),
            'comments'      => __('Comments', 'poradnik-platform'),
            'notifications' => __('Notifications', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('index.php'));
            $class = $tab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($tab) {
            case 'saved':
                self::renderSavedTab();
                break;
            case 'comments':
                self::renderCommentsTab();
                break;
            case 'notifications':
                self::renderNotificationsTab();
                break;
            default:
                self::renderProfileTab();
        }
    }

    private static function renderProfileTab(): void
    {
        $user = wp_get_current_user();

        $profileData = [
            __('Username', 'poradnik-platform')    => $user->user_login,
            __('Display Name', 'poradnik-platform') => $user->display_name,
            __('Email', 'poradnik-platform')        => $user->user_email,
            __('Registered', 'poradnik-platform')   => $user->user_registered,
            __('Role', 'poradnik-platform')         => implode(', ', (array) $user->roles),
        ];

        echo '<h3>' . esc_html__('My Profile', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<tbody>';
        foreach ($profileData as $label => $value) {
            echo '<tr><th>' . esc_html((string) $label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('profile.php')) . '">' . esc_html__('Edit Profile', 'poradnik-platform') . '</a></p>';
    }

    private static function renderSavedTab(): void
    {
        $user = wp_get_current_user();
        $savedIds = (array) get_user_meta($user->ID, 'poradnik_saved_articles', true);
        $savedIds = array_filter(array_map('absint', $savedIds));

        echo '<h3>' . esc_html__('Saved Articles', 'poradnik-platform') . '</h3>';

        if ($savedIds === []) {
            echo '<p>' . esc_html__('No saved articles yet.', 'poradnik-platform') . '</p>';
            return;
        }

        $posts = get_posts([
            'post__in'       => $savedIds,
            'post_type'      => ['guide', 'ranking', 'review', 'news', 'tool'],
            'post_status'    => 'publish',
            'posts_per_page' => 20,
        ]);

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Type', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th></th></tr></thead><tbody>';

        if (empty($posts)) {
            echo '<tr><td colspan="4">' . esc_html__('No saved articles found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($posts as $post) {
            $permalink = get_permalink($post->ID);
            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url((string) $permalink) . '" target="_blank">' . esc_html__('Read', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderCommentsTab(): void
    {
        $user = wp_get_current_user();

        $comments = get_comments([
            'user_id' => $user->ID,
            'number'  => 20,
            'orderby' => 'comment_date_gmt',
            'order'   => 'DESC',
        ]);

        echo '<h3>' . esc_html__('My Comments', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Comment', 'poradnik-platform') . '</th><th>' . esc_html__('Post', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        if (empty($comments)) {
            echo '<tr><td colspan="4">' . esc_html__('No comments yet.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($comments as $comment) {
            $post = get_post($comment->comment_post_ID);
            $postTitle = $post ? $post->post_title : '';
            $status = $comment->comment_approved === '1' ? __('Approved', 'poradnik-platform') : __('Pending', 'poradnik-platform');

            echo '<tr>';
            echo '<td>' . esc_html(wp_trim_words($comment->comment_content, 15)) . '</td>';
            echo '<td>' . esc_html($postTitle) . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '<td>' . esc_html($comment->comment_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderNotificationsTab(): void
    {
        $user = wp_get_current_user();
        $notifications = (array) get_user_meta($user->ID, 'poradnik_notifications', true);

        echo '<h3>' . esc_html__('Notifications', 'poradnik-platform') . '</h3>';

        if ($notifications === []) {
            echo '<p>' . esc_html__('No notifications.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Message', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($notifications as $notification) {
            if (! is_array($notification)) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) ($notification['message'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($notification['date'] ?? '')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
