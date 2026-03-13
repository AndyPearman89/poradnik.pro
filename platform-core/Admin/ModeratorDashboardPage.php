<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Moderator Dashboard – content moderation panel.
 *
 * Sections: Pending Articles | Comments Moderation | User Reports | Warnings
 */
final class ModeratorDashboardPage
{
    private const PAGE_SLUG = 'peartree-moderator-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_menu_page(
            __('Moderator Dashboard', 'poradnik-platform'),
            __('Moderator', 'poradnik-platform'),
            'moderate_comments',
            self::PAGE_SLUG,
            [self::class, 'renderPage'],
            'dashicons-flag',
            6
        );
    }

    public static function renderPage(): void
    {
        if (! current_user_can('moderate_comments') && ! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'pending';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Moderator Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab);

        echo '</div>';
    }

    private static function renderTabs(string $activeTab): void
    {
        $tabs = [
            'pending' => __('Pending Articles', 'poradnik-platform'),
            'comments' => __('Comments Moderation', 'poradnik-platform'),
            'reports' => __('User Reports', 'poradnik-platform'),
            'warnings' => __('Warnings', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('admin.php'));
            $class = $activeTab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($activeTab) {
            case 'comments':
                self::renderCommentsTab();
                break;
            case 'reports':
                self::renderReportsTab();
                break;
            case 'warnings':
                self::renderWarningsTab();
                break;
            default:
                self::renderPendingArticlesTab();
                break;
        }
    }

    private static function renderPendingArticlesTab(): void
    {
        $args = [
            'post_type' => ['guide', 'ranking', 'review', 'comparison', 'news', 'tool', 'sponsored'],
            'post_status' => 'pending',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new \WP_Query($args);
        $posts = $query->posts;

        if (! is_array($posts) || $posts === []) {
            echo '<p>' . esc_html__('No pending articles.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Type', 'poradnik-platform') . '</th><th>' . esc_html__('Author', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th>' . esc_html__('Actions', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }
            $author = get_userdata($post->post_author);
            $authorName = $author instanceof \WP_User ? $author->display_name : '-';

            echo '<tr>';
            echo '<td>' . esc_html((string) $post->ID) . '</td>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($authorName) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url(get_edit_post_link($post->ID) ?? '') . '">' . esc_html__('Review', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderCommentsTab(): void
    {
        $comments = get_comments([
            'status' => 'hold',
            'number' => 20,
        ]);

        if (! is_array($comments) || $comments === []) {
            echo '<p>' . esc_html__('No comments pending moderation.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr><th>' . esc_html__('Author', 'poradnik-platform') . '</th><th>' . esc_html__('Comment', 'poradnik-platform') . '</th><th>' . esc_html__('Post', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th>' . esc_html__('Actions', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($comments as $comment) {
            if (! $comment instanceof \WP_Comment) {
                continue;
            }

            $editUrl = add_query_arg(
                ['action' => 'editcomment', 'c' => absint($comment->comment_ID)],
                admin_url('comment.php')
            );

            echo '<tr>';
            echo '<td>' . esc_html((string) $comment->comment_author) . '</td>';
            echo '<td>' . esc_html(wp_trim_words((string) $comment->comment_content, 15)) . '</td>';
            echo '<td><a href="' . esc_url((string) get_permalink((int) $comment->comment_post_ID)) . '">' . esc_html(get_the_title((int) $comment->comment_post_ID)) . '</a></td>';
            echo '<td>' . esc_html((string) $comment->comment_date) . '</td>';
            echo '<td><a href="' . esc_url($editUrl) . '">' . esc_html__('Edit', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderReportsTab(): void
    {
        $reports = get_option('peartree_user_reports', []);
        $items = is_array($reports) ? $reports : [];

        if ($items === []) {
            echo '<p>' . esc_html__('No user reports.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:960px;">';
        echo '<thead><tr><th>' . esc_html__('Reporter', 'poradnik-platform') . '</th><th>' . esc_html__('Target', 'poradnik-platform') . '</th><th>' . esc_html__('Reason', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($items as $item) {
            echo '<tr>';
            echo '<td>' . esc_html((string) ($item['reporter'] ?? '-')) . '</td>';
            echo '<td>' . esc_html((string) ($item['target'] ?? '-')) . '</td>';
            echo '<td>' . esc_html((string) ($item['reason'] ?? '-')) . '</td>';
            echo '<td>' . esc_html((string) ($item['date'] ?? '-')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderWarningsTab(): void
    {
        $warnings = get_option('peartree_user_warnings', []);
        $items = is_array($warnings) ? $warnings : [];

        if ($items === []) {
            echo '<p>' . esc_html__('No warnings issued.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:960px;">';
        echo '<thead><tr><th>' . esc_html__('User', 'poradnik-platform') . '</th><th>' . esc_html__('Reason', 'poradnik-platform') . '</th><th>' . esc_html__('Issued by', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($items as $item) {
            echo '<tr>';
            echo '<td>' . esc_html((string) ($item['user'] ?? '-')) . '</td>';
            echo '<td>' . esc_html((string) ($item['reason'] ?? '-')) . '</td>';
            echo '<td>' . esc_html((string) ($item['issued_by'] ?? '-')) . '</td>';
            echo '<td>' . esc_html((string) ($item['date'] ?? '-')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
