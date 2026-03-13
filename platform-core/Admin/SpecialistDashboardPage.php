<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;

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
            Capabilities::specialistCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canAccessSpecialistDashboard()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'articles';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Specialist Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab);

        echo '</div>';
    }

    private static function renderTabs(string $tab): void
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
                self::renderCreateTab();
                break;
            case 'reviews':
                self::renderReviewsTab();
                break;
            case 'rankings':
                self::renderRankingsTab();
                break;
            case 'statistics':
                self::renderStatisticsTab();
                break;
            default:
                self::renderArticlesTab();
        }
    }

    private static function renderArticlesTab(): void
    {
        $currentUser = wp_get_current_user();

        $args = [
            'author'         => $currentUser->ID,
            'post_type'      => ['guide', 'ranking', 'review', 'news'],
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $posts = get_posts($args);

        echo '<h3>' . esc_html__('My Articles', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Type', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th></th></tr></thead><tbody>';

        if (empty($posts)) {
            echo '<tr><td colspan="5">' . esc_html__('No articles found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($posts as $post) {
            $editUrl = get_edit_post_link($post->ID, 'raw');
            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url((string) $editUrl) . '">' . esc_html__('Edit', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderCreateTab(): void
    {
        echo '<h3>' . esc_html__('Create New Guide', 'poradnik-platform') . '</h3>';
        echo '<p>' . esc_html__('Select the type of content you want to create:', 'poradnik-platform') . '</p>';

        $contentTypes = [
            'guide'   => __('Poradnik (Guide)', 'poradnik-platform'),
            'ranking' => __('Ranking', 'poradnik-platform'),
            'review'  => __('Review', 'poradnik-platform'),
            'news'    => __('News', 'poradnik-platform'),
        ];

        echo '<ul style="list-style:none; padding:0;">';
        foreach ($contentTypes as $type => $label) {
            $url = admin_url('post-new.php?post_type=' . $type);
            echo '<li style="margin-bottom:8px;"><a class="button button-secondary" href="' . esc_url($url) . '">' . esc_html__('+ Create', 'poradnik-platform') . ' ' . esc_html($label) . '</a></li>';
        }
        echo '</ul>';

        echo '<p style="margin-top:16px;"><a class="button button-primary" href="' . esc_url(admin_url('tools.php?page=poradnik-ai-content')) . '">' . esc_html__('Use AI Content Generator', 'poradnik-platform') . '</a></p>';
    }

    private static function renderReviewsTab(): void
    {
        $currentUser = wp_get_current_user();

        $posts = get_posts([
            'author'         => $currentUser->ID,
            'post_type'      => 'review',
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        echo '<h3>' . esc_html__('My Reviews', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th></th></tr></thead><tbody>';

        if (empty($posts)) {
            echo '<tr><td colspan="4">' . esc_html__('No reviews found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($posts as $post) {
            $editUrl = get_edit_post_link($post->ID, 'raw');
            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url((string) $editUrl) . '">' . esc_html__('Edit', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=review')) . '">' . esc_html__('+ New Review', 'poradnik-platform') . '</a></p>';
    }

    private static function renderRankingsTab(): void
    {
        $currentUser = wp_get_current_user();

        $posts = get_posts([
            'author'         => $currentUser->ID,
            'post_type'      => 'ranking',
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        echo '<h3>' . esc_html__('My Rankings', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th></th></tr></thead><tbody>';

        if (empty($posts)) {
            echo '<tr><td colspan="4">' . esc_html__('No rankings found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($posts as $post) {
            $editUrl = get_edit_post_link($post->ID, 'raw');
            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url((string) $editUrl) . '">' . esc_html__('Edit', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=ranking')) . '">' . esc_html__('+ New Ranking', 'poradnik-platform') . '</a></p>';
    }

    private static function renderStatisticsTab(): void
    {
        $currentUser = wp_get_current_user();

        $published = (int) (new \WP_Query([
            'author'         => $currentUser->ID,
            'post_type'      => ['guide', 'ranking', 'review', 'news'],
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]))->found_posts;

        $draft = (int) (new \WP_Query([
            'author'         => $currentUser->ID,
            'post_type'      => ['guide', 'ranking', 'review', 'news'],
            'post_status'    => 'draft',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]))->found_posts;

        $pending = (int) (new \WP_Query([
            'author'         => $currentUser->ID,
            'post_type'      => ['guide', 'ranking', 'review', 'news'],
            'post_status'    => 'pending',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]))->found_posts;

        $stats = [
            __('Published Articles', 'poradnik-platform') => $published,
            __('Draft Articles', 'poradnik-platform')     => $draft,
            __('Pending Review', 'poradnik-platform')     => $pending,
            __('Total Articles', 'poradnik-platform')     => $published + $draft + $pending,
        ];

        echo '<h3>' . esc_html__('My Statistics', 'poradnik-platform') . '</h3>';
        echo '<table class="widefat striped" style="max-width:480px;">';
        echo '<tbody>';
        foreach ($stats as $label => $value) {
            echo '<tr><th>' . esc_html((string) $label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
