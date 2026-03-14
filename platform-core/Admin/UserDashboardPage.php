<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Domain\SaasPlans\PlanRepository;

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
        add_menu_page(
            __('My Dashboard', 'poradnik-platform'),
            __('My Dashboard', 'poradnik-platform'),
            'read',
            self::PAGE_SLUG,
            [self::class, 'renderPage'],
            'dashicons-admin-home',
            4
        );
    }

    public static function renderPage(): void
    {
        if (! is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to view this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'profile';
        $userId = get_current_user_id();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('My Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab, $userId);

        echo '</div>';
    }

    private static function renderTabs(string $tab, int $userId): void
    {
        $tabs = [
            'profile'       => __('Profile', 'poradnik-platform'),
            'saved'         => __('Saved Articles', 'poradnik-platform'),
            'comments'      => __('My Comments', 'poradnik-platform'),
            'notifications' => __('Notifications', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('admin.php'));
            $class = $tab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($tab) {
            case 'saved':
                self::renderSavedArticles($userId);
                break;
            case 'comments':
                self::renderMyComments($userId);
                break;
            case 'notifications':
                self::renderNotifications($userId);
                break;
            default:
                self::renderProfile($userId);
        }
    }

    private static function renderProfile(int $userId): void
    {
        $user = get_userdata($userId);
        if (! $user instanceof \WP_User) {
            echo '<p>' . esc_html__('User not found.', 'poradnik-platform') . '</p>';
            return;
        }

        $plan = PlanRepository::getUserPlan($userId);
        $planData = PlanRepository::find($plan);
        $planLabel = is_array($planData) ? (string) ($planData['label'] ?? $plan) : strtoupper($plan);

        echo '<h2>' . esc_html__('Profile', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<tbody>';
        echo '<tr><th>' . esc_html__('Name', 'poradnik-platform') . '</th><td>' . esc_html($user->display_name) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Email', 'poradnik-platform') . '</th><td>' . esc_html($user->user_email) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Username', 'poradnik-platform') . '</th><td>' . esc_html($user->user_login) . '</td></tr>';
        echo '<tr><th>' . esc_html__('SaaS Plan', 'poradnik-platform') . '</th><td>' . esc_html($planLabel) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Member Since', 'poradnik-platform') . '</th><td>' . esc_html($user->user_registered) . '</td></tr>';
        echo '</tbody></table>';

        $profileUrl = admin_url('profile.php');
        echo '<p><a href="' . esc_url($profileUrl) . '" class="button">' . esc_html__('Edit Profile', 'poradnik-platform') . '</a></p>';
    }

    private static function renderSavedArticles(int $userId): void
    {
        $savedIds = get_user_meta($userId, 'poradnik_saved_articles', true);
        if (! is_array($savedIds)) {
            $savedIds = [];
        }

        echo '<h2>' . esc_html__('Saved Articles', 'poradnik-platform') . '</h2>';

        if ($savedIds === []) {
            echo '<p>' . esc_html__('You have no saved articles yet.', 'poradnik-platform') . '</p>';
            return;
        }

        $args = [
            'post__in'       => array_map('absint', $savedIds),
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
        ];

        $query = new \WP_Query($args);

        echo '<table class="widefat striped" style="max-width:1000px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Title', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Type', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Date', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        if (! $query->have_posts()) {
            echo '<tr><td colspan="3">' . esc_html__('No saved articles found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $viewUrl = get_permalink($post->ID);

            echo '<tr>';
            echo '<td><a href="' . esc_url((string) $viewUrl) . '" target="_blank">' . esc_html($post->post_title) . '</a></td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderMyComments(int $userId): void
    {
        $comments = get_comments([
            'user_id' => $userId,
            'number'  => 20,
            'status'  => 'all',
        ]);

        echo '<h2>' . esc_html__('My Comments', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Comment', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Post', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Status', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Date', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        if ($comments === []) {
            echo '<tr><td colspan="4">' . esc_html__('No comments yet.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($comments as $comment) {
            if (! $comment instanceof \WP_Comment) {
                continue;
            }

            $status = $comment->comment_approved === '1' ? __('Approved', 'poradnik-platform') : __('Pending', 'poradnik-platform');
            $postTitle = get_the_title((int) $comment->comment_post_ID);

            echo '<tr>';
            echo '<td>' . esc_html(wp_trim_words((string) $comment->comment_content, 15)) . '</td>';
            echo '<td>' . esc_html((string) $postTitle) . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '<td>' . esc_html((string) $comment->comment_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderNotifications(int $userId): void
    {
        $notifications = get_user_meta($userId, 'poradnik_notifications', true);
        if (! is_array($notifications)) {
            $notifications = [];
        }

        echo '<h2>' . esc_html__('Notifications', 'poradnik-platform') . '</h2>';

        if ($notifications === []) {
            echo '<p>' . esc_html__('No notifications at this time.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<ul>';
        foreach ($notifications as $notification) {
            if (! is_array($notification)) {
                continue;
            }

            $message = isset($notification['message']) ? sanitize_text_field((string) $notification['message']) : '';
            $date = isset($notification['date']) ? sanitize_text_field((string) $notification['date']) : '';

            echo '<li>' . esc_html($message) . ' <small>(' . esc_html($date) . ')</small></li>';
        }
        echo '</ul>';
    }
}
