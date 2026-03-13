<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Specialist Dashboard – expert content creator panel.
 *
 * Sections: My Articles | Create Guide | Reviews | Rankings | Statistics
 */
final class SpecialistDashboardPage
{
    private const PAGE_SLUG = 'peartree-specialist-dashboard';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_menu_page(
            __('Specialist Dashboard', 'poradnik-platform'),
            __('Specialist', 'poradnik-platform'),
            'edit_posts',
            self::PAGE_SLUG,
            [self::class, 'renderPage'],
            'dashicons-edit-large',
            5
        );
    }

    public static function renderPage(): void
    {
        if (! is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to access this page.', 'poradnik-platform'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'articles';
        $currentUser = wp_get_current_user();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Specialist Dashboard', 'poradnik-platform') . '</h1>';

        self::renderTabs($tab, $currentUser);

        echo '</div>';
    }

    private static function renderTabs(string $activeTab, \WP_User $user): void
    {
        $tabs = [
            'articles' => __('My Articles', 'poradnik-platform'),
            'create' => __('Create Guide', 'poradnik-platform'),
            'reviews' => __('Reviews', 'poradnik-platform'),
            'rankings' => __('Rankings', 'poradnik-platform'),
            'statistics' => __('Statistics', 'poradnik-platform'),
        ];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:16px;">';
        foreach ($tabs as $key => $label) {
            $url = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $key], admin_url('admin.php'));
            $class = $activeTab === $key ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        switch ($activeTab) {
            case 'create':
                self::renderCreateGuideTab();
                break;
            case 'reviews':
                self::renderReviewsTab($user);
                break;
            case 'rankings':
                self::renderRankingsTab($user);
                break;
            case 'statistics':
                self::renderStatisticsTab($user);
                break;
            default:
                self::renderArticlesTab($user);
                break;
        }
    }

    private static function renderArticlesTab(\WP_User $user): void
    {
        $cptTypes = ['guide', 'ranking', 'review', 'comparison', 'news', 'tool'];

        $args = [
            'author' => $user->ID,
            'post_type' => $cptTypes,
            'posts_per_page' => 20,
            'post_status' => ['publish', 'draft', 'pending'],
        ];

        $query = new \WP_Query($args);
        $posts = $query->posts;

        if (! is_array($posts) || $posts === []) {
            echo '<p>' . esc_html__('No articles yet. Create your first guide!', 'poradnik-platform') . '</p>';
            echo '<a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=guide')) . '">' . esc_html__('Create Guide', 'poradnik-platform') . '</a>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:1200px;">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Type', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th><th>' . esc_html__('Actions', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) $post->ID) . '</td>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_type) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '<td><a href="' . esc_url(get_edit_post_link($post->ID) ?? '') . '">' . esc_html__('Edit', 'poradnik-platform') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderCreateGuideTab(): void
    {
        $types = [
            'guide' => __('Guide (Poradnik)', 'poradnik-platform'),
            'ranking' => __('Ranking', 'poradnik-platform'),
            'review' => __('Review (Recenzja)', 'poradnik-platform'),
            'comparison' => __('Comparison (Porównanie)', 'poradnik-platform'),
        ];

        echo '<h3>' . esc_html__('Create New Content', 'poradnik-platform') . '</h3>';
        echo '<ul>';
        foreach ($types as $cpt => $label) {
            echo '<li style="margin-bottom:8px;"><a class="button" href="' . esc_url(admin_url('post-new.php?post_type=' . $cpt)) . '">' . esc_html($label) . '</a></li>';
        }
        echo '</ul>';
    }

    private static function renderReviewsTab(\WP_User $user): void
    {
        $args = [
            'author' => $user->ID,
            'post_type' => 'review',
            'posts_per_page' => 20,
            'post_status' => ['publish', 'draft'],
        ];

        $query = new \WP_Query($args);
        $posts = $query->posts;

        if (! is_array($posts) || $posts === []) {
            echo '<p>' . esc_html__('No reviews yet.', 'poradnik-platform') . '</p>';
            echo '<a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=review')) . '">' . esc_html__('Create Review', 'poradnik-platform') . '</a>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:960px;">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) $post->ID) . '</td>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderRankingsTab(\WP_User $user): void
    {
        $args = [
            'author' => $user->ID,
            'post_type' => 'ranking',
            'posts_per_page' => 20,
            'post_status' => ['publish', 'draft'],
        ];

        $query = new \WP_Query($args);
        $posts = $query->posts;

        if (! is_array($posts) || $posts === []) {
            echo '<p>' . esc_html__('No rankings yet.', 'poradnik-platform') . '</p>';
            echo '<a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=ranking')) . '">' . esc_html__('Create Ranking', 'poradnik-platform') . '</a>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:960px;">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Date', 'poradnik-platform') . '</th></tr></thead><tbody>';

        foreach ($posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) $post->ID) . '</td>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($post->post_status) . '</td>';
            echo '<td>' . esc_html($post->post_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderStatisticsTab(\WP_User $user): void
    {
        $args = [
            'author' => $user->ID,
            'post_type' => ['guide', 'ranking', 'review', 'comparison', 'news', 'tool'],
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        $query = new \WP_Query($args);
        $total = $query->found_posts;

        echo '<table class="widefat striped" style="max-width:600px;">';
        echo '<tbody>';
        echo '<tr><th>' . esc_html__('Published Articles', 'poradnik-platform') . '</th><td>' . esc_html((string) $total) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Author ID', 'poradnik-platform') . '</th><td>' . esc_html((string) $user->ID) . '</td></tr>';
        echo '</tbody></table>';
    }
}
