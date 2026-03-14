<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Ads\CampaignRepository;
use Poradnik\Platform\Domain\Ads\SlotRepository;

if (! defined('ABSPATH')) {
    exit;
}

final class AdsCampaignsPage
{
    private const PAGE_SLUG = 'poradnik-ads-campaigns';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_poradnik_ads_save_campaign', [self::class, 'handleSave']);
        add_action('admin_post_poradnik_ads_delete_campaign', [self::class, 'handleDelete']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Ad Campaigns', 'poradnik-platform'),
            __('Ad Campaigns', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleSave(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to manage ad campaigns.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_ads_save_campaign');

        $campaignId = isset($_POST['campaign_id']) ? absint(wp_unslash($_POST['campaign_id'])) : 0;
        $allowedCampaignStatuses = ['active', 'paused', 'draft'];
        $rawStatus = isset($_POST['status']) ? sanitize_key((string) wp_unslash($_POST['status'])) : 'draft';
        $data = [
            'name' => isset($_POST['name']) ? sanitize_text_field((string) wp_unslash($_POST['name'])) : '',
            'advertiser_id' => isset($_POST['advertiser_id']) ? absint(wp_unslash($_POST['advertiser_id'])) : 0,
            'slot_id' => isset($_POST['slot_id']) ? absint(wp_unslash($_POST['slot_id'])) : 0,
            'status' => in_array($rawStatus, $allowedCampaignStatuses, true) ? $rawStatus : 'draft',
            'start_date' => isset($_POST['start_date']) ? sanitize_text_field((string) wp_unslash($_POST['start_date'])) : '',
            'end_date' => isset($_POST['end_date']) ? sanitize_text_field((string) wp_unslash($_POST['end_date'])) : '',
            'budget' => isset($_POST['budget']) ? (string) abs((float) wp_unslash($_POST['budget'])) : '0',
            'destination_url' => isset($_POST['destination_url']) ? esc_url_raw((string) wp_unslash($_POST['destination_url'])) : '',
            'creative_text' => isset($_POST['creative_text']) ? sanitize_text_field((string) wp_unslash($_POST['creative_text'])) : '',
        ];

        $savedId = CampaignRepository::save($data, $campaignId);

        $redirect = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                $savedId > 0 ? 'updated' : 'error' => '1',
                'campaign_id' => $savedId > 0 ? $savedId : $campaignId,
            ],
            admin_url('tools.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function handleDelete(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to delete ad campaigns.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_ads_delete_campaign');

        $campaignId = isset($_GET['campaign_id']) ? absint(wp_unslash($_GET['campaign_id'])) : 0;
        CampaignRepository::delete($campaignId);

        $redirect = add_query_arg(['page' => self::PAGE_SLUG, 'deleted' => '1'], admin_url('tools.php'));

        wp_safe_redirect($redirect);
        exit;
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $editingId = isset($_GET['campaign_id']) ? absint(wp_unslash($_GET['campaign_id'])) : 0;
        $editingCampaign = $editingId > 0 ? CampaignRepository::findById($editingId) : null;
        $campaigns = CampaignRepository::findAll();
        $slots = SlotRepository::findAll();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Ad Campaigns', 'poradnik-platform') . '</h1>';

        if (isset($_GET['updated']) && (string) wp_unslash($_GET['updated']) === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ad campaign saved.', 'poradnik-platform') . '</p></div>';
        }
        if (isset($_GET['deleted']) && (string) wp_unslash($_GET['deleted']) === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ad campaign deleted.', 'poradnik-platform') . '</p></div>';
        }
        if (isset($_GET['error']) && (string) wp_unslash($_GET['error']) === '1') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Ad campaign could not be saved.', 'poradnik-platform') . '</p></div>';
        }

        self::renderForm($editingCampaign, $slots);
        self::renderTable($campaigns);

        echo '</div>';
    }

