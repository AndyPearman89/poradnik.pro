<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;

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
            Capabilities::moderatorCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canAccessModeratorDashboard()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'pending';

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
                self::renderCommentsTab();
                break;
            case 'reports':
                self::renderReportsTab();
                break;
            case 'warnings':
                self::renderWarningsTab();
                break;
            default:
                self::renderPendingTab();
        }
    }

    private static function renderPendingTab(): void
    {
        $posts = get_posts([
            'post_type'      => ['guide', 'ranking', 'review', 'news', 'tool'],
            'post_status'    => 'pending',
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ]);

        echo '<h3>' . esc_html__('Pending Articles', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Type', 'poradnik-platform') . '</th><th>' . esc_html__('Author', 'poradnik-platform') . '</th><th>' . esc_html__('Submitted', 'poradnik-platform') . '</th><th>' . esc_html__('Actions', 'poradnik-platform') . '</th></tr></thead><tbody>';

        if (empty($posts)) {
            echo '<tr><td colspan="5">' . esc_html__('No pending articles.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($posts as $post) {
            $editUrl = get_edit_post_link($post->ID, 'raw');
            $author = get_userdata($post->post_author);
            $authorName = $author ? $author->display_name : __('Unknown', 'poradnik-platform');

            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($authorName) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url((string) $editUrl) . '">' . esc_html__('Review', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderCommentsTab(): void
    {
        $comments = get_comments([
            'status'  => 'hold',
            'number'  => 30,
            'orderby' => 'comment_date_gmt',
            'order'   => 'ASC',
        ]);

        echo '<h3>' . esc_html__('Comments Awaiting Moderation', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Author', 'poradnik-platform') . '</th><th>' . esc_html__('Comment', 'poradnik-platform') . '</th><th>' . esc_html__('Post', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th></th></tr></thead><tbody>';

        if (empty($comments)) {
            echo '<tr><td colspan="5">' . esc_html__('No comments awaiting moderation.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($comments as $comment) {
            $post = get_post($comment->comment_post_ID);
            $postTitle = $post ? $post->post_title : '';
            $editUrl = admin_url('comment.php?action=editcomment&c=' . absint($comment->comment_ID));

            echo '<tr>';
            echo '<td>' . esc_html($comment->comment_author) . '</td>';
            echo '<td>' . esc_html(wp_trim_words($comment->comment_content, 15)) . '</td>';
            echo '<td>' . esc_html($postTitle) . '</td>';
            echo '<td>' . esc_html($comment->comment_date) . '</td>';
            echo '<td><a href="' . esc_url($editUrl) . '">' . esc_html__('Review', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('edit-comments.php?comment_status=moderated')) . '">' . esc_html__('All Comments', 'poradnik-platform') . '</a></p>';
    }

    private static function renderReportsTab(): void
    {
        echo '<h3>' . esc_html__('User Reports', 'poradnik-platform') . '</h3>';

        $reports = (array) get_option('poradnik_user_reports', []);

        if ($reports === []) {
            echo '<p>' . esc_html__('No user reports submitted.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Reporter', 'poradnik-platform') . '</th><th>' . esc_html__('Reported User', 'poradnik-platform') . '</th><th>' . esc_html__('Reason', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($reports as $report) {
            if (! is_array($report)) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) ($report['reporter'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($report['reported'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($report['reason'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($report['date'] ?? '')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderWarningsTab(): void
    {
        echo '<h3>' . esc_html__('User Warnings', 'poradnik-platform') . '</h3>';

        $warnings = (array) get_option('poradnik_user_warnings', []);

        if ($warnings === []) {
            echo '<p>' . esc_html__('No warnings issued.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('User', 'poradnik-platform') . '</th><th>' . esc_html__('Reason', 'poradnik-platform') . '</th><th>' . esc_html__('Issued By', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($warnings as $warning) {
            if (! is_array($warning)) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) ($warning['user'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($warning['reason'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($warning['issued_by'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($warning['date'] ?? '')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
