<?php


require_once 'inc/customizer_2.php';
require_once 'inc/customize-theme.php';
require_once 'inc/template-tags.php';
require_once 'inc/template-functions.php';
require_once 'inc/icon-functions.php';
require_once 'inc/theme-tags.php';

// require_once 'inc/customizer.php';

// load_theme_textdomain( 'twentyseventeen' );
	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );
	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );
	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
	 */
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'twentyseventeen-featured-image', 2000, 1200, true );
	add_image_size( 'twentyseventeen-thumbnail-avatar', 100, 100, true );

	$custom_logo = array_reduce(array(get_stylesheet_directory(), get_template_directory()), function($carry, $item) {
		return array_merge($carry, glob($item . '/img/custom-logo.{jpg,png,gif,svg}', GLOB_BRACE));
	}, array());

// $custom_logo = glob(get_stylesheet_directory() . '/img/custom-logo.{jpg,png,gif,svg}', GLOB_BRACE);



add_action( 'after_setup_theme', function() {
  add_theme_support( 'custom-logo', array(
    'height'      => 40,
    'width'       => 80,
    'flex-height' => false,
    'flex-width'  => true,
    'header-text' => array(
      'site-title',
      'site-description'
    ),
		'default-image' => array_reduce(
			array(get_stylesheet_directory(), get_template_directory()),
			function($carry, $item) {
				return array_merge($carry, glob($item . '/img/custom-logo.{jpg,png,gif,svg}', GLOB_BRACE));
			}, array()
		)
  ));

  // Add header image support
  add_theme_support('custom-header', array(
  	'width'         => 1680,
  	'height'        => 600,
		'default-image' => array_reduce(
			array(get_stylesheet_directory(), get_template_directory()),
			function($carry, $item) {
				return array_merge($carry, glob($item . '/img/header-image.{jpg,png,gif,svg}', GLOB_BRACE));
			}, array()
		)
  ));
}, 10);

add_action('wp_enqueue_scripts', function() {
  //  wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css');
  wp_enqueue_script('popper-js', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js', array( 'jquery' ), '', true);
  // wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js', array( 'jquery' ), '', true);

	wp_enqueue_script('popper-js', get_template_directory_uri() . '/dist/popper.js/popper.min.js', array( 'jquery' ), '', true);
	wp_enqueue_script('bootstrap-js', get_template_directory_uri() . '/dist/bootstrap/js/bootstrap.min.js', array( 'jquery' ), '', true);

  // wp_enqueue_script('turbolinks-js', 'https://cdn.jsdelivr.net/npm/turbolinks@5.2.0/dist/turbolinks.min.js', null, '', false);

  wp_enqueue_style( 'bootstrap-css', get_template_directory_uri() . '/dist/bootstrap.css');

	wp_enqueue_style( 'fontawesome-css', get_template_directory_uri() . '/dist/fontawesome/css/all.css');

  wp_enqueue_style( 'kicks-app-css', get_template_directory_uri() . '/style.css');
}, 10);

add_action( 'enqueue_block_editor_assets', function() {
	wp_deregister_style( 'bootstrap-editor-css');
	wp_dequeue_style( 'bootstrap-editor-css');

	wp_register_style( 'bootstrap-editor-css', get_template_directory_uri() . '/dist/bootstrap-editor.css');
	wp_enqueue_style( 'bootstrap-editor-css');
}, 11);


add_action( 'wp_enqueue_scripts', function() {
  // Get custom header meta from customizer with defaults.
  $default_header_meta = array(
      'background_position' => 'left',
      'background_size'     => 'cover'
  );
  $header_meta = get_option( 'custom_header_meta', $default_header_meta );

  // Render header meta as CSS parameters.
  $header_styles = '';
  foreach ( $header_meta as $key => $val ) {
    $header_styles .= str_replace( '_', '-', $key ) . ':' . $val . ';';
  }

  // Render header image as CSS parameters.
  if ( get_header_image() ) {
      $header_image = get_theme_mod( 'header_image_data' );
      // $header_styles .= 'background-image:url(' . $header_image->url . ');';
      // $header_styles .= 'width:' . (string) $header_image->width . 'px;';
      // $header_styles .= 'height:' . (string) $header_image->height . 'px;';
      $header_styles .= 'object-fit:' . $header_meta['background_size'] . ';';
      $header_styles .= 'height: 100%;';
  }

  // Finally render CSS selector with parameters.
  $custom_css = ".wp-custom-header img { $header_styles }";

  wp_register_style( 'kicks-app-custom-header-inline-style', false );
  wp_enqueue_style( 'kicks-app-custom-header-inline-style' );
  wp_add_inline_style('kicks-app-custom-header-inline-style', $custom_css );
}, 10);

add_action('get_header',function() {
	remove_action('wp_head', '_admin_bar_bump_cb');
});

if (function_exists( 'wp_bootstrap_hooks' )) {
  wp_bootstrap_hooks();
}

// Make social-menu-icons render font-awesom
add_filter( 'wp_nav_menu_args', function($args) {
  $args['social_icon_prefix'] = 'fab fa-lg fa-';

  return $args;
}, 1, 2);

// Excerpts
define('EXCERPT_LENGTH', 19);

add_filter( 'excerpt_length', function( $length ) {
  return EXCERPT_LENGTH;
}, 999 );

add_filter('excerpt_more', function ($more) {
  global $post;

  return '<a class="readmore d-block mt-1 mt-md-2 mt-lg-4" href="'. get_permalink($post->ID) . '">' . __('Read more') . ' »</a>';
});

// Register widget area.

register_sidebar(array(
  'name'          => __( 'Widget Area', 'kicks-app' ),
  'id'            => 'sidebar-1',
  'description'   => __( 'Add widgets here to appear in your sidebar.', 'kicks-app' )
));

register_sidebar(array(
  'name'          => __( 'Widget Area', 'kicks-app' ),
  'id'            => 'sidebar-2',
  'description'   => __( 'Add widgets here to appear in your footer.', 'kicks-app' )
));


// Register menus
register_nav_menus(array(
  'primary' => __( 'Primary Menu',      'kicks-app' ),
  'secondary' => __( 'Secondary Menu',  'kicks-app' ),
  'social'  => __( 'Social Links Menu', 'kicks-app' )
));

add_filter('theme_icon_html', function($html, $name) {
	return sprintf('<i class="fas far fa-%s"> </i>', $name);
}, 10, 2);

add_filter('the_category', function($list) {
	print_r($list);

	return $list;
});


add_filter('get_custom_logo', function($html) {
  return 'Test' . $html;
}, 11);
