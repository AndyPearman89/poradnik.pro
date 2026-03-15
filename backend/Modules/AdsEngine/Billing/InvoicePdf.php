<?php

namespace Poradnik\Platform\Modules\AdsEngine\Billing;

if (! defined('ABSPATH')) {
    exit;
}

final class InvoicePdf
{
    public static function render(array $invoice): string
    {
        $lines = [
            'PORADNIK.PRO - FAKTURA',
            'Nr: ' . (string) ($invoice['invoice_number'] ?? ''),
            'Data: ' . gmdate('Y-m-d'),
            'Firma: ' . (string) ($invoice['company_name'] ?? ''),
            'NIP/VAT: ' . (string) ($invoice['vat_number'] ?? ''),
            'Adres: ' . preg_replace('/\s+/', ' ', (string) ($invoice['address'] ?? '')),
            'Kwota: ' . number_format((float) ($invoice['amount'] ?? 0), 2, '.', ' ') . ' PLN',
            'Podatek: ' . number_format((float) ($invoice['tax'] ?? 0), 2, '.', ' ') . ' PLN',
            'Status: ' . (string) ($invoice['status'] ?? 'pending'),
        ];

        $content = "BT\n/F1 12 Tf\n50 780 Td\n";
        $first = true;
        foreach ($lines as $line) {
            $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            if (! $first) {
                $content .= "0 -16 Td\n";
            }
            $content .= '(' . $text . ") Tj\n";
            $first = false;
        }
        $content .= "ET";

        $streamLen = strlen($content);
        $objects = [];
        $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj";
        $objects[] = "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj";
        $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>endobj";
        $objects[] = "4 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj";
        $objects[] = "5 0 obj<< /Length {$streamLen} >>stream\n{$content}\nendstream endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }

        $xrefPos = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }
        $pdf .= "trailer<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }
}
