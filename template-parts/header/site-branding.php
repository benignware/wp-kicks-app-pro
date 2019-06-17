<?php
/**
 * Displays header site branding
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?>
<div class="site-branding jumbotron jumbotron-fluid bg-transparent">
	<div class="wrap container">

		<?php the_custom_logo(); ?>

		<div class="site-branding-text text-<?= get_theme_mod('colorscheme') === 'dark' ? 'dark' : 'light'; ?>">
			<?php if ( is_front_page() ) : ?>
				<h1 class="site-title display-4">
					<a class="text-decoration-none text-<?= get_theme_mod('colorscheme') === 'dark' ? 'dark' : 'light'; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				</h1>
			<?php else : ?>
				<p class="site-title display-4">
					<a class="text-decoration-none text-<?= get_theme_mod('colorscheme') === 'dark' ? 'dark' : 'light'; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				</p>
			<?php endif; ?>

			<?php
			$description = get_bloginfo( 'description', 'display' );

			if ( $description || is_customize_preview() ) :
				?>
				<p class="site-description lead"><?php echo $description; ?></p>
			<?php endif; ?>
		</div><!-- .site-branding-text -->

	</div><!-- .wrap -->
</div><!-- .site-branding -->
