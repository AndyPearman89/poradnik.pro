<?php
get_header();
?>
<section class="container section">
    <h1>404</h1>
    <p><?php esc_html_e('Nie znaleziono strony.', 'generatepress-child-poradnik'); ?></p>
    <a class="btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Wróć na start', 'generatepress-child-poradnik'); ?></a>
</section>
<?php
get_footer();
