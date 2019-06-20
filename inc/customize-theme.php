<?php

/*
function get_theme_vars() {
  global $theme_vars;

  return $theme_vars;
}
*/

function register_theme_var($name, $attributes = []) {
  global $theme_vars;

  if (!isset($theme_vars)) {
    $theme_vars = [];
  }

  $theme_vars[$name] = array_merge([
    'name' => $name,
    'label' => $name,
    'type' => 'text',
    'section' => 'theme'
  ], $attributes);
}

function register_theme_vars($vars) {
  global $theme_vars;

  foreach ($vars as $name => $attributes) {
    register_theme_var($name, $attributes);
  }
}

/*
function register_theme_font($name, $attributes = []) {
  global $theme_fonts;

  if (!isset($theme_fonts)) {
    $theme_fonts = [];
  }

  $theme_vars[$name] = array_merge([
    'name' => $name
  ], $attributes);
}
*/


function load_theme_defaults() {
  global $theme_defaults;

  if (isset($theme_defaults)) {
    return $theme_defaults;
  }

  $locations = array(
    get_template_directory() . '/style.css',
    get_stylesheet_directory() . '/style.css'
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

  /*
  $theme_resources = get_theme_resources();

  echo '<br/>';
  echo '*********';
  echo '<br/>';

  foreach ($theme_resources as $src => $object) {
    echo $src;
    echo '<br/>';
    print_r($object);

    echo '<br/>';
    echo '-----';
    echo '<br/>';
  }


  exit;
  */

  $theme_defaults = load_theme_defaults();


  $fonts = get_theme_fonts();

  foreach ($theme_vars as $name => $attributes) {
    $wp_customize->add_setting($name, [
      'default' => $theme_defaults[$name] ?: $attributes['default']
    ]);

    $type = $attributes['type'];

    switch ($type) {
      case 'color':
        $wp_customize->add_control(
          new WP_Customize_Color_Control(
            $wp_customize,
            $name,
            array_merge(array(
              'label' => ($attributes['label'] ?: $name),
              'section' =>  ($attributes['section'] ?: 'theme_parameters'),
              'settings' => $name,
              'priority' => 10
            ), $attributes['control'] ?: [])
          )
        );
        break;
      case 'font':
        $wp_customize->add_control( $name, array(
          'type' => 'select',
          'section' =>  ($attributes['section'] ?: 'theme_parameters'),
          'label' => ($attributes['label'] ?: $name),
          'description' => $attributes['description'],
          'choices' => array_reduce($fonts, function($result, $current) {
            return array_merge($result, array(
              $current => __( $current ),
            ));
          }, array())
        ));

        break;
      default:
        $wp_customize->add_control(
          new WP_Customize_Control(
            $wp_customize,
            $name,
            array_merge(array(
              'label' => ($attributes['label'] ?: __($name)),
              'section' =>  ($attributes['section'] ?: 'theme_parameters'),
              'settings' => $name,
              'priority' => 10
            ), $attributes['control'] ?: [])
          )
        );
    }
  }
});

function get_theme_custom_css() {
  global $theme_vars;

  $theme_mods = get_theme_mods();

  $css = '';


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
  echo 'customize register...';
  print_r(get_theme_fonts());
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


add_action( 'after_setup_theme', function() {
  global $theme_vars;

  // echo 'after setup theme';

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


function get_theme_resources() {
  global $wp_styles;

  $resources = array();

  foreach ($wp_styles->registered as $key => $item) {

    if (!$item->src) {
      continue;
    }

    if (strpos($item->handle, 'wp-') === 0) {
      continue;
    }

    $match = preg_match('~^\/(wp-admin|wp-includes)~', $item->src);

    if ($match) {
      continue;
    }

    if (in_array('wp-edit-blocks', $item->deps)) {
      continue;
    }

    if (in_array('wp-editor', $item->deps)) {
      continue;
    }

    if (in_array('wp-admin', $item->deps)) {
      continue;
    }

    $resources[] = $item->src;

  }

  return $resources;
}


function theme_fetch_resource($url) {
  global $theme_resources;

  if (function_exists('curl_init')) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    //curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Removed
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Added
    $data = curl_exec($ch);
    curl_close($ch);
  } else {
    echo 'CUrl is not supported';
  }

  return $data;
}

function theme_parse_resource_data($content) {
  $result = array(
    'vars' => array(),
    'fonts' => array()
  );

  // Parse vars
  preg_match_all("~\s*:root\s*\{([^}]*)\s*\}~", $content, $root_decl_matches, PREG_SET_ORDER);

  foreach($root_decl_matches as $root_decl_match) {
    $decl = $root_decl_match[1];
    preg_match_all("~\s*--([a-zA-Z_-]*):([^;]*)\s*~", $decl, $var_matches, PREG_SET_ORDER);

    foreach($var_matches as $var_match) {
      $name = $var_match[1];
      $value = $var_match[2];

      $result['vars'][$name] = $value;
    }
  }

  // Parse fonts

  preg_match_all("~\s*@font-face\s*\{([^}]*)\s*\}~", $content, $font_face_matches, PREG_SET_ORDER);

  if (count($font_face_matches) > 0) {
    foreach($font_face_matches as $font_face_match) {
      preg_match_all("~\s*font-family:([^;]*)\s*~", $font_face_match[1], $font_family_matches, PREG_SET_ORDER);

      foreach($font_family_matches as $font_family_match) {
        $font_family = $font_family_match[1];

        $result['fonts'] = array_unique(array_merge($result['fonts'], array($font_family)));


      }
    }
  }

  return $result;
}

/*
add_filter( 'style_loader_src', function($html, $handle = null, $href = null, $media = null ) {
  global $theme_resources;

  echo 'style_loader_tag';

  if (!isset($theme_resources)) {
    $theme_resources = array();
  }

  $content = theme_fetch_resource($href);
  $data = theme_parse_resource_data($content);

  $theme_resources[] = array_merge(
    $data, array(
      'url' => $href
    )
  );

  return $html;
}, 11, 4);
*/

/*
add_action( 'wp_head', function() {
  global $theme_resources;
  echo 'AFTER THEME SETUP...<br/>';

  print_r(get_theme_fonts());
  return $html;
}, 11);
*/

function get_theme_fonts() {
  global $theme_resources;

  $fonts = array_reduce($theme_resources, function($result, $current) {
    return array_merge($result, $current['fonts']);
  }, array());

  return $fonts;
}

function get_theme_vars() {
  global $theme_resources;
  global $theme_vars;

  $vars = array_reduce($theme_resources, function($result, $current) {
    return array_merge($result, $current['vars']);
  }, array());

  $result = array();

  foreach ($theme_vars as $name => $attributes) {
    $result[$name] = $vars[$name] ?: $attributes['default'];
  }

  return $result;
}


add_action( 'wp_ajax_foobar', 'my_ajax_foobar_handler' );

function my_ajax_foobar_handler() {
  global $wp_styles;
    // Make your response and echo it.

    ob_start();
    wp_head();
    ob_end_clean();
    $r = get_theme_resources();

    // echo json_encode($r);
    print_r($r);
    // Don't forget to stop execution afterward.
    wp_die();
}

/*
$url = admin_url( 'admin-ajax.php' );
echo $url;
exit;
*/
