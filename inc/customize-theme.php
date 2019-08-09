<?php

function get_color_by_name($name) {
  return array(
    'white' => '#ffffff',
    'black' => '#000000',
    'red' => '#ff0000',
    'yellow' => '#FFFF00',
    'green' => '#008000',
    'blue' => '#0000ff',
    'cyan' => '#00FFFF',
    'purple' => '#800080',
    'gray' => '#808080',
    'teal' => '#008080',
    'orange' => '#ffa500'
  )[$name] ?: $name;
}

function adjustBrightness($hexCode, $adjustPercent) {
  if (strpos($hexCode, 'rgb') === 0) {
    $rgb_string = preg_replace('~rgba?\s*\(\s*(.*)\s*\)~', '$1', $hexCode);
    $rgb = array_map('trim', array_slice(explode(',', $rgb_string), 0, 3));
  } else {

    $hexCode = get_color_by_name($hexCode);

    if (strpos($hexCode, '#') === 0) {
      $hexCode = ltrim($hexCode, '#');
      $normalized_percent = $adjustPercent / 100;

      if (strlen($hexCode) == 3) {
        $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
      }

      $rgb = array_map('hexdec', str_split($hexCode, 2));
    }
  }

  foreach ($rgb as & $color) {
    $adjustableLimit = $normalized_percent < 0 ? $color : 255 - $color;
    $adjustAmount = ceil($adjustableLimit * $normalized_percent);

    $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
  }

  return '#' . implode($rgb);
}


function register_theme_option($name, $attributes = []) {
  global $theme_options;

  if (!isset($theme_options)) {
    $theme_options = [];
  }

  $theme_options[$name] = array_merge([
    'name' => $name,
    'label' => $name,
    'type' => 'string',
    'section' => null
  ], $attributes);
}

function register_theme_options($options) {
  foreach ($options as $name => $attributes) {
    register_theme_option($name, $attributes);
  }
}

function register_theme_var($name, $attributes = []) {
  global $theme_vars;

  if (!isset($theme_vars)) {
    $theme_vars = [];
  }

  $theme_vars[$name] = array_merge([
    'name' => $name,
    'label' => $name,
    'type' => 'string',
    'section' => null
  ], $attributes);
}

function register_theme_vars($vars) {
  foreach ($vars as $name => $attributes) {
    register_theme_var($name, $attributes);
  }
}

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

function get_theme_fonts_x() {
  global $theme_fonts;

  if (isset($theme_fonts)) {
    return $theme_fonts;
  }

  $theme_fonts = array();
  $theme_resources = get_theme_resources();

  foreach ($theme_resources as $theme_resource) {
    $theme_fonts = array_unique(array_merge($theme_resource['fonts'], $theme_fonts));
  }

  return $theme_fonts;
}

