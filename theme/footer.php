<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <h4>Poradnik.pro</h4>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/poradniki/')); ?>">Poradniki</a></li>
                <li><a href="<?php echo esc_url(home_url('/rankingi/')); ?>">Rankingi</a></li>
                <li><a href="<?php echo esc_url(home_url('/recenzje/')); ?>">Recenzje</a></li>
                <li><a href="<?php echo esc_url(home_url('/specjalisci/')); ?>">Specjaliści</a></li>
            </ul>
        </div>
        <div>
            <h4>Info</h4>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/o-nas/')); ?>">O nas</a></li>
                <li><a href="<?php echo esc_url(home_url('/kontakt/')); ?>">Kontakt</a></li>
                <li><a href="<?php echo esc_url(home_url('/reklama/')); ?>">Reklama</a></li>
            </ul>
        </div>
        <div>
            <h4>Legal</h4>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/regulamin/')); ?>">Regulamin</a></li>
                <li><a href="<?php echo esc_url(home_url('/polityka-prywatnosci/')); ?>">Polityka prywatności</a></li>
            </ul>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
