<?php

require_once 'controls/Theme_Customize_Color_Control.php';
require_once 'controls/Theme_Customize_Control.php';
//


function themalize_humanize($str) {
	$str = trim(strtolower($str));
	$str = preg_replace('/[^a-z0-9\s+]/', ' ', $str);
	$str = preg_replace('/\s+/', ' ', $str);
	$str = explode(' ', $str);
	$str = array_map('ucwords', $str);

	return implode(' ', $str);
}

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

function get_color_value($value) {
	if (preg_match('~^\s*var\(\s*--([a-zA-Z_-]+)\s*\)\s*$~', $value)) {
		return $value;
	}
	$value = get_color_by_name($value);
	$value = preg_replace('~rgba\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*1\s*\)~', 'rgb($1, $2, $3)', $value);

	if (preg_match('~rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)~is', $value, $output, PREG_SET_ORDER)) {
		$matches = $output[0];
		$value = call_user_func_array('sprintf', [
			"#%02x%02x%02x", $matches[1], $matches[2], $matches[3]
		]);
	}

	return $value;
}

function get_font_value($value) {
	if (preg_match('~^\s*var\(\s*--([a-zA-Z_-]+)\s*\)\s*$~', $value)) {
		return $value;
	}

	$fonts = explode(',', $value);
	$fonts = array_map(function($string) {
		return trim($string, '\'" ');
	}, $fonts);

	return $fonts[0];
}

function adjustBrightness($hexCode, $adjustPercent) {
	$hexCode = get_color_value($hexCode);

  if (strpos($hexCode, 'rgb') === 0) {
    $rgb_string = preg_replace('~rgba?\s*\(\s*(.*)\s*\)~', '$1', $hexCode);
    $rgb = array_map('trim', array_slice(explode(',', $rgb_string), 0, 3));
  } else {
    if (strpos($hexCode, '#') === 0) {
      $hexCode = ltrim($hexCode, '#');
      $normalized_percent = $adjustPercent / 100;

      if (strlen($hexCode) == 3) {
        $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
      }

      $rgb = array_map('hexdec', str_split($hexCode, 2));
    }
  }

  if (!is_array($rgb)) {
    return '';
  }

  foreach ($rgb as & $color) {
    $adjustableLimit = $normalized_percent < 0 ? $color : 255 - $color;
    $adjustAmount = ceil($adjustableLimit * $normalized_percent);

    $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
  }

  return '#' . implode($rgb);
}


function register_theme_option($name, $attributes = []) {
  // echo 'REGISTER THEME OPTION ' . $name . '<br/>';
  global $theme_options;

  if (!isset($theme_options)) {
    $theme_options = [];
  }

  $type = $attributes['type'];

  $theme_options[$name] = array_merge([
    'name' => $name,
    'type' => $type
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
    // 'label' => $name,
    'type' => 'string',
    'section' => null
  ], $attributes);
}

function register_theme_vars($vars) {
  foreach ($vars as $name => $attributes) {
    register_theme_var($name, $attributes);
  }
}

