<?php


function get_theme_vars() {
  global $theme_vars;

  return $theme_vars;
}


function register_theme_var($name, $attributes = []) {
  global $theme_vars;

  if (!isset($theme_vars)) {
    $theme_vars = [];
  }

  $theme_vars[$name] = array_merge([
    'name' => $name,
    'label' => $name,
    'type' => 'text'
  ], $attributes);
}

function register_theme_vars($vars) {
  global $theme_vars;

  foreach ($vars as $name => $attributes) {
    register_theme_var($name, $attributes);
  }
}

function register_theme_font($name, $attributes = []) {
  global $theme_fonts;

  if (!isset($theme_fonts)) {
    $theme_fonts = [];
  }

  $theme_vars[$name] = array_merge([
    'name' => $name
  ], $attributes);
}


function load_theme_defaults() {
  global $theme_defaults;

  if (isset($theme_defaults)) {
    return $theme_defaults;
  }

  $locations = array(
    get_template_directory() . '/theme.css',
    get_stylesheet_directory() . '/theme.css'
  );

  foreach ($locations as $file) {
    $result = array();

    if (file_exists($file)) {
      ob_start();
      include $file;
      $content = ob_get_contents();
      ob_end_clean();

      preg_match_all("~\s*:root\s*\{([^}]*)\s*\}~", $content, $decl_matches, PREG_SET_ORDER);

      foreach($decl_matches as $decl_match) {
        $decl = $decl_match[1];
        preg_match_all("~\s*--([a-zA-Z_-]*):([^;]*)\s*~", $decl, $var_matches, PREG_SET_ORDER);

        foreach($var_matches as $var_match) {
          $name = $var_match[1];
          $value = $var_match[2];

          $result[$name] = $value;
        }

      }
    }
  }

  $theme_defaults = $result;

  return $result;
}


add_action('customize_register', function($wp_customize) {
  global $theme_vars;

  $theme_defaults = load_theme_defaults();

  foreach ($theme_vars as $name => $attributes) {
    $wp_customize->add_setting($name, [
      'default' => $theme_defaults[$name] ?: $attributes['default']
    ]);

    $control = $attributes['control'] ?: [];

    switch ($control['type']) {
      case 'color':
        $wp_customize->add_control(
          new WP_Customize_Color_Control(
            $wp_customize,
            $name,
            array_merge(array(
              'label' => ($control['label'] ?: $name),
              'section' =>  ($control['section'] ?: 'theme_parameters'),
              'settings' => $name,
              'priority' => 10
            ), $control)
          )
        );
        break;
      default:
        $wp_customize->add_control(
          new WP_Customize_Control(
            $wp_customize,
            $name,
            array_merge(array(
              'label' => ($control['label'] ?: $name),
              'section' =>  ($control['section'] ?: 'theme_parameters'),
              'settings' => $name,
              'priority' => 10
            ), $control)
          )
        );
    }
  }
});

function get_theme_custom_css() {
  global $theme_vars;

  $theme_mods = get_theme_mods();

  /*
  print_r($theme_mods);
  exit;
  */

  $css = '';

  // $css.= 'body { background: yellow; }';

  $css.= <<<EOT
:root {

EOT;
  foreach ($theme_vars as $name => $attributes) {
    $theme_defaults = load_theme_defaults();
    $value = get_theme_mod($name, $theme_defaults[$name] ?: $attributes['default']);

    if ($attributes['unit']) {
      $value = $value . $attributes['unit'];
    }

    if ($value) {
      $css.= <<<EOT
  --$name: $value;

EOT;
    }
  }

  $css.= <<<EOT
}

EOT;

  return $css;
}

add_action( 'wp_enqueue_scripts', function() {
  $css = get_theme_custom_css();

  wp_register_style( 'kicks-app-custom-style', false );
  wp_enqueue_style( 'kicks-app-custom-style' );
  wp_add_inline_style('kicks-app-custom-style', $css );
}, 11);

add_action( 'enqueue_block_editor_assets', function() {
  $css = get_theme_custom_css();

  wp_register_style( 'kicks-app-custom-style', false );
  wp_enqueue_style( 'kicks-app-custom-style' );
  wp_add_inline_style('kicks-app-custom-style', $css );
}, 12);

