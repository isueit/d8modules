<?php

namespace Drupal\isueo_helpers\ISUEOHelpers;

class Map {

  // Return a map as an svg file, after applying any given styles
  public static function map_get_svg($styles = '', $links = [])  {
    $mapPath = \Drupal::service('file_system')->realpath(\Drupal::service('module_handler')->getModule('isueo_helpers')->getPath()) . '/images/iowa_map.svg';
    $mapCode = file_get_contents($mapPath);
    $mapCode = str_replace('/*CustomStyles*/', $styles, $mapCode);

    foreach ($links as $key => $value) {
      $mapCode = str_replace('/*' . $key .' link*/', '<a xlink:href="' . $value . '">', $mapCode);
      $mapCode = str_replace('/*' . $key .' endlink*/', '</a>', $mapCode);
    }

    return $mapCode;
  }

  // Return a color code from an array
  public static function map_get_colorcode($count, $colors = []) {
    // Set up some initial values for variables
    if (empty($colors)) {
      $colors = ['#c8102e', '#a2a569', '#cac747', '#aca39a', '#ce7549', '#7a99ac', '#f1be48', '#318abb', '#a5463a', '#eed484', '#b9975b', '#9b945f', ];
      //$colors = ['#c8102e', '#7c2529', '#c84a4a', '#f5c8f5', '#ebebc8', '#008540', ];
    }
    $number_of_colors = count($colors);

    return $colors[$count % $number_of_colors];
  }

}
