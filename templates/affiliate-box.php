<?php
/** @var array $affiliate */

if (!defined('ABSPATH')) {
    exit;
}

$title = (string) ($affiliate['title'] ?? '');
$description = (string) ($affiliate['description'] ?? '');
$buttonText = (string) ($affiliate['button_text'] ?? 'Sprawdź ofertę');
$imageUrl = (string) ($affiliate['image_url'] ?? '');
$slug = sanitize_title((string) ($affiliate['slug'] ?? ''));
$linkUrl = home_url('/go/' . $slug);
?>
<div class="paa-affiliate-box" data-affiliate-id="<?php echo esc_attr((string) (int) ($affiliate['id'] ?? 0)); ?>">
    <?php if ($imageUrl !== '') : ?>
        <div class="paa-affiliate-image-wrap">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($title); ?>" class="paa-affiliate-image" loading="lazy" />
        </div>
    <?php endif; ?>

    <div class="paa-affiliate-content">
        <?php if ($title !== '') : ?>
            <h3 class="paa-affiliate-title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>

        <?php if ($description !== '') : ?>
            <p class="paa-affiliate-description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>

        <a class="paa-affiliate-btn" href="<?php echo esc_url($linkUrl); ?>" target="_blank" rel="sponsored nofollow noopener">
            <?php echo esc_html($buttonText); ?>
        </a>
    </div>
</div>
