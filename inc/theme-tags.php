<?php

function theme_get_icon($name) {
  return apply_filters('theme_icon_html', sprintf('<i class="icon icon-%s"></i>', $name), $name);
}


function theme_icon($name) {
  echo theme_get_icon($name);
}
