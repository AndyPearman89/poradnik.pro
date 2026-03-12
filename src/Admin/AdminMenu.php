<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

class AdminMenu
{
    private DashboardPage $dashboardPage;
    private SettingsPage $settingsPage;
    private ProductsPage $productsPage;
    private KeywordsPage $keywordsPage;
    private SeoPagesPage $seoPagesPage;
    private StatisticsPage $statisticsPage;
    private ToolsPage $toolsPage;

    public function __construct(
        DashboardPage $dashboardPage,
        SettingsPage $settingsPage,
        ProductsPage $productsPage,
        KeywordsPage $keywordsPage,
        SeoPagesPage $seoPagesPage,
        StatisticsPage $statisticsPage,
        ToolsPage $toolsPage
    ) {
        $this->dashboardPage = $dashboardPage;
        $this->settingsPage = $settingsPage;
        $this->productsPage = $productsPage;
        $this->keywordsPage = $keywordsPage;
        $this->seoPagesPage = $seoPagesPage;
        $this->statisticsPage = $statisticsPage;
        $this->toolsPage = $toolsPage;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('peartree.pro Programmatic', 'peartree-pro-programmatic-affiliate'),
            __('peartree.pro Programmatic', 'peartree-pro-programmatic-affiliate'),
            'manage_options',
            'ppae-dashboard',
            [$this->dashboardPage, 'renderPage'],
            'dashicons-chart-area',
            56
        );

        add_submenu_page('ppae-dashboard', __('Dashboard', 'peartree-pro-programmatic-affiliate'), __('Dashboard', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-dashboard', [$this->dashboardPage, 'renderPage']);
        add_submenu_page('ppae-dashboard', __('AdSense', 'peartree-pro-programmatic-affiliate'), __('AdSense', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-adsense', [$this->settingsPage, 'renderPage']);
        add_submenu_page('ppae-dashboard', __('Produkty afiliacyjne', 'peartree-pro-programmatic-affiliate'), __('Produkty afiliacyjne', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-products', [$this->productsPage, 'renderPage']);
        add_submenu_page('ppae-dashboard', __('Słowa kluczowe autolink', 'peartree-pro-programmatic-affiliate'), __('Słowa kluczowe autolink', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-keywords', [$this->keywordsPage, 'renderPage']);
        add_submenu_page('ppae-dashboard', __('Strony Programmatic SEO', 'peartree-pro-programmatic-affiliate'), __('Strony Programmatic SEO', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-seo-pages', [$this->seoPagesPage, 'renderPage']);
        add_submenu_page('ppae-dashboard', __('Statystyki', 'peartree-pro-programmatic-affiliate'), __('Statystyki', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-statistics', [$this->statisticsPage, 'renderPage']);
        add_submenu_page('ppae-dashboard', __('Ustawienia', 'peartree-pro-programmatic-affiliate'), __('Ustawienia', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-settings', [$this->settingsPage, 'renderPage']);
        add_submenu_page('ppae-dashboard', __('Narzędzia', 'peartree-pro-programmatic-affiliate'), __('Narzędzia', 'peartree-pro-programmatic-affiliate'), 'manage_options', 'ppae-tools', [$this->toolsPage, 'renderPage']);
    }
}