add_action('customize_register', function($wp_customize) {
  global $theme_options;

  $fonts = get_theme_fonts_x();
  $theme_resources = get_theme_resources();
  $defaults = get_theme_defaults();

  $pattern = '~^\s*var\s*\(\s*--([a-zA-Z_-]*)\s*\)\s*$~';

  foreach ($theme_options as $name => $attributes) {
    $default = $defaults[$name] ?: $attributes['default'];

    $c = 0;
    while (preg_match($pattern, $default, $matches))  {
      $default = $defaults[$matches[1]] ?: $attributes['default'];
      $c++;

      if ($c > 10) {
        break;
      }
    }

    if ($attributes['type'] === 'color') {
      $default = get_color_by_name($default);
    }

    if ($attributes['type'] === 'font') {
      $default = trim(array_slice(explode(',', $default), 0, 1)[0], '\'" ');
    }

    $wp_customize->add_setting($name, [
      'default' => $default
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
  $theme_vars = get_theme_vars();
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


function enqueue_theme_custom_css() {
  if (wp_doing_ajax() && $_GET['action'] === 'theme_resources') {
    return;
  }

  $css = x_get_theme_custom_css();

  wp_register_style( 'kicks-app-custom-style', false );
  wp_enqueue_style( 'kicks-app-custom-style' );
  wp_add_inline_style('kicks-app-custom-style', $css );
}

add_action( 'wp_enqueue_scripts', 'enqueue_theme_custom_css', 100);
add_action( 'enqueue_block_editor_assets', 'enqueue_theme_custom_css', 100);


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


function load_theme_resources() {
  global $wp_styles;

  $resource_links = array();

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

    $resource_links[] = $item->src;

  }

  $resources = array();

  foreach ($resource_links as $url) {
    $content = theme_fetch_resource($url);
    $data = theme_parse_resource_data($content);

    $resources[] = array_merge(
      $data,
      array(
        'url' => $url
      )
    );
  }

  return $resources;
}


function theme_fetch_resource($url) {
  global $theme_resources;

  $local_directory_uris = array(
    [ get_stylesheet_directory_uri(), get_stylesheet_directory() ],
    [ get_template_directory_uri(), get_template_directory() ]
  );

  $local_files = array_map(
    function($item) use($url) {
      return $item[1] . substr($url, strlen($item[0]));
    },
    array_values(
        array_filter($local_directory_uris, function($item) use ($url) {
        return (substr($url, 0, strlen($item[0])) === $item[0]);
      })
    )
  );
  $local_file = $local_files[0];

  if ($local_file) {
    ob_start();
    include $local_file;
    $content = ob_get_contents();
    ob_end_clean();

    if ($content) {
      return $content;
    }
  }

  if (function_exists('curl_init')) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    //curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Removed
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Added
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
  } else {
    echo 'CUrl is not supported';
  }

  return null;
}

function theme_parse_resource_data($content) {
  $result = array(
    'vars' => array(),
    'fonts' => array()
  );

  // Parse vars
  preg_match_all("~\s:root\s*\{([^}]*)\s*\}~", $content, $root_decl_matches, PREG_SET_ORDER);

  foreach($root_decl_matches as $root_decl_match) {
    $decl = $root_decl_match[1];
    preg_match_all("~\s*--([a-zA-Z_-]*):\s*([^;]*)\s*~", $decl, $var_matches, PREG_SET_ORDER);

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
      preg_match_all("~\s*font-family:\s*([^;]*)\s*~", $font_face_match[1], $font_family_matches, PREG_SET_ORDER);

      foreach($font_family_matches as $font_family_match) {
        $font_family = trim($font_family_match[1], ' \'"');

        $result['fonts'] = array_unique(array_merge($result['fonts'], array($font_family)));
      }
    }
  }

  return $result;
}

function get_theme_fonts() {
  global $theme_resources;

  $fonts = array_reduce($theme_resources, function($result, $current) {
    return array_merge($result, $current['fonts']);
  }, array());

  print_r($fonts);
  exit;

  return $fonts;
}


function get_theme_vars() {
  global $theme_resources;
  global $theme_vars;

  if (!isset($theme_resources) || !$theme_resources) {
    $theme_resources = array();
  }

  $vars = array_reduce($theme_resources, function($result, $current) {
    return array_merge($result, $current['vars']);
  }, array());

  $result = array();

  foreach ($theme_vars as $name => $attributes) {
    $result[$name] = $vars[$name] ?: $attributes['default'];
  }

  return $result;
}


function get_theme_resources() {
  global $theme_resources;

  if (isset($theme_resources)) {
    return $theme_resources;
  }

  $url = admin_url( 'admin-ajax.php' ) . '?action=theme_resources';
  $urlinfo = parse_url($url);

  $url = $urlinfo['scheme'] . '://' . $_SERVER['SERVER_ADDR'] . $urlinfo['path'] . ($urlinfo['query'] ? '?' . $urlinfo['query'] : '');


  if (function_exists('curl_init')) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    // curl_setopt($ch, CURLOPT_TIMEOUT, 100); // Removed
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Added
    $content = curl_exec($ch);

    if ($content === false) {
      echo 'Curl-Error: ' . curl_error($ch);
      exit;
    }

    curl_close($ch);
  } else {
    echo 'Curl is not supported';
    exit;
  }

  if ($content) {
    $theme_resources = json_decode($content, true);
  } else {
    $theme_resources = array();
  }

  return $theme_resources;
}

add_action( 'wp_ajax_nopriv_theme_resources', function() {
  global $wp_styles;
    // Make your response and echo it.


    ob_start();
    wp_head();
    ob_end_clean();

    $resources = load_theme_resources();

    $output = json_encode($resources);

    header('Content-Type: application/json');

    echo $output;

    // Don't forget to stop execution afterward.
    wp_die();
});


function load_theme_manifest($file) {
  ob_start();
  include $file;
  $content = ob_get_contents();
  ob_end_clean();

  if ($content) {
    return json_decode($content, true);
  }

  return array();
}

function get_theme_defaults() {
  $theme_resources = get_theme_resources();

  $defaults = array();
  foreach ($theme_resources as $theme_resource) {
    /*
    echo 'GET DEFAULT VaLUES: ' . $theme_resource['url'];
    echo '<br/>';
    */
    $defaults = array_merge($defaults, $theme_resource['vars']);
  }

  return $defaults;
}

function x_get_theme_vars() {
  global $theme_vars;


  $defaults = get_theme_defaults();
  /*
  echo '<pre>';
  var_dump($defaults);

  echo '</pre>';

  exit;*/

  $result = array();


  foreach ($theme_vars as $key => $data) {
    // $value = isset($data['value']) ? $data['value'] : $data['default'];
    $value = isset($defaults[$key]) ? $defaults[$key] : $data['value'];
    $implicit = isset($data['implicit']) ? $data['implicit'] : false;

    if (!$implicit) {
      $value = get_theme_mod($key, $value);
      $result[$key] = $value;
    }
  }

  $pattern = '~^\s*var\s*\(\s*--([a-zA-Z_-]*)\s*\)\s*$~';

  foreach ($theme_vars as $key => $data) {
    $implicit = isset($data['implicit']) ? $data['implicit'] : false;

    if ($implicit) {
      $source_var = $data['source'];
      $source_default = $theme_vars[$source_var]['default'];
      $source_value = $result[$source_var];

      /*
      echo 'IMPLICIT VAR: ' . $key . ' SOURCE VAR: ' . $source_var . ' VALUE: ' . $source_value;
      echo '<br/>';
      */
      $c = 0;

      while (preg_match($pattern, $source_value, $matches))  {
        $source_var = $matches[1];
        $source_value = $result[$source_var] ?: $source_value;
        $source_default = $theme_vars[$source_var]['default'] ?: $source_default;

        /*
        $c++;

        if ($c > 10) {
          echo 'BREAK';
          break;
        }
        */
      }

      if ($data['filter']) {
        $value = apply_filters(
          'theme_implicit_' . $data['filter']['name'],
          $source_value,
          $data['filter']['amount'],
          $data,
          $data['source']
        );
      }
      $result[$key] = $value;
    }
  }


  return $result;
}

$manifest = load_theme_manifest(get_template_directory() . '/dist/bootstrap.css.json');

foreach ($manifest as $key => $value) {
  # code...
  register_theme_var($key, $value);

}




/*
$url = admin_url( 'admin-ajax.php' );
echo $url;
exit;
*/


add_filter('theme_implicit_darken', function($value, $amount, $data, $source) {
  return adjustBrightness($value, intval($amount) * -1);
}, 10, 4);

add_filter('theme_implicit_lighten', function($value, $amount, $data, $source) {
  return adjustBrightness($value, intval($amount));
}, 10, 4);


function x_get_theme_custom_css() {
  $theme_vars = x_get_theme_vars();


  /*
  echo '<pre>';
  var_dump($theme_vars);

  echo '</pre>';
  exit;
  */

  $css = <<<EOT
:root {

EOT;
  foreach ($theme_vars as $name => $value) {
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
