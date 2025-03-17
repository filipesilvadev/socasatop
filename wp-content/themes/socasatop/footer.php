<?php
/**
 * The footer for our theme
 *
 * @package SoCasaTop
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>
    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="site-info">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>