    /**
     * @param array<string, mixed>|null $campaign
     * @param array<int, array<string, mixed>> $slots
     */
    private static function renderForm(?array $campaign, array $slots): void
    {
        $campaignId = is_array($campaign) && isset($campaign['id']) ? absint($campaign['id']) : 0;
        $name = is_array($campaign) ? (string) ($campaign['name'] ?? '') : '';
        $advertiserId = is_array($campaign) ? absint($campaign['advertiser_id'] ?? 0) : 0;
        $slotId = is_array($campaign) ? absint($campaign['slot_id'] ?? 0) : 0;
        $status = is_array($campaign) ? (string) ($campaign['status'] ?? 'draft') : 'draft';
        $startDate = is_array($campaign) ? (string) ($campaign['start_date'] ?? '') : '';
        $endDate = is_array($campaign) ? (string) ($campaign['end_date'] ?? '') : '';
        $budget = is_array($campaign) ? (string) ($campaign['budget'] ?? '0') : '0';
        $destinationUrl = is_array($campaign) ? (string) ($campaign['destination_url'] ?? '') : '';
        $creativeText = is_array($campaign) ? (string) ($campaign['creative_text'] ?? '') : '';

        echo '<h2>' . esc_html($campaignId > 0 ? 'Edit Campaign' : 'Add Campaign') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="max-width: 900px; margin-bottom: 24px;">';
        wp_nonce_field('poradnik_ads_save_campaign');
        echo '<input type="hidden" name="action" value="poradnik_ads_save_campaign" />';
        echo '<input type="hidden" name="campaign_id" value="' . esc_attr((string) $campaignId) . '" />';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="poradnik-ads-name">Name</label></th><td><input id="poradnik-ads-name" name="name" type="text" class="regular-text" value="' . esc_attr($name) . '" required /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-advertiser">Advertiser ID</label></th><td><input id="poradnik-ads-advertiser" name="advertiser_id" type="number" min="0" class="small-text" value="' . esc_attr((string) $advertiserId) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-slot">Slot</label></th><td><select id="poradnik-ads-slot" name="slot_id">';
        echo '<option value="0">Select slot</option>';
        foreach ($slots as $slot) {
            $id = absint($slot['id'] ?? 0);
            $label = (string) ($slot['label'] ?? '');
            $slotKey = (string) ($slot['slot_key'] ?? '');
            echo '<option value="' . esc_attr((string) $id) . '" ' . selected($slotId, $id, false) . '>' . esc_html($label . ' (' . $slotKey . ')') . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-status">Status</label></th><td><select id="poradnik-ads-status" name="status"><option value="active" ' . selected($status, 'active', false) . '>active</option><option value="paused" ' . selected($status, 'paused', false) . '>paused</option><option value="draft" ' . selected($status, 'draft', false) . '>draft</option></select></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-start">Start Date</label></th><td><input id="poradnik-ads-start" name="start_date" type="text" class="regular-text" value="' . esc_attr($startDate) . '" placeholder="YYYY-mm-dd HH:ii:ss" /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-end">End Date</label></th><td><input id="poradnik-ads-end" name="end_date" type="text" class="regular-text" value="' . esc_attr($endDate) . '" placeholder="YYYY-mm-dd HH:ii:ss" /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-budget">Budget</label></th><td><input id="poradnik-ads-budget" name="budget" type="number" min="0" step="0.01" class="small-text" value="' . esc_attr($budget) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-url">Destination URL</label></th><td><input id="poradnik-ads-url" name="destination_url" type="url" class="large-text" value="' . esc_attr($destinationUrl) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ads-creative">Creative Text</label></th><td><input id="poradnik-ads-creative" name="creative_text" type="text" class="large-text" value="' . esc_attr($creativeText) . '" /></td></tr>';
        echo '</table>';

        submit_button($campaignId > 0 ? __('Update Campaign', 'poradnik-platform') : __('Add Campaign', 'poradnik-platform'));
        echo '</form>';
    }

    /**
     * @param array<int, array<string, mixed>> $campaigns
     */
    private static function renderTable(array $campaigns): void
    {
        echo '<h2>' . esc_html__('Campaign List', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width: 1200px;">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Slot</th><th>Status</th><th>Budget</th><th>Actions</th></tr></thead><tbody>';

        if ($campaigns === []) {
            echo '<tr><td colspan="6">' . esc_html__('No ad campaigns found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($campaigns as $campaign) {
            $campaignId = absint($campaign['id'] ?? 0);
            $editUrl = add_query_arg(['page' => self::PAGE_SLUG, 'campaign_id' => $campaignId], admin_url('tools.php'));
            $deleteUrl = wp_nonce_url(
                add_query_arg(['action' => 'poradnik_ads_delete_campaign', 'campaign_id' => $campaignId], admin_url('admin-post.php')),
                'poradnik_ads_delete_campaign'
            );

            echo '<tr>';
            echo '<td>' . esc_html((string) $campaignId) . '</td>';
            echo '<td>' . esc_html((string) ($campaign['name'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($campaign['slot_label'] ?? $campaign['slot_key'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($campaign['status'] ?? 'draft')) . '</td>';
            echo '<td>' . esc_html((string) ($campaign['budget'] ?? '0')) . '</td>';
            echo '<td><a href="' . esc_url($editUrl) . '">Edit</a> | <a href="' . esc_url($deleteUrl) . '" onclick="return confirm(\'Delete this campaign?\');">Delete</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
