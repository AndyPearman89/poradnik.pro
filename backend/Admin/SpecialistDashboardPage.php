<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Roles;

if (! defined('ABSPATH')) {
    exit;
}

final class SpecialistDashboardPage
{
    private const PAGE_SLUG = 'poradnik-specialist-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Specialist Dashboard', 'poradnik-platform'),
            __('Specialist Dashboard', 'poradnik-platform'),
            'read',
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Roles::canAccessSpecialistDashboard()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'articles';
        $userId = get_current_user_id();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Specialist Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab, $userId);

        echo '</div>';
    }

    private static function renderTabs(string $tab, int $userId): void
    {
        $tabs = [
            'articles'   => __('My Articles', 'poradnik-platform'),
            'create'     => __('Create Guide', 'poradnik-platform'),
            'reviews'    => __('Reviews', 'poradnik-platform'),
            'rankings'   => __('Rankings', 'poradnik-platform'),
            'statistics' => __('Statistics', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('tools.php'));
            $class = $tab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($tab) {
            case 'create':
                self::renderCreate();
                break;
            case 'reviews':
                self::renderMyContent($userId, 'review', __('My Reviews', 'poradnik-platform'));
                break;
            case 'rankings':
                self::renderMyContent($userId, 'ranking', __('My Rankings', 'poradnik-platform'));
                break;
            case 'statistics':
                self::renderStatistics($userId);
                break;
            default:
                self::renderMyArticles($userId);
        }
    }

    private static function renderMyArticles(int $userId): void
    {
        $args = [
            'post_type'      => ['post', 'guide'],
            'post_status'    => ['publish', 'pending', 'draft'],
            'author'         => $userId,
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new \WP_Query($args);

        echo '<h2>' . esc_html__('My Articles', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Title', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Type', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Status', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Date', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Actions', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        if (! $query->have_posts()) {
            echo '<tr><td colspan="5">' . esc_html__('No articles found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $editUrl = get_edit_post_link($post->ID);
            $viewUrl = get_permalink($post->ID);

            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url((string) $editUrl) . '">' . esc_html__('Edit', 'poradnik-platform') . '</a>';
            if ($post->post_status === 'publish') {
                echo ' | <a href="' . esc_url((string) $viewUrl) . '" target="_blank">' . esc_html__('View', 'poradnik-platform') . '</a>';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        $newUrl = admin_url('post-new.php');
        echo '<p><a href="' . esc_url($newUrl) . '" class="button button-primary">' . esc_html__('Create New Article', 'poradnik-platform') . '</a></p>';
    }

    private static function renderCreate(): void
    {
        $newGuideUrl = add_query_arg('post_type', 'guide', admin_url('post-new.php'));
        $newPostUrl = admin_url('post-new.php');

        echo '<h2>' . esc_html__('Create Guide', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('Create a new guide or article for the platform.', 'poradnik-platform') . '</p>';
        echo '<p>';
        echo '<a href="' . esc_url($newGuideUrl) . '" class="button button-primary" style="margin-right:8px;">' . esc_html__('New Guide', 'poradnik-platform') . '</a>';
        echo '<a href="' . esc_url($newPostUrl) . '" class="button">' . esc_html__('New Post', 'poradnik-platform') . '</a>';
        echo '</p>';
        echo '<h3>' . esc_html__('Content Types', 'poradnik-platform') . '</h3>';
        echo '<ul>';
        foreach ([
            __('Poradniki (guides)', 'poradnik-platform'),
            __('Rankingi (rankings)', 'poradnik-platform'),
            __('Recenzje (reviews)', 'poradnik-platform'),
            __('News', 'poradnik-platform'),
        ] as $type) {
            echo '<li>' . esc_html($type) . '</li>';
        }
        echo '</ul>';
    }

    private static function renderMyContent(int $userId, string $postType, string $heading): void
    {
        $args = [
            'post_type'      => $postType,
            'post_status'    => ['publish', 'pending', 'draft'],
            'author'         => $userId,
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new \WP_Query($args);

        echo '<h2>' . esc_html($heading) . '</h2>';
        echo '<table class="widefat striped" style="max-width:1000px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Title', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Status', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Date', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        if (! $query->have_posts()) {
            echo '<tr><td colspan="3">' . esc_html__('No content found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderStatistics(int $userId): void
    {
        $args = [
            'post_type'      => ['post', 'guide', 'ranking', 'review'],
            'post_status'    => 'publish',
            'author'         => $userId,
            'posts_per_page' => -1,
        ];

        $query = new \WP_Query($args);
        $totalPublished = $query->found_posts;

        $draftArgs = array_merge($args, ['post_status' => 'draft']);
        $draftQuery = new \WP_Query($draftArgs);

        $pendingArgs = array_merge($args, ['post_status' => 'pending']);
        $pendingQuery = new \WP_Query($pendingArgs);

        echo '<h2>' . esc_html__('Article Statistics', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:520px;">';
        echo '<tbody>';
        echo '<tr><th>' . esc_html__('Published', 'poradnik-platform') . '</th><td>' . esc_html((string) $totalPublished) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Drafts', 'poradnik-platform') . '</th><td>' . esc_html((string) $draftQuery->found_posts) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Pending Review', 'poradnik-platform') . '</th><td>' . esc_html((string) $pendingQuery->found_posts) . '</td></tr>';
        echo '</tbody></table>';
    }
}