function get_theme_fonts_x() {
  global $theme_fonts;

  if (isset($theme_fonts)) {
    return $theme_fonts;
  }

  $theme_fonts = array();
  $theme_resources = get_theme_resources() ?: array();

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
    $default = $defaults[$name] ?: $attributes['value'];
		$default_value = $default ?: $attributes['default'];

    $c = 0;
    while (preg_match($pattern, $default, $matches))  {
			$next_name = $matches[1];
			if ($defaults[$next_name]) {
				$default = $defaults[$next_name];
			}
      $c++;

      if ($c > 10) {
        break;
      }
    }

    $type = $attributes['type'];
    $value = $default ?: $attributes['value'];
		$value = trim($value);

    if ($type === 'color') {
			$value = get_color_value($value);
      $default_value = get_color_value($default_value);
    }

    if ($type === 'font') {
			$value = get_font_value($value);
      $default_value = get_font_value($default_value);
    }

		$is_reference = preg_match('~^\s*var\(\s*--([a-zA-Z_-]+)\s*\)\s*$~', $value, $matches);

		if ($is_reference) {
			$reference = $matches[1];
			$reference_label = themalize_humanize($reference);
		}

		$setting_default = $is_reference ? $value : $default_value;

		// echo '----> REGISTER THEME OPTION:' . $name . '  VALUE: ' . $value . ' DEFAULT: ' . $default;
    // // print_r($attributes);
    // echo '<br/>';

    $setting = [
      'default' => $setting_default
    ];

    $wp_customize->add_setting($name, $setting);


    $label = $attributes['label'] ?: themalize_humanize($name);
    $description = $attributes['description'];

    $sections = [
      'components' => [
        'pattern' => '~^(?:nav|jumbotron|pagination|border|custom-|component|blockquote|btn|dropdown|modal|navbar|popover|progress|tooltip|badge|toast|card|alert|list|thumbnail|breadcrumb|carousel|spinner|caret)~'
      ],
			'layout' => [
        'pattern' => '~^(?:grid|spacer)~'
      ],
			'content' => [
        'pattern' => '~^(?:table|spacer|hr|mark|kbd|code|pre)~'
      ],
      'forms' => [
        'pattern' => '~(?:form|^input|^label)~'
      ],
      'typography' => [
        'pattern' => '~(?:font-|^link|^paragraph|^headings|^display|^text)~',
        'type' => 'font'
      ],
      'colors' => [
        'type' => 'color'
      ]
    ];

    $section = $attributes['section'];

    if (!$section) {
      $section = array_keys(array_filter($sections, function($section) use ($attributes) {
        if (isset($section['pattern']) && preg_match($section['pattern'], $attributes['name'])) {
          return true;
        }
        if (isset($section['type']) && $attributes['type'] === $section['type']) {
          return true;
        }
      }))[0] ?: 'uncategorized';
    }



		$args = array_merge(array(
			'label' => $label,
			'section' =>  $section,
			'settings' => $name,
			'priority' => 0
		), $attributes['control'] ?: [], [
			'type' => $type === 'color' ? 'var_color' : 'variable'
		]);

		$default_label = $default . ' (' . ($is_reference ? $reference_label : 'Default') . ')';

    switch ($type) {
      case 'color':

        $control = new Theme_Customize_Color_Control(
          $wp_customize,
          $name,
          $args
        );

        $wp_customize->add_control($control);
        break;
      case 'font':


        // echo 'REGISTER THEME CONTROL:' . $label . '  TYPE: ' . $type . ' SECTION: ' . $section . ' VALUE: ' . $value;
        // // print_r($attributes);
        // echo '<br/>';


				// print_r($fonts);
				// exit;



				$control = new Theme_Customize_Control(
          $wp_customize,
          $name,
          array_merge(
						$args,
						[
							'choices' => array_reduce($fonts, function($result, $current) {
		            return array_merge($result, array(
		              $current => __( $current ),
		            ));
		          }, array(
								'' => $default_label
							))
						]
					)
        );

        $wp_customize->add_control($control);
        break;
      default:
        $wp_customize->add_control(
          new Theme_Customize_Control(
            $wp_customize,
            $name,
            $args
          )
        );
    }
  }
});

function enqueue_theme_custom_css() {
  if (wp_doing_ajax() && $_GET['action'] === 'theme_resources') {
    return;
  }

  $css = x_get_theme_custom_css();

  wp_register_style( 'kicks-app-custom-style', false );
  wp_enqueue_style( 'kicks-app-custom-style' );

  if ($css) {
    wp_add_inline_style('kicks-app-custom-style', $css );
  }
}

add_action( 'wp_enqueue_scripts', 'enqueue_theme_custom_css', 99);
add_action( 'enqueue_block_editor_assets', 'enqueue_theme_custom_css', 99);

