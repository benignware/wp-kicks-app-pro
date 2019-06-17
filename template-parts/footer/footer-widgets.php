<?php
/**
 * Displays footer widgets if assigned
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?>

<?php if (is_active_sidebar('sidebar-2')): ?>
	<aside class="widget-area" role="complementary" aria-label="<?php esc_attr_e( 'Footer', 'twentyseventeen' ); ?>">
		<?php dynamic_sidebar( 'sidebar-2' ); ?>
	</aside><!-- .widget-area -->
<?php endif; ?>
