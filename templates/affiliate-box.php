<?php
if (!defined('ABSPATH')) {
    exit;
}

$title = (string) ($product['title'] ?? '');
$description = (string) ($product['description'] ?? '');
$price = (string) ($product['price'] ?? '');
$rating = (string) ($product['rating'] ?? '0');
$image = (string) ($product['image'] ?? '');
$button = (string) ($product['button_text'] ?? 'Sprawdź ofertę');
$slug = sanitize_title((string) ($product['slug'] ?? ''));
$url = home_url('/go/' . $slug);
?>
<div class="ppae-affiliate-box">
    <?php if ($image !== '') : ?>
        <img class="ppae-affiliate-image" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
    <?php endif; ?>
    <div class="ppae-affiliate-content">
        <h3><?php echo esc_html($title); ?></h3>
        <p class="ppae-affiliate-rating">★ <?php echo esc_html($rating); ?>/5</p>
        <p><?php echo esc_html($description); ?></p>
        <?php if ($price !== '') : ?><p class="ppae-affiliate-price"><?php echo esc_html($price); ?></p><?php endif; ?>
        <a class="ppae-affiliate-btn" href="<?php echo esc_url($url); ?>" rel="sponsored nofollow noopener" target="_blank"><?php echo esc_html($button); ?></a>
    </div>
</div>
