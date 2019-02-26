<?php

// Load theme variables
global $theme_variables;

$theme_variables = json_decode(file_get_contents(get_template_directory() . "/dist/variables.json"), true);


// Add header image support
add_theme_support('custom-header', array(
	'width'         => 1680,
	'height'        => 600,
	'default-image' => get_template_directory_uri() . '/img/header-image.jpg'
));

// Make theme available for translation.
load_theme_textdomain( 'kicks-app' );

// Add default posts and comments RSS feed links to head.
add_theme_support( 'automatic-feed-links' );

// Enable support for Post Thumbnails on posts and pages.
add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 900, 500, true );

// Switch default core markup for search form, comment form, and comments to output valid HTML5.
add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption'  ));

// Let WordPress manage the document title.
add_theme_support( 'title-tag' );

// Adjust default image sizes
update_option( 'thumbnail_size_w', 230 );
update_option( 'thumbnail_size_h', 230 );
update_option( 'thumbnail_crop', 1 );


// Editor styles
add_theme_support('editor-styles');

// Editor style
// Add backend styles for Gutenberg.
add_action( 'enqueue_block_editor_assets', function () {
	// Load the theme styles within Gutenberg.
	wp_enqueue_style( 'bootstrap-gutenberg', get_theme_file_uri( '/dist/editor.css' ), false );
});

/**
 * Load Gutenberg stylesheet.
 */

// Enqueue Scripts
function enqueue_scripts() {
  wp_enqueue_script( 'jquery' );
  wp_enqueue_style( 'main', get_template_directory_uri() . '/dist/main.css');
  wp_enqueue_script( 'vendor', get_template_directory_uri() . '/dist/vendor.js', array( 'jquery'));
  wp_enqueue_script( 'main', get_template_directory_uri() . '/dist/main.js', array( 'vendor'));
}
add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );

// Initialize scripts once only
function turbolinks_attributes( $url ) {
  $defer = '/main/';
  $defer_match = preg_match($defer, $url);

  $ignore = '/mmr|vendor|main/';
  $ignore_match = preg_match($ignore, $url);

  if ($defer_match && $ignore_match) {
    $url = "$url' defer";
    $url = "$url data-turbolinks-eval='false";
  } else if ($ignore_match) {
    $url = "$url' data-turbolinks-eval='false";
  } else if ($defer_match) {
    $url = "$url' defer";
  }
  return $url;
}
add_filter( 'clean_url', 'turbolinks_attributes', 1, 1 );

//

// Register widget area.
register_sidebar( array(
  'name'          => __( 'Widget Area', 'kicks-app' ),
  'id'            => 'sidebar-1',
  'description'   => __( 'Add widgets here to appear in your sidebar.', 'kicks-app' )
) );

// Register menus
register_nav_menus( array(
  'primary' => __( 'Primary Menu',      'kicks-app' ),
  'secondary' => __( 'Secondary Menu',  'kicks-app' ),
  'social'  => __( 'Social Links Menu', 'kicks-app' )
) );

// Limit archives widget
function limit_archives( $args ) {
    $args['limit'] = 6;
    return $args;
}
add_filter( 'widget_archives_args', 'limit_archives' );


// Init bootstrap hooks
if (function_exists('wp_bootstrap_hooks')) {
  wp_bootstrap_hooks();
}

// Show font-awesome search icon in searchform
add_filter( 'bootstrap_options', function($options) {
  return array_merge($options, array(
    'search_submit_label' => '<i class="fa fa-search"></i>',
		'post_tag_class' => 'badge badge-primary text-light mb-1'
  ));
} );

// Make social-menu-icons render font-awesome
add_filter( 'wp_nav_menu_args', function($args) {
  $args['social_icon_prefix'] = 'fab fa-lg fa-';
  return $args;
}, 1, 2 );

// Excerpts
define ("EXCERPT_LENGTH", 19);

add_filter( 'excerpt_length', function( $length ) {
  return EXCERPT_LENGTH;
}, 999 );

add_filter('excerpt_more', function ($more) {
  global $post;

  return '<a class="readmore d-block mt-1 mt-md-2 mt-lg-4" href="'. get_permalink($post->ID) . '">' . __('Read more') . ' »</a>';
});


// Theme functions
require_once 'inc/customizer.php';
require_once 'inc/template-functions.php';
require_once 'inc/template-tags.php';

function kicks_app_get_icon_html($array) {
	return '<i class="fas fa-' . $array['icon'] . '"></i>';
	//return 'ICOM<i class="' . $array['icon'] . '"></i>';
	//return '[' . $array['icon'] . "]";
}

function kicks_app_unique_id() {
	return uniqid();
}

add_filter('edit_post_link', function($link = null, $post_id = null, $text = '') {
	$edit_post_link_class = 'btn btn-secondary btn-sm';
	// Parse DOM
	$doc = new DOMDocument();
	@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $link );
	$links = $doc->getElementsByTagName('a');

	foreach($links as $link) {
		$classes = explode(' ', $link->getAttribute('class'));
		$classes[]= $edit_post_link_class;
		$classes = array_unique($classes);

		$link->setAttribute('class', implode(' ', $classes));
	}

	$link = preg_replace('~(?:<\?[^>]*>|<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>)\s*~i', '', $doc->saveHTML());

	return $link;
}, 3, 10);


add_filter('sticky_widget_area_options', function($options = array()) {
	global $theme_variables;

	$min_width = $theme_variables['global']['$grid-breakpoints']['lg'];

	$options = array_merge($options, array(
		'topSpacing' => 56,
		'minWidth' => $min_width
	));

	return $options;
});


add_filter('get_header_image_tag', function($html, $header = null, $attr = null) {
	$header_img_class = 'img-fluid img-cover custom-header-image';
	// Parse DOM
	$doc = new DOMDocument();
	@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html );
	$imgs = $doc->getElementsByTagName('img');

	foreach($imgs as $img) {
		$classes = explode(' ', $img->getAttribute('class'));
		$classes[]= $header_img_class;
		$classes = array_unique($classes);

		$img->setAttribute('class', trim(implode(' ', $classes)));
	}

	$html = preg_replace('~(?:<\?[^>]*>|<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>)\s*~i', '', $doc->saveHTML());

	return $html;
}, 3, 10);
