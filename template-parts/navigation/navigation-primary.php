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
<nav id="site-navigation" class="main-navigation navbar navbar-expand-md navbar-dark" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'kicks-app' ); ?>">
		<div class="container">
				<div class="mr-auto-">
						<a class="navbar-brand" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
								<?php bloginfo( 'name' ); ?>
						</a>
				</div>
				<a class="navbar-toggler border-0 p-0" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
						<span class="hamburger hamburger--squeeze">
								<span class="hamburger-box">
										<span class="hamburger-inner"></span>
								</span>
						</span>
				</a>
				<div class="collapse navbar-collapse" id="navbarCollapse">
						<?php
						// Primary navigation menu.
						wp_nav_menu(array(
								'menu' => 'primary',
								'menu_id' => 'primary-menu',
								'theme_location' => 'primary',
								'menu_class' => 'ml-auto navbar-nav align-items-center',
								'container' => false
						));
						?>
				</div>
		</div>
</nav>
