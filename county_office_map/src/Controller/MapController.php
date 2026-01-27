<?php

namespace Drupal\county_office_map\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for county office map.
 */
class MapController extends ControllerBase {

  /**
   * Display the interactive map.
   */
  public function view() {
    // Load SVG file
    $module_path = \Drupal::service('extension.list.module')->getPath('county_office_map');
    $svg_path = DRUPAL_ROOT . '/' . $module_path . '/assets/iowa-map.svg';
    $map_svg = file_exists($svg_path) ? file_get_contents($svg_path) : '';
    
    return [
      '#theme' => 'county_office_map',
      '#map_svg' => $map_svg,
      '#counties' => $counties,
      '#regions' => $regions,
      '#attached' => [
        'library' => ['county_office_map/map'],
        'drupalSettings' => [
          'countyOfficeMap' => [
          ],
        ],
      ],
    ];
  }

}