<?php

$theme_colors = [
  'primary' => [
    'default' => '#007bff',
    'label' => __('Primary')
  ],
  'secondary' => [
    'default' => '#6c757d',
    'label' => __('Secondary')
  ],
  'success' => [
    'default' => '#28a745',
    'label' => __('Success')
  ],
  'info' => [
    'default' => '#17a2b8',
    'label' => __('Info')
  ],
  'danger' => [
    'default' => '#dc3545',
    'label' => __('Danger')
  ],
  'warning' => [
    'default' => '#ffc107',
    'label' => __('Warning')
  ],
  'light' => [
    'default' => '#f8f9fa',
    'label' => __('Light')
  ],
  'dark' => [
    'default' => '#343a40',
    'label' => __('Dark')
  ]
];

add_action('customize_register', function($wp_customize) use ($theme_colors) {
  $wp_customize->add_section( 'theme_colors' , array(
    'title' => 'Theme Colors',
    'priority' => 100
  ));


  foreach ($theme_colors as $color_slug => $color_setting) {
    $wp_customize->add_setting(
      $color_slug, array(
        'default' => $color_setting['default'],
        
        // 'type' => 'option',
        // 'capability' =>  'edit_theme_options'
      )
    );

    $wp_customize->add_control(
      new WP_Customize_Color_Control(
        $wp_customize,
        $color_slug,
        array(
          'label' => $color_setting['label'],
          'section' => 'theme_colors',
          'settings' => $color_slug,
          'priority' => 10
        )
      )
    );
  }
}, 10);
