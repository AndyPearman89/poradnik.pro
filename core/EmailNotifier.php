<?php

namespace PPAM\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wysyła powiadomienia e-mail do reklamodawców przy zmianie statusu kampanii.
 *
 * Wyzwalane przez CampaignManager::adminAction() oraz expireExpiredCampaigns().
 */
class EmailNotifier
{
    /**
     * Wyślij powiadomienie po zmianie statusu przez admina.
     *
     * @param int    $campaignId
     * @param string $newStatus  Nowy status (active|rejected|paused|completed)
     * @param string $context    'admin' | 'cron'
     */
    public static function notifyStatusChange(int $campaignId, string $newStatus, string $context = 'admin'): void
    {
        $email = self::getOwnerEmail($campaignId);
        if ($email === '') {
            return;
        }

        $campaign = get_post($campaignId);
        if (!$campaign) {
            return;
        }

        $name       = get_the_title($campaignId);
        $slotKey    = (string) get_post_meta($campaignId, '_ppam_slot', true);
        $slots      = CampaignManager::getSlots();
        $slotLabel  = $slots[$slotKey]['label'] ?? $slotKey;
        $panelUrl   = get_option('ppam_page_panel_id')
            ? get_permalink((int) get_option('ppam_page_panel_id'))
            : home_url('/panel-reklamodawcy/');
        $siteName   = get_bloginfo('name');

        [$subject, $body] = self::buildContent($newStatus, $name, $slotLabel, $panelUrl, $siteName, $context);

        if ($subject === '') {
            return;
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $siteName . ' <' . get_option('admin_email') . '>',
        ];

        wp_mail($email, $subject, $body, $headers);
    }

    /**
     * Wyślij powiadomienie po automatycznym wygaszeniu kampanii przez cron.
     *
     * @param int $campaignId
     */
    public static function notifyExpired(int $campaignId): void
    {
        self::notifyStatusChange($campaignId, 'completed', 'cron');
    }

    // -------------------------------------------------------------------------
    // Prywatne helpery
    // -------------------------------------------------------------------------

    private static function getOwnerEmail(int $campaignId): string
    {
        $post = get_post($campaignId);
        if (!$post) {
            return '';
        }

        $user = get_userdata((int) $post->post_author);
        if (!$user || !is_email($user->user_email)) {
            return '';
        }

        return $user->user_email;
    }

    /**
     * Zwraca [subject, body] dla danego statusu.
     *
     * @return array{0: string, 1: string}
     */
    private static function buildContent(
        string $newStatus,
        string $name,
        string $slotLabel,
        string $panelUrl,
        string $siteName,
        string $context
    ): array {
        switch ($newStatus) {
            case 'active':
                $subject = "[{$siteName}] Kampania zatwierdzona — emisja aktywna";
                $intro   = 'Twoja kampania reklamowa została <strong>zatwierdzona</strong> przez administratora i jest już aktywna.';
                break;

            case 'rejected':
                $subject = "[{$siteName}] Kampania odrzucona";
                $intro   = 'Niestety, Twoja kampania reklamowa została <strong>odrzucona</strong> przez administratora.';
                break;

            case 'paused':
                $subject = "[{$siteName}] Kampania wstrzymana";
                $intro   = 'Twoja kampania reklamowa została <strong>wstrzymana</strong> przez administratora. Jeśli masz pytania, skontaktuj się z nami.';
                break;

            case 'completed':
                if ($context === 'cron') {
                    $subject = "[{$siteName}] Kampania zakończona (data emisji minęła)";
                    $intro   = 'Twoja kampania reklamowa <strong>zakończyła się</strong> — osiągnięto datę końca emisji.';
                } else {
                    $subject = "[{$siteName}] Kampania zakończona";
                    $intro   = 'Twoja kampania reklamowa została <strong>zakończona</strong> przez administratora.';
                }
                break;

            default:
                return ['', ''];
        }

        $body = self::wrapHtml($siteName, $intro, $name, $slotLabel, $panelUrl, $newStatus);

        return [$subject, $body];
    }

    private static function wrapHtml(
        string $siteName,
        string $intro,
        string $campaignName,
        string $slotLabel,
        string $panelUrl,
        string $status
    ): string {
        $statusLabel = CampaignManager::getStatusLabel($status);
        $escapedSiteName    = esc_html($siteName);
        $escapedCampaign    = esc_html($campaignName);
        $escapedSlot        = esc_html($slotLabel);
        $escapedStatusLabel = esc_html($statusLabel);
        $escapedPanelUrl    = esc_url($panelUrl);

        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333;">
  <h2 style="color:#1a73e8;">{$escapedSiteName} — Panel Reklamodawcy</h2>
  <p>{$intro}</p>
  <table style="width:100%;border-collapse:collapse;margin:16px 0;">
    <tr>
      <td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;width:40%;font-weight:bold;">Kampania</td>
      <td style="padding:8px;border:1px solid #ddd;">{$escapedCampaign}</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Slot reklamowy</td>
      <td style="padding:8px;border:1px solid #ddd;">{$escapedSlot}</td>
    </tr>
    <tr>
      <td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Status</td>
      <td style="padding:8px;border:1px solid #ddd;">{$escapedStatusLabel}</td>
    </tr>
  </table>
  <p style="margin-top:24px;">
    <a href="{$escapedPanelUrl}" style="background:#1a73e8;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;">
      Przejdź do panelu reklamodawcy
    </a>
  </p>
  <p style="margin-top:32px;font-size:12px;color:#999;">
    Wiadomość automatyczna z systemu {$escapedSiteName}.
    Nie odpowiadaj na tę wiadomość.
  </p>
</body>
</html>
HTML;
    }
}
