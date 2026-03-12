<?php

namespace PPAM\Core;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignManager
{
    public static function getAllowedStatuses(): array
    {
        return ['pending_payment', 'pending_approval', 'active', 'paused', 'completed', 'rejected'];
    }

    public static function getSlots(): array
    {
        return [
            'header_banner'       => ['label' => __('Header Banner', 'peartree-pro-ads-marketplace'), 'price' => 399],
            'sidebar_banner'      => ['label' => __('Sidebar Banner', 'peartree-pro-ads-marketplace'), 'price' => 249],
            'article_banner'      => ['label' => __('Banner w artykule', 'peartree-pro-ads-marketplace'), 'price' => 299],
            'footer_banner'       => ['label' => __('Footer Banner', 'peartree-pro-ads-marketplace'), 'price' => 149],
            'sponsored_article'   => ['label' => __('ArtykuĹ‚ sponsorowany', 'peartree-pro-ads-marketplace'), 'price' => 799],
            'sponsored_link'      => ['label' => __('Link sponsorowany', 'peartree-pro-ads-marketplace'), 'price' => 199],
            'homepage_promo'      => ['label' => __('Promocja na stronie gĹ‚Ăłwnej', 'peartree-pro-ads-marketplace'), 'price' => 349],
            'ranking_top_routers' => ['label' => __('Ranking sponsorowany: TOP routery', 'peartree-pro-ads-marketplace'), 'price' => 599],
            'ranking_top_hosting' => ['label' => __('Ranking sponsorowany: TOP hostingi', 'peartree-pro-ads-marketplace'), 'price' => 599],
            'ranking_top_laptops' => ['label' => __('Ranking sponsorowany: TOP laptopy', 'peartree-pro-ads-marketplace'), 'price' => 649],
            'ranking_top_tools'   => ['label' => __('Ranking sponsorowany: TOP narzÄ™dzia', 'peartree-pro-ads-marketplace'), 'price' => 499],
        ];
    }

    public static function createCampaign(array $data, int $userId): int
    {
        $slot = sanitize_key((string) ($data['slot'] ?? ''));
        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        $targetUrl = esc_url_raw((string) ($data['target_url'] ?? ''));
        $bannerUrl = esc_url_raw((string) ($data['banner_url'] ?? ''));
        $days = max(1, min(90, (int) ($data['duration_days'] ?? 30)));
        $budget = max(0, (float) ($data['budget'] ?? 0));

        $slots = self::getSlots();
        if (!isset($slots[$slot]) || $name === '' || $targetUrl === '') {
            return 0;
        }

        $campaignId = wp_insert_post([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'post_author' => $userId,
            'post_title' => $name,
        ]);

        if (!$campaignId || is_wp_error($campaignId)) {
            return 0;
        }

        $startDate = current_time('mysql');
        $endDate = wp_date('Y-m-d H:i:s', strtotime($startDate . ' +' . $days . ' days'));

        update_post_meta($campaignId, '_ppam_slot', $slot);
        update_post_meta($campaignId, '_ppam_target_url', $targetUrl);
        update_post_meta($campaignId, '_ppam_banner_url', $bannerUrl);
        update_post_meta($campaignId, '_ppam_duration_days', $days);
        update_post_meta($campaignId, '_ppam_budget', $budget > 0 ? $budget : (float) $slots[$slot]['price']);
        update_post_meta($campaignId, '_ppam_start_date', $startDate);
        update_post_meta($campaignId, '_ppam_end_date', $endDate);
        update_post_meta($campaignId, '_ppam_status', 'pending_payment');
        update_post_meta($campaignId, '_ppam_payment_method', '');
        update_post_meta($campaignId, '_ppam_impressions', 0);
        update_post_meta($campaignId, '_ppam_clicks', 0);

        return (int) $campaignId;
    }

    public static function setStatus(int $campaignId, string $status): void
    {
        $allowed = self::getAllowedStatuses();
        if (!in_array($status, $allowed, true)) {
            return;
        }

        update_post_meta($campaignId, '_ppam_status', $status);
    }