add_action('admin_init', function() {
	global $theme_vars;

  $url = get_template_directory_uri();

  wp_register_script('themalize', $url . '/lib/js/themalize.js', array('jquery', 'wp-color-picker'));

	wp_localize_script('themalize', 'ThemalizeSettings', [
		'theme_vars_url' => admin_url( 'admin-ajax.php' ) . '?action=theme_vars',
		'theme_vars' => json_encode($theme_vars)
	]);

  enqueue_theme_custom_css();
}, 100);

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

	$wp_customize->add_section('content', array(
    'title' => 'Content',
    'priority' => 100
  ));

	$wp_customize->add_section('layout', array(
    'title' => 'Layout',
    'priority' => 100
  ));

  $wp_customize->add_section('typography', array(
    'title' => 'Typography',
    'priority' => 100
  ));

  $wp_customize->add_section('uncategorized', array(
    'title' => 'Uncategorized',
    'priority' => 100
  ));

  $wp_customize->register_control_type( 'Theme_Customize_Color_Control' );
	$wp_customize->register_control_type( 'Theme_Customize_Control' );
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
    // echo 'fetch local resource...' . $local_file;
    // echo '<br/>';

    if (!file_exists($local_file)) {
      echo 'FILE NOT FOUND';
      exit;
    }

    ob_start();
    include $local_file;
    $content = ob_get_contents();
    ob_end_clean();

    if ($content) {
      // echo ' ----> SUCCESS <br/>';
      // echo substr($content, 0, 100);
      // echo '<br/>';
      return $content;
    }

    echo 'NO CONTENT IN FILE';
    exit;
  }

  if (function_exists('curl_init')) {

    // echo 'fetch remote resource...' . $url;
    // echo '<br/>';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    //curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Removed
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Added
    $content = curl_exec($ch);

    // if ($content) {
    //   echo ' ----> SUCCESS <br/>';
    // }

    curl_close($ch);

    return $content;
  } else {
    echo 'Curl is not supported';
    exit;
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

  // echo 'VARS: ' . count(array_keys($result['vars']));
  // echo '<br/>';

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

  /*

  $host = $_SERVER['SERVER_ADDR'];

  $url = $urlinfo['scheme'] . '://' . $host . $urlinfo['path'] . ($urlinfo['query'] ? '?' . $urlinfo['query'] : '');

  echo $url;
  echo '<br/>';
  */

  $host = $_SERVER['SERVER_NAME'];
  $url = $urlinfo['scheme'] . '://' . $host . $urlinfo['path'] . ($urlinfo['query'] ? '?' . $urlinfo['query'] : '');

  /*


  echo $url;
  exit;
  */

  if (function_exists('curl_init')) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Themalizer/0.1 (http://www.googlebot.com/bot.html)');
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

function ajax_theme_resources() {
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
}

add_action( 'wp_ajax_nopriv_theme_resources', 'ajax_theme_resources');
add_action( 'wp_ajax_theme_resources', 'ajax_theme_resources');

add_action( 'wp_ajax_theme_vars', function() {
	$data = isset($_POST['data']) ? $_POST['data'] : array();

	$theme_vars = x_get_theme_vars($data);

	$output = json_encode($theme_vars);

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
    $manifest = json_decode($content, true);

		foreach ($manifest as $key => $value) {
		  register_theme_var($key, $value);

		  if (!isset($value['implicit']) || !$value['implicit']) {
		    register_theme_option($key, $value);
		  }
		}


		return $manifest;
  }

  return array();
}

function get_theme_defaults() {
  $theme_resources = get_theme_resources() ?: array();

  $defaults = array();
  foreach ($theme_resources as $theme_resource) {
    $defaults = array_merge($defaults, $theme_resource['vars']);
  }

  return $defaults;
}

function x_get_theme_vars($custom = array()) {
  global $theme_vars;

  $defaults = get_theme_defaults();
  $result = array();

  // Process regular vars
	foreach ($theme_vars as $key => $data) {
    // $value = isset($data['value']) ? $data['value'] : $data['default'];
    $value = isset($defaults[$key]) ? $defaults[$key] : $data['value'];
    $implicit = isset($data['implicit']) ? $data['implicit'] : false;

    if ($value && !$implicit) {
      try {
        // We cannot process url for some reason
        if (preg_match('~url\s*\(~', $value)) {
          continue;
        }

        // Go ahead

				// Custom vars override theme mods
				if (isset($custom[$key])) {
					// echo 'CUSTOM KEY: ' . $key;
					// echo 'CUSTOM VALUE: ' . $custom[$key];
					$value = $custom[$key] ?: $value;
				} else {
					$value = get_theme_mod($key, $value);
				}

        if ($value) {
          $result[$key] = $value;
        }

      } catch (Exception $e) {
        echo 'Exception: ',  $e->getMessage(), "\n";
        exit;
      }
    }
  }

  // Process implicit vars
  $pattern = '~^\s*var\s*\(\s*--([a-zA-Z_-]*)\s*\)\s*$~';

  foreach ($theme_vars as $key => $data) {
    $implicit = isset($data['implicit']) ? $data['implicit'] : false;

    if ($implicit) {
      $source_var = $data['source'];
      $source_default = $theme_vars[$source_var]['default'];
      $source_value = $result[$source_var];

      while (preg_match($pattern, $source_value, $matches))  {
        $source_var = $matches[1];
        $source_value = $result[$source_var] ?: $source_value;
        $source_default = $theme_vars[$source_var]['default'] ?: $source_default;
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
    if (preg_match('~url\s*\(~', $value)) {
      continue;
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




//
// add_filter('astra_color_palettes', 'intelliwolf_custom_palettes');
//
// function intelliwolf_custom_palettes($palettes) {
//   $palettes = array(
//     '#0000ff',
//     '#FFFFFF',
//     '#F1C40F',
//     '#666A86',
//     '#C5AFA4',
//     '#CC7E85',
//     '#CF4D6F',
//     '#8FA998',
//     '#666A86',
//     '#C5AFA4',
//     '#CC7E85',
//     '#CF4D6F',
//     '#8FA998'
//   );
//   return $palettes;
// }


load_theme_manifest(get_template_directory() . '/dist/bootstrap.css.json');
