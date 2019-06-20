<?php


add_action('after_setup_theme', function() {
  remove_theme_support( 'custom-header' );
}, 11);

add_action('wp_enqueue_scripts', function() {
  wp_enqueue_style( 'oswald', 'https://fonts.googleapis.com/css?family=Oswald:300,400,700&display=swap', false );
  wp_enqueue_style( 'open-sans-x', 'http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,400,700,300', false );


  wp_enqueue_style( 'kicks-app-child-style', get_stylesheet_directory_uri() . '/style.css', false );
}, 11);

add_filter('sticky_widget_area_options', function($options = array()) {
	$options = array_merge($options, array(
		'resizeSensor' => true,
		'topSpacing' => 88,
		'bottomSpacing' => 0,
		'minWidth' => 992
	));

	return $options;
});

// Customize Basic Contact Form
add_filter('shortcode_atts_basic_contact_form', function($out, $pairs, $atts, $shortcode) {
  return array_merge($out, array(
    'template' => get_theme_file_path() . '/contact-form.php'
  ), $atts);
}, 10, 4);


add_filter( 'pre_custom_archive_template', function($template, $post_id, $post_type) {
  return 'archive-job.php';
}, 11, 3);