// Register sections
add_action('customize_register', function($wp_customize) {
  $wp_customize->add_section('common', array(
    'title' => 'Common',
    'priority' => 100
  ));

  $wp_customize->add_section('components', array(
    'title' => 'Components',
    'priority' => 100
  ));

  $wp_customize->add_section('forms', array(
    'title' => 'Forms',
    'priority' => 100
  ));

  $wp_customize->add_section('typography', array(
    'title' => 'Typography',
    'priority' => 100
  ));
});

register_theme_vars([
  'primary' => [
    'default' => '#007bff',
    'editor' => true,
    'control' => [
      'label' => __('Primary'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'secondary' => [
    'default' => '#6c757d',
    'editor' => true,
    'control' => [
      'label' => __('Secondary'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'success' => [
    'default' => '#28a745',
    'editor' => true,
    'control' => [
      'label' => __('Success'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'info' => [
    'default' => '#17a2b8',
    'editor' => true,
    'control' => [
      'label' => __('Info'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'danger' => [
    'default' => '#dc3545',
    'editor' => true,
    'control' => [
      'label' => __('Danger'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'warning' => [
    'default' => '#ffc107',
    'editor' => true,
    'control' => [
      'label' => __('Warning'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'light' => [
    'default' => '#f8f9fa',
    'editor' => true,
    'control' => [
      'label' => __('Light'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'dark' => [
    'default' => '#343a40',
    'editor' => true,
    'control' => [
      'label' => __('Dark'),
      'section' => 'colors',
      'type' => 'color'
    ]
  ],
  'body-bg' => [
    'default' => 'white',
    'control' => [
      'label' => __('Body Background'),
      'section' => 'common',
      'type' => 'color'
    ]
  ],
  'body-color' => [
    'default' => 'black',
    'control' => [
      'label' => __('Body Color'),
      'section' => 'common',
      'type' => 'color'
    ]
  ],
  'link-color' => [
    'default' => 'black',
    'control' => [
      'label' => __('Link Color'),
      'section' => 'typography',
      'type' => 'color'
    ]
  ],
  'font-family-sans-serif' => [
    'default' => 'Times New Roman',
    'control' => [
      'label' => __('Font Family Sans Serif'),
      'type' => 'select',
      'section' => 'typography',
      'choices' => [
        'Verdana',
        'Times New Roman',
        'Arial',
        'Open Sans'
      ]
    ]
  ],
  'border-radius' => [
    'default' => '3',
    'unit' => 'px',
    'control' => [
      'label' => __('Border Radius'),
      'type' => 'number',
      'section' => 'components'
    ]
  ],
  'border-width' => [
    'default' => '1',
    'unit' => 'px',
    'control' => [
      'label' => __('Border Width'),
      'type' => 'number',
      'section' => 'components'
    ]
  ],
  'border-color' => [
    'default' => 'rgba(222,226,230,1)',
    'control' => [
      'label' => __('Border Color'),
      'type' => 'color',
      'section' => 'components'
    ]
  ],
  'card-border-color' => [
    'default' => 'rgba(222,226,230,1)',
    'control' => [
      'label' => __('Card Border Color'),
      'type' => 'color',
      'section' => 'components'
    ]
  ],
  'card-bg' => [
    'default' => '#fff',
    'control' => [
      'label' => __('Card Background'),
      'type' => 'color',
      'section' => 'components'
    ]
  ],
  'input-border-color' => [
    'default' => '#ced4da',
    'control' => [
      'label' => __('Input Border Color'),
      'type' => 'color',
      'section' => 'forms'
    ]
  ]
]);

add_action( 'after_setup_theme', function() {
  global $theme_vars;

  /*
  $editor_color_palette = apply_filters('theme_editor_colors', array());

  foreach ($editor_color_palette as $index => $color) {
    if (is_string($color) && isset($theme_vars[$color])) {
      $editor_color_palette[$index] = array(
        'name' => $theme_vars[$color]['label'] ?: $theme_vars[$color]['control']['label'],
        'slug' => $color,
        'color' => get_theme_mod($color, $theme_vars[$color])
      )
    }
  }
  */

  // add_theme_support( 'editor-color-palette', $editor_color_palette);
});

/*
add_filter( 'style_loader_tag', function($html, $handle = null, $href = null, $media = null ) {
  echo 'ENQUEUE STYLE...' . $href . '<br/>';

  return $html;
}, 10, 4);
*/
