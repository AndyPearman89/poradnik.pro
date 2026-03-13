<?php

use Poradnik\Platform\Modules\AdsEngine\SlotRenderer;

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('render_ad_slot')) {
    function render_ad_slot(string $slotName): string
    {
        return SlotRenderer::render($slotName);
    }
}
