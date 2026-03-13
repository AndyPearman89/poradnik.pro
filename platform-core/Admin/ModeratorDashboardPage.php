<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Roles;

if (! defined('ABSPATH')) {
    exit;
}

final class ModeratorDashboardPage
{
    private const PAGE_SLUG = 'poradnik-moderator-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Moderator Dashboard', 'poradnik-platform'),
            __('Moderator Dashboard', 'poradnik-platform'),
            'read',
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Roles::canAccessModeratorDashboard()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'pending';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Moderator Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab);

        echo '</div>';
    }

    private static function renderTabs(string $tab): void
    {
        $tabs = [
            'pending'  => __('Pending Articles', 'poradnik-platform'),
            'comments' => __('Comments Moderation', 'poradnik-platform'),
            'reports'  => __('User Reports', 'poradnik-platform'),
            'warnings' => __('Warnings', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('tools.php'));
            $class = $tab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($tab) {
            case 'comments':
                self::renderComments();
                break;
            case 'reports':
                self::renderReports();
                break;
            case 'warnings':
                self::renderWarnings();
                break;
            default:
                self::renderPendingArticles();
        }
    }

    private static function renderPendingArticles(): void
    {
        $args = [
            'post_type'      => ['post', 'guide', 'ranking', 'review'],
            'post_status'    => 'pending',
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ];

        $query = new \WP_Query($args);

        echo '<h2>' . esc_html__('Pending Articles', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Title', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Type', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Author', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Submitted', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Actions', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        if (! $query->have_posts()) {
            echo '<tr><td colspan="5">' . esc_html__('No pending articles.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $editUrl = get_edit_post_link($post->ID);
            $authorName = get_the_author_meta('display_name', (int) $post->post_author);

            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html((string) $authorName) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url((string) $editUrl) . '">' . esc_html__('Review', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderComments(): void
    {
        $comments = get_comments([
            'status' => 'hold',
            'number' => 30,
        ]);

        echo '<h2>' . esc_html__('Comments Awaiting Moderation', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Author', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Comment', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Post', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Date', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Actions', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        if ($comments === []) {
            echo '<tr><td colspan="5">' . esc_html__('No comments awaiting moderation.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($comments as $comment) {
            if (! $comment instanceof \WP_Comment) {
                continue;
            }

            $editUrl = admin_url('comment.php?action=editcomment&c=' . absint($comment->comment_ID));
            $postTitle = get_the_title((int) $comment->comment_post_ID);

            echo '<tr>';
            echo '<td>' . esc_html((string) $comment->comment_author) . '</td>';
            echo '<td>' . esc_html(wp_trim_words((string) $comment->comment_content, 20)) . '</td>';
            echo '<td>' . esc_html((string) $postTitle) . '</td>';
            echo '<td>' . esc_html((string) $comment->comment_date) . '</td>';
            echo '<td><a href="' . esc_url($editUrl) . '">' . esc_html__('Moderate', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        $moderateUrl = admin_url('edit-comments.php?comment_status=moderated');
        echo '<p><a href="' . esc_url($moderateUrl) . '" class="button">' . esc_html__('Open Comment Manager', 'poradnik-platform') . '</a></p>';
    }

    private static function renderReports(): void
    {
        echo '<h2>' . esc_html__('User Reports', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('User report management is handled via the platform moderation workflow.', 'poradnik-platform') . '</p>';

        $usersUrl = admin_url('users.php');
        echo '<p><a href="' . esc_url($usersUrl) . '" class="button">' . esc_html__('Manage Users', 'poradnik-platform') . '</a></p>';
    }

    private static function renderWarnings(): void
    {
        echo '<h2>' . esc_html__('Warnings', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('Warnings and moderation actions log.', 'poradnik-platform') . '</p>';

        $auditUrl = admin_url('edit.php?post_status=spam');
        echo '<p><a href="' . esc_url($auditUrl) . '" class="button">' . esc_html__('View Spam/Flagged Content', 'poradnik-platform') . '</a></p>';
    }
}
