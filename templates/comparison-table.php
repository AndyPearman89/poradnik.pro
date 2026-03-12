<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<table class="ppae-comparison-table">
    <thead>
    <tr><th>Product</th><th>Rating</th><th>Price</th><th>Features</th><th>Link</th></tr>
    </thead>
    <tbody>
    <?php foreach ($products as $product) :
        $url = home_url('/go/' . sanitize_title((string) ($product['slug'] ?? '')));
        ?>
        <tr>
            <td><?php echo esc_html((string) ($product['title'] ?? '')); ?></td>
            <td><?php echo esc_html((string) ($product['rating'] ?? '0')); ?>/5</td>
            <td><?php echo esc_html((string) ($product['price'] ?? '')); ?></td>
            <td><?php echo esc_html((string) ($product['features'] ?? '')); ?></td>
            <td><a href="<?php echo esc_url($url); ?>" rel="sponsored nofollow" target="_blank"><?php echo esc_html((string) ($product['button_text'] ?? 'Sprawdź')); ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
