<?php

namespace Poradnik\Platform\Domain\Setup;

use Poradnik\Platform\Domain\Ads\CampaignRepository;
use Poradnik\Platform\Domain\Ads\SlotRepository;

if (! defined('ABSPATH')) {
    exit;
}

final class DataSeeder
{
    private const SEEDED_OPTION = 'poradnik_platform_demo_seeded';
    private const REKLAMAPRO_OPTION = 'poradnik_platform_reklamapro_user_id';

    public static function maybeSeed(): void
    {
        if (get_option(self::SEEDED_OPTION, false)) {
            return;
        }

        self::seedAdvertiserAccount();
        self::seedSampleCampaigns();

        update_option(self::SEEDED_OPTION, true, false);
    }

    public static function seedAdvertiserAccount(): int
    {
        $existingId = (int) get_option(self::REKLAMAPRO_OPTION, 0);
        if ($existingId > 0 && get_userdata($existingId) !== false) {
            return $existingId;
        }

        $existing = get_user_by('login', 'reklamapro');
        if ($existing !== false) {
            update_option(self::REKLAMAPRO_OPTION, $existing->ID, false);
            return $existing->ID;
        }

        // Demo account: password is generated randomly on first install.
        // Retrieve via the WordPress password reset flow: /wp-login.php?action=lostpassword
        $demoPass = wp_generate_password(16, true, false);

        $userId = wp_insert_user([
            'user_login' => 'reklamapro',
            'user_pass' => $demoPass,
            'user_email' => 'reklamapro@poradnik.pro',
            'display_name' => 'ReklamaPRO Demo',
            'role' => 'subscriber',
            'description' => 'Demo advertiser account for testing the ad platform.',
        ]);

        if (is_wp_error($userId)) {
            return 0;
        }

        // Store generated password for first-login documentation purposes (encrypted by WP).
        update_option(self::REKLAMAPRO_OPTION, $userId, false);
        update_option('poradnik_platform_reklamapro_created', true, false);

        add_user_meta($userId, '_poradnik_advertiser', '1', true);
        add_user_meta($userId, '_poradnik_advertiser_company', 'ReklamaPRO Sp. z o.o.', true);

        return (int) $userId;
    }

    public static function seedSampleCampaigns(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'poradnik_ad_campaigns';
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        if ($count > 0) {
            return;
        }

        $advertiserId = (int) get_option(self::REKLAMAPRO_OPTION, 1);

        $slots = SlotRepository::findAll();
        $slotMap = [];
        foreach ($slots as $slot) {
            $slotMap[$slot['slot_key']] = (int) $slot['id'];
        }

        $campaigns = self::sampleCampaigns($advertiserId, $slotMap);

        foreach ($campaigns as $campaign) {
            CampaignRepository::save($campaign);
        }
    }

    /**
     * @param array<string, int> $slotMap
     * @return array<int, array<string, mixed>>
     */
    private static function sampleCampaigns(int $advertiserId, array $slotMap): array
    {
        $now = gmdate('Y-m-d H:i:s');
        $end = gmdate('Y-m-d H:i:s', strtotime('+90 days'));

        return [
            [
                'name' => 'Hosting PRO – Kampania wizerunkowa',
                'advertiser_id' => $advertiserId,
                'slot_id' => $slotMap['homepage-hero'] ?? 1,
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $end,
                'budget' => 5000.00,
                'destination_url' => 'https://poradnik.pro/hosting/',
                'creative_text' => '🚀 Hosting PRO – Najlepsze serwery dla Twojego biznesu. Sprawdź ofertę →',
            ],
            [
                'name' => 'SEO Tools – Kampania konwersyjna',
                'advertiser_id' => $advertiserId,
                'slot_id' => $slotMap['sidebar-banner'] ?? 2,
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $end,
                'budget' => 3500.00,
                'destination_url' => 'https://poradnik.pro/seo-narzedzia/',
                'creative_text' => '🔍 Narzędzia SEO PRO – Analiza słów kluczowych, audyt i monitoring pozycji.',
            ],
            [
                'name' => 'Finanse PRO – Kampania produktowa',
                'advertiser_id' => $advertiserId,
                'slot_id' => $slotMap['inline-article'] ?? 3,
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $end,
                'budget' => 7500.00,
                'destination_url' => 'https://poradnik.pro/konta-bankowe/',
                'creative_text' => '💳 Konto bankowe bez opłat – Porównaj 15 ofert i wybierz najlepszą dla siebie.',
            ],
            [
                'name' => 'VPN Ranking 2026 – Footer',
                'advertiser_id' => $advertiserId,
                'slot_id' => $slotMap['footer-banner'] ?? 4,
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $end,
                'budget' => 2000.00,
                'destination_url' => 'https://poradnik.pro/najlepszy-vpn/',
                'creative_text' => '🔐 Ranking VPN 2026 – Chroń swoją prywatność online. Sprawdź top 10.',
            ],
            [
                'name' => 'Kurs Online – Kampania sezonowa',
                'advertiser_id' => $advertiserId,
                'slot_id' => $slotMap['homepage-hero'] ?? 1,
                'status' => 'draft',
                'start_date' => gmdate('Y-m-d H:i:s', strtotime('+30 days')),
                'end_date' => gmdate('Y-m-d H:i:s', strtotime('+60 days')),
                'budget' => 1500.00,
                'destination_url' => 'https://poradnik.pro/kursy-online/',
                'creative_text' => '🎓 Kursy online PRO – Naucz się SEO, marketingu i programowania od ekspertów.',
            ],
        ];
    }
}
