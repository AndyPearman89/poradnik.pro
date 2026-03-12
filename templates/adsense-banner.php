<?php
/** @var string $manualScript */
/** @var string $publisher */
/** @var string $slot */
/** @var string $placement */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="paa-adsense paa-adsense-<?php echo esc_attr($placement); ?>">
    <?php if ($manualScript !== '') : ?>
        <?php echo $manualScript; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($publisher); ?>" crossorigin="anonymous"></script>
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="<?php echo esc_attr($publisher); ?>"
             data-ad-slot="<?php echo esc_attr($slot); ?>"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    <?php endif; ?>
</div>
