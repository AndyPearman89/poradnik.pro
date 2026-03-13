<?php

namespace Poradnik\Platform\Modules\UserDashboard;

use Poradnik\Platform\Core\RoleManager;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * User Dashboard module.
 *
 * Provides:
 * - Frontend /dashboard/ page with role-based redirect.
 * - REST API endpoints for user favorites (registered via RestKernel/UserController).
 */
final class Module
{
    private const DASHBOARD_SLUG = 'dashboard';

    public static function init(): void
    {
        add_action('init', [self::class, 'registerRewriteRule']);
        add_filter('query_vars', [self::class, 'addQueryVars']);
        add_action('template_redirect', [self::class, 'handleDashboardRequest']);
    }

    public static function registerRewriteRule(): void
    {
        add_rewrite_rule(
            '^' . self::DASHBOARD_SLUG . '/?$',
            'index.php?poradnik_dashboard=1',
            'top'
        );
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public static function addQueryVars(array $vars): array
    {
        $vars[] = 'poradnik_dashboard';
        return $vars;
    }

    public static function handleDashboardRequest(): void
    {
        if (! get_query_var('poradnik_dashboard')) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(home_url('/' . self::DASHBOARD_SLUG . '/')));
            exit;
        }

        self::renderDashboard();
        exit;
    }

    private static function renderDashboard(): void
    {
        $user = wp_get_current_user();
        $displayName = esc_html($user->display_name ?: $user->user_login);

        // Determine active section.
        $section = isset($_GET['section']) ? sanitize_key((string) wp_unslash($_GET['section'])) : 'overview';

        // Allowed sections.
        $sections = ['overview', 'favorites', 'subscriptions', 'settings'];
        if (! in_array($section, $sections, true)) {
            $section = 'overview';
        }

        // Check if user has specialist/advertiser role for additional links.
        $isSpecialist = RoleManager::currentUserCan('manage_own_specialist_profile');
        $isAdvertiser = RoleManager::currentUserCan('manage_campaigns');

        get_header();

        echo '<div class="poradnik-dashboard wrap" style="max-width:1200px;margin:40px auto;padding:0 20px;">';
        echo '<h1 style="margin-bottom:24px;">' . esc_html__('Mój Panel', 'poradnik-platform') . ' – ' . $displayName . '</h1>';

        // Navigation.
        echo '<nav class="poradnik-dashboard-nav" style="margin-bottom:32px;">';
        foreach ($sections as $s) {
            $label = match ($s) {
                'overview'      => __('Przegląd', 'poradnik-platform'),
                'favorites'     => __('Ulubione', 'poradnik-platform'),
                'subscriptions' => __('Subskrypcje', 'poradnik-platform'),
                'settings'      => __('Ustawienia', 'poradnik-platform'),
                default         => $s,
            };
            $url    = esc_url(home_url('/' . self::DASHBOARD_SLUG . '/?section=' . $s));
            $active = $section === $s ? ' style="font-weight:bold;text-decoration:underline;"' : '';
            echo '<a href="' . $url . '"' . $active . ' style="margin-right:16px;">' . esc_html($label) . '</a>';
        }

        if ($isSpecialist) {
            echo '<a href="' . esc_url(home_url('/dashboard/specialist/')) . '" style="margin-right:16px;">' . esc_html__('Panel Specjalisty', 'poradnik-platform') . '</a>';
        }

        if ($isAdvertiser) {
            echo '<a href="' . esc_url(home_url('/dashboard/advertiser/')) . '" style="margin-right:16px;">' . esc_html__('Panel Reklamodawcy', 'poradnik-platform') . '</a>';
        }

        echo '</nav>';

        // Section content.
        match ($section) {
            'overview'      => self::renderOverview($displayName),
            'favorites'     => self::renderFavorites(),
            'subscriptions' => self::renderSubscriptions(),
            'settings'      => self::renderSettings($user),
            default         => self::renderOverview($displayName),
        };

        echo '</div>';

        get_footer();
    }

    private static function renderOverview(string $displayName): void
    {
        echo '<section>';
        echo '<h2>' . esc_html__('Witaj', 'poradnik-platform') . ', ' . $displayName . '!</h2>';
        echo '<p>' . esc_html__('To jest Twój osobisty panel na PORADNIK.PRO.', 'poradnik-platform') . '</p>';
        echo '<ul>';
        echo '<li><a href="' . esc_url(home_url('/dashboard/?section=favorites')) . '">' . esc_html__('Twoje ulubione artykuły', 'poradnik-platform') . '</a></li>';
        echo '<li><a href="' . esc_url(home_url('/dashboard/?section=subscriptions')) . '">' . esc_html__('Zarządzaj subskrypcjami', 'poradnik-platform') . '</a></li>';
        echo '<li><a href="' . esc_url(home_url('/dashboard/?section=settings')) . '">' . esc_html__('Ustawienia konta', 'poradnik-platform') . '</a></li>';
        echo '</ul>';
        echo '</section>';
    }

    private static function renderFavorites(): void
    {
        echo '<section>';
        echo '<h2>' . esc_html__('Ulubione artykuły', 'poradnik-platform') . '</h2>';
        echo '<p id="poradnik-favorites-loading">' . esc_html__('Ładowanie...', 'poradnik-platform') . '</p>';
        echo '<ul id="poradnik-favorites-list"></ul>';
        echo '</section>';
        echo '<script>
(function(){
    var nonce = ' . wp_json_encode(wp_create_nonce('wp_rest')) . ';
    fetch("' . esc_js(rest_url('poradnik/v1/user/favorites')) . '", {
        headers: { "X-WP-Nonce": nonce }
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        var list = document.getElementById("poradnik-favorites-list");
        var loading = document.getElementById("poradnik-favorites-loading");
        if(loading) loading.remove();
        var items = data.items || [];
        if(items.length === 0){
            list.innerHTML = "<li>' . esc_js(__('Brak ulubionych artykułów.', 'poradnik-platform')) . '</li>";
            return;
        }
        items.forEach(function(item){
            var li = document.createElement("li");
            li.innerHTML = "<a href=\"' . esc_js(home_url('/')) . '?p=" + item.post_id + "\">Post #" + item.post_id + "</a>";
            list.appendChild(li);
        });
    })
    .catch(function(){
        document.getElementById("poradnik-favorites-loading").textContent = "' . esc_js(__('Błąd ładowania.', 'poradnik-platform')) . '";
    });
}());
</script>';
    }

    private static function renderSubscriptions(): void
    {
        echo '<section>';
        echo '<h2>' . esc_html__('Subskrypcje tematyczne', 'poradnik-platform') . '</h2>';
        echo '<p>' . esc_html__('Funkcja subskrypcji per temat będzie dostępna wkrótce.', 'poradnik-platform') . '</p>';
        echo '</section>';
    }

    private static function renderSettings(?\WP_User $user): void
    {
        if ($user === null) {
            return;
        }

        echo '<section>';
        echo '<h2>' . esc_html__('Ustawienia konta', 'poradnik-platform') . '</h2>';
        echo '<table class="form-table" style="max-width:600px;">';
        echo '<tr><th>' . esc_html__('Login:', 'poradnik-platform') . '</th><td>' . esc_html($user->user_login) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Email:', 'poradnik-platform') . '</th><td>' . esc_html($user->user_email) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Zarejestrowany:', 'poradnik-platform') . '</th><td>' . esc_html($user->user_registered) . '</td></tr>';
        echo '</table>';
        echo '<p><a href="' . esc_url(wp_lostpassword_url()) . '">' . esc_html__('Zmień hasło', 'poradnik-platform') . '</a></p>';
        echo '</section>';
    }
}
