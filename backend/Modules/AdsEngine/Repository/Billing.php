<?php

namespace Poradnik\Platform\Modules\AdsEngine\Repository;

use Poradnik\Platform\Modules\AdsEngine\Support\Db;

if (! defined('ABSPATH')) {
    exit;
}

final class Billing
{
    public static function createPayment(array $payload): int
    {
        global $wpdb;
        $table = Db::table('payments');
        $now = current_time('mysql', true);

        $ok = $wpdb->insert(
            $table,
            [
                'user_id' => absint($payload['user_id'] ?? 0),
                'amount' => (float) ($payload['amount'] ?? 0),
                'status' => sanitize_key((string) ($payload['status'] ?? 'pending')),
                'gateway' => sanitize_key((string) ($payload['gateway'] ?? 'manual')),
                'campaign_id' => absint($payload['campaign_id'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%f', '%s', '%s', '%d', '%s', '%s']
        );

        return $ok === 1 ? (int) $wpdb->insert_id : 0;
    }

    public static function createInvoice(array $payload): int
    {
        global $wpdb;
        $table = Db::table('invoices');
        $now = current_time('mysql', true);

        $ok = $wpdb->insert(
            $table,
            [
                'user_id' => absint($payload['user_id'] ?? 0),
                'invoice_number' => sanitize_text_field((string) ($payload['invoice_number'] ?? '')),
                'amount' => (float) ($payload['amount'] ?? 0),
                'status' => sanitize_key((string) ($payload['status'] ?? 'pending')),
                'company_name' => sanitize_text_field((string) ($payload['company_name'] ?? '')),
                'vat_number' => sanitize_text_field((string) ($payload['vat_number'] ?? '')),
                'address' => sanitize_textarea_field((string) ($payload['address'] ?? '')),
                'tax' => (float) ($payload['tax'] ?? 0),
                'payment_id' => absint($payload['payment_id'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%f', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s']
        );

        return $ok === 1 ? (int) $wpdb->insert_id : 0;
    }

    public static function invoiceById(int $invoiceId): ?array
    {
        global $wpdb;
        $table = Db::table('invoices');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $invoiceId);
        $row = $wpdb->get_row($query, ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public static function invoicesForUser(int $userId): array
    {
        global $wpdb;
        $table = Db::table('invoices');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC", $userId);
        $rows = $wpdb->get_results($query, ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function nextInvoiceNumber(): string
    {
        global $wpdb;
        $table = Db::table('invoices');
        $year = gmdate('Y');
        $prefix = 'FV/' . $year . '/';
        $like = $prefix . '%';

        $last = (string) $wpdb->get_var($wpdb->prepare("SELECT invoice_number FROM {$table} WHERE invoice_number LIKE %s ORDER BY id DESC LIMIT 1", $like));

        $next = 1;
        if ($last !== '') {
            $parts = explode('/', $last);
            $next = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
