<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ppae-adsense ppae-adsense-<?php echo esc_attr($placement); ?>">
    <?php if ($script !== '') : ?>
        <?php echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($publisherId); ?>" crossorigin="anonymous"></script>
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="<?php echo esc_attr($publisherId); ?>"
             data-ad-slot="<?php echo esc_attr((string) $slot); ?>"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    <?php endif; ?>

    <?php if ($autoAds && $publisherId !== '') : ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($publisherId); ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</div>
