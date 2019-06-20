<?php
/**
 * Displays top navigation
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.2
 */
?>
<nav id="site-navigation" class="bg-<?= get_theme_mod( 'colorscheme') ?: 'light'; ?> navbar navbar-expand-md navbar-<?= get_theme_mod( 'colorscheme') ?: 'light'; ?> px-0" role="navigation" aria-label="<?php esc_attr_e( 'Top Menu', 'twentyseventeen' ); ?>">
	<div class="wrap container">
		<?php if (!has_custom_logo()): ?>
			<a class="navbar-brand site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" title="<?php bloginfo( 'name' ); ?>">
				<?php bloginfo( 'name' ); ?>
			</a>
		<?php else: ?>
			<?php the_custom_logo([
				'class' => 'navbar-brand'
			]); ?>
		<?php endif; ?>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse flex-grow-0" id="navbarCollapse">
			<?php
				// Primary navigation menu.
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu'
				));
			?>
		</div>
		<?php if ( ( twentyseventeen_is_frontpage() || ( is_home() && is_front_page() ) ) && has_custom_header() ) : ?>
			<!--
			<a href="#content" class="menu-scroll-down d-none d-lg-block"><?php echo twentyseventeen_get_svg( array( 'icon' => 'arrow-right' ) ); ?><span class="screen-reader-text"><?php _e( 'Scroll down to content', 'twentyseventeen' ); ?></span></a>
			-->
		<?php endif; ?>
	</div><!-- .wrap -->
</nav><!-- #site-navigation -->