    public static function getUserCampaigns(int $userId): array
    {
        return get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'author' => $userId,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    public static function getActiveCampaignForSlot(string $slot)
    {
        $now = current_time('mysql');
        $campaigns = get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_ppam_slot',
                    'value' => $slot,
                ],
                [
                    'key' => '_ppam_status',
                    'value' => 'active',
                ],
                [
                    'key' => '_ppam_start_date',
                    'value' => $now,
                    'compare' => '<=',
                    'type' => 'DATETIME',
                ],
                [
                    'key' => '_ppam_end_date',
                    'value' => $now,
                    'compare' => '>=',
                    'type' => 'DATETIME',
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        return $campaigns[0] ?? null;
    }

    public static function expireExpiredCampaigns(): int
    {
        $now = current_time('mysql');

        $campaigns = get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 300,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_ppam_status',
                    'value' => 'active',
                ],
                [
                    'key' => '_ppam_end_date',
                    'value' => $now,
                    'compare' => '<=',
                    'type' => 'DATETIME',
                ],
            ],
        ]);

        $expiredCount = 0;
        foreach ($campaigns as $campaignId) {
            self::setStatus((int) $campaignId, 'completed');
            update_post_meta((int) $campaignId, '_ppam_completed_at', $now);
            EmailNotifier::notifyExpired((int) $campaignId);
            $expiredCount++;
        }

        return $expiredCount;
    }

    public static function countExpiredActiveCampaigns(): int
    {
        $now = current_time('mysql');

        $query = new \WP_Query([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_ppam_status',
                    'value' => 'active',
                ],
                [
                    'key' => '_ppam_end_date',
                    'value' => $now,
                    'compare' => '<=',
                    'type' => 'DATETIME',
                ],
            ],
        ]);

        return (int) $query->found_posts;
    }

    public static function getOverviewStats(): array
    {
        $campaigns = get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 2000,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $stats = [
            'total' => 0,
            'active' => 0,
            'pending_payment' => 0,
            'pending_approval' => 0,
            'paused' => 0,
            'completed' => 0,
            'rejected' => 0,
            'total_budget' => 0.0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'avg_ctr' => 0.0,
        ];

        foreach ($campaigns as $campaign) {
            $campaignId = (int) $campaign->ID;
            $status = (string) get_post_meta($campaignId, '_ppam_status', true);
            $budget = (float) get_post_meta($campaignId, '_ppam_budget', true);
            $impressions = (int) get_post_meta($campaignId, '_ppam_impressions', true);
            $clicks = (int) get_post_meta($campaignId, '_ppam_clicks', true);

            $stats['total']++;
            if (isset($stats[$status])) {
                $stats[$status]++;
            }

            $stats['total_budget'] += $budget;
            $stats['total_impressions'] += $impressions;
            $stats['total_clicks'] += $clicks;
        }

        if ($stats['total_impressions'] > 0) {
            $stats['avg_ctr'] = round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2);
        }

        $stats['total_budget'] = round($stats['total_budget'], 2);

        return $stats;
    }

    public static function getRecentCampaigns(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $campaigns = get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $rows = [];
        foreach ($campaigns as $campaign) {
            $campaignId = (int) $campaign->ID;
            $status = (string) get_post_meta($campaignId, '_ppam_status', true);
            $budget = (float) get_post_meta($campaignId, '_ppam_budget', true);
            $impressions = (int) get_post_meta($campaignId, '_ppam_impressions', true);
            $clicks = (int) get_post_meta($campaignId, '_ppam_clicks', true);

            $rows[] = [
                'id' => $campaignId,
                'title' => (string) $campaign->post_title,
                'status' => $status,
                'status_label' => self::getStatusLabel($status),
                'budget' => round($budget, 2),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
                'created_at' => (string) $campaign->post_date,
                'edit_url' => (string) get_edit_post_link($campaignId, ''),
            ];
        }

        return $rows;
    }

    public static function getStatusLabel(string $status): string
    {
        $map = [
            'pending_payment'  => __('Oczekuje na pĹ‚atnoĹ›Ä‡', 'peartree-pro-ads-marketplace'),
            'pending_approval' => __('Oczekuje na akceptacjÄ™', 'peartree-pro-ads-marketplace'),
            'active'           => __('Aktywna', 'peartree-pro-ads-marketplace'),
            'paused'           => __('Wstrzymana', 'peartree-pro-ads-marketplace'),
            'completed'        => __('ZakoĹ„czona', 'peartree-pro-ads-marketplace'),
            'rejected'         => __('Odrzucona', 'peartree-pro-ads-marketplace'),
        ];

        return (string) ($map[$status] ?? $status);
    }

    public static function advertiserAction(int $campaignId, int $userId, string $action): bool
    {
        if ($campaignId <= 0 || $userId <= 0) {
            return false;
        }

        $post = get_post($campaignId);
        if (!$post || (int) $post->post_author !== $userId) {
            return false;
        }

        $status = (string) get_post_meta($campaignId, '_ppam_status', true);
        $isExpired = self::isCampaignExpired($campaignId);

        if ($action === 'pause' && $status === 'active') {
            self::setStatus($campaignId, 'paused');
            return true;
        }

        if ($action === 'resume' && $status === 'paused' && !$isExpired) {
            self::setStatus($campaignId, 'active');
            return true;
        }

        return false;
    }

    public static function adminAction(int $campaignId, string $action): bool
    {
        if ($campaignId <= 0) {
            return false;
        }

        $status = (string) get_post_meta($campaignId, '_ppam_status', true);
        $isExpired = self::isCampaignExpired($campaignId);

        if ($action === 'approve' && $status === 'pending_approval' && !$isExpired) {
            self::setStatus($campaignId, 'active');
            EmailNotifier::notifyStatusChange($campaignId, 'active');
            return true;
        }

        if ($action === 'reject' && in_array($status, ['pending_approval', 'pending_payment'], true)) {
            self::setStatus($campaignId, 'rejected');
            EmailNotifier::notifyStatusChange($campaignId, 'rejected');
            return true;
        }

        if ($action === 'pause' && $status === 'active') {
            self::setStatus($campaignId, 'paused');
            EmailNotifier::notifyStatusChange($campaignId, 'paused');
            return true;
        }

        if ($action === 'resume' && $status === 'paused' && !$isExpired) {
            self::setStatus($campaignId, 'active');
            return true;
        }

        if ($action === 'complete' && in_array($status, ['active', 'paused'], true)) {
            self::setStatus($campaignId, 'completed');
            update_post_meta($campaignId, '_ppam_completed_at', current_time('mysql'));
            EmailNotifier::notifyStatusChange($campaignId, 'completed');
            return true;
        }

        return false;
    }

    private static function isCampaignExpired(int $campaignId): bool
    {
        $end = (string) get_post_meta($campaignId, '_ppam_end_date', true);
        if ($end === '') {
            return false;
        }

        return strtotime($end) <= strtotime(current_time('mysql'));
    }
}

