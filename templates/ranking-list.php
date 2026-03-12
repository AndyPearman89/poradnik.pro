<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<ol class="ppae-ranking-list">
    <?php foreach ($products as $index => $product) :
        $url = home_url('/go/' . sanitize_title((string) ($product['slug'] ?? '')));
        ?>
        <li>
            <strong>#<?php echo esc_html((string) ($index + 1)); ?> <?php echo esc_html((string) ($product['title'] ?? '')); ?></strong>
            <span class="ppae-ranking-rating">(★ <?php echo esc_html((string) ($product['rating'] ?? '0')); ?>/5)</span>
            <a href="<?php echo esc_url($url); ?>" rel="sponsored nofollow" target="_blank"><?php echo esc_html((string) ($product['button_text'] ?? 'Sprawdź')); ?></a>
        </li>
    <?php endforeach; ?>
</ol>
