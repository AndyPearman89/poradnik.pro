<?php

namespace Poradnik\Platform\Modules\AdsEngine\Rest;

use Poradnik\Platform\Modules\AdsEngine\Billing\InvoicePdf;
use Poradnik\Platform\Modules\AdsEngine\Repository\Billing;
use Poradnik\Platform\Modules\AdsEngine\Repository\Campaigns;
use Poradnik\Platform\Modules\AdsEngine\Repository\Slots;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class Routes
{
    public static function register(): void
    {
        register_rest_route('poradnik/v1', '/ads/slots', [
            'methods' => 'GET',
            'callback' => [self::class, 'slots'],
            'permission_callback' => [self::class, 'canRead'],
        ]);

        register_rest_route('poradnik/v1', '/ads/campaigns', [
            'methods' => 'GET',
            'callback' => [self::class, 'campaigns'],
            'permission_callback' => [self::class, 'canRead'],
        ]);

        register_rest_route('poradnik/v1', '/ads/campaigns', [
            'methods' => 'POST',
            'callback' => [self::class, 'createCampaign'],
            'permission_callback' => [self::class, 'canAdvertise'],
        ]);

        register_rest_route('poradnik/v1', '/ads/analytics', [
            'methods' => 'GET',
            'callback' => [self::class, 'analytics'],
            'permission_callback' => [self::class, 'canRead'],
        ]);

        register_rest_route('poradnik/v1', '/ads/payments', [
            'methods' => 'POST',
            'callback' => [self::class, 'createPayment'],
            'permission_callback' => [self::class, 'canAdvertise'],
        ]);

        register_rest_route('poradnik/v1', '/ads/invoices', [
            'methods' => 'GET',
            'callback' => [self::class, 'invoices'],
            'permission_callback' => [self::class, 'canRead'],
        ]);

        register_rest_route('poradnik/v1', '/ads/invoice/(?P<id>\d+)/pdf', [
            'methods' => 'GET',
            'callback' => [self::class, 'invoicePdf'],
            'permission_callback' => [self::class, 'canRead'],
        ]);
    }

    public static function canRead(): bool
    {
        return is_user_logged_in() && current_user_can('read');
    }

    public static function canAdvertise(): bool
    {
        return is_user_logged_in() && (current_user_can('advertiser') || current_user_can('manage_options') || current_user_can('read'));
    }

    public static function slots(): WP_REST_Response
    {
        return new WP_REST_Response(['items' => Slots::all()], 200);
    }

    public static function campaigns(): WP_REST_Response
    {
        $userId = get_current_user_id();
        return new WP_REST_Response(['items' => Campaigns::forUser($userId)], 200);
    }

    public static function createCampaign(WP_REST_Request $request): WP_REST_Response
    {
        $nonce = (string) $request->get_header('X-WP-Nonce');
        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response(['message' => 'Invalid nonce'], 403);
        }

        $params = $request->get_json_params();
        if (! is_array($params)) {
            $params = $request->get_params();
        }

        $params['user_id'] = get_current_user_id();
        $campaignId = Campaigns::create($params);

        if ($campaignId < 1) {
            return new WP_REST_Response(['message' => 'Campaign create failed'], 422);
        }

        return new WP_REST_Response(['id' => $campaignId], 201);
    }

    public static function analytics(): WP_REST_Response
    {
        $userId = get_current_user_id();
        return new WP_REST_Response(Campaigns::analyticsForUser($userId), 200);
    }

    public static function createPayment(WP_REST_Request $request): WP_REST_Response
    {
        $nonce = (string) $request->get_header('X-WP-Nonce');
        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response(['message' => 'Invalid nonce'], 403);
        }

        $params = $request->get_json_params();
        if (! is_array($params)) {
            $params = $request->get_params();
        }

        $userId = get_current_user_id();
        $paymentId = Billing::createPayment([
            'user_id' => $userId,
            'amount' => (float) ($params['amount'] ?? 0),
            'status' => 'paid',
            'gateway' => sanitize_key((string) ($params['gateway'] ?? 'stripe')),
            'campaign_id' => absint($params['campaign_id'] ?? 0),
        ]);

        if ($paymentId < 1) {
            return new WP_REST_Response(['message' => 'Payment create failed'], 422);
        }

        $invoiceId = Billing::createInvoice([
            'user_id' => $userId,
            'invoice_number' => Billing::nextInvoiceNumber(),
            'amount' => (float) ($params['amount'] ?? 0),
            'status' => 'paid',
            'company_name' => sanitize_text_field((string) ($params['company_name'] ?? '')),
            'vat_number' => sanitize_text_field((string) ($params['vat_number'] ?? '')),
            'address' => sanitize_textarea_field((string) ($params['address'] ?? '')),
            'tax' => (float) ($params['tax'] ?? 0),
            'payment_id' => $paymentId,
        ]);

        return new WP_REST_Response(['payment_id' => $paymentId, 'invoice_id' => $invoiceId], 201);
    }

    public static function invoices(): WP_REST_Response
    {
        return new WP_REST_Response(['items' => Billing::invoicesForUser(get_current_user_id())], 200);
    }

    public static function invoicePdf(WP_REST_Request $request): WP_REST_Response
    {
        $invoiceId = absint((string) $request['id']);
        $invoice = Billing::invoiceById($invoiceId);
        if (! is_array($invoice) || absint($invoice['user_id'] ?? 0) !== get_current_user_id()) {
            return new WP_REST_Response(['message' => 'Not found'], 404);
        }

        $pdf = InvoicePdf::render($invoice);
        $response = new WP_REST_Response($pdf, 200);
        $response->header('Content-Type', 'application/pdf');
        $response->header('Content-Disposition', 'inline; filename="invoice-' . sanitize_file_name((string) ($invoice['invoice_number'] ?? 'invoice')) . '.pdf"');
        return $response;
    }
}
