<?php
/**
 * フッターテンプレート
 *
 * @package 行政書士試験ブログ
 */
?>

<footer class="site-footer">
    <div class="footer-content">
        <p><?php bloginfo('name'); ?> | <?php bloginfo('description'); ?></p>
        
        <div class="footer-links">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'footer',
                'depth'          => 1,
                'fallback_cb'    => '',
            ));
            ?>
        </div>
        
        <p class="copyright">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All Rights Reserved.</p>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
