<?php

namespace Drupal\staff_field_specialist_map\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides a 'Staff Field Specialist Map' Block.
 *
 * @Block(
 *   id = "staff_field_specialist_map",
 *   admin_label = @Translation("Staff Field Specialist Map block"),
 *   category = @Translation("Staff Field Specialist Map"),
 * )
 */
class StaffFieldSpecialistMap extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // Do NOT cache a page with this block on it
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Set up some initial values for variables
    $count = 0;
    $styles = $this->getDefaultStyles();
    $links = [];
    $results = '';

    // Get a list of nodes returned by the Field Specialist View
    // See: https://pixelthis.gr/content/drupal-9-gettings-views-custom-block-di-using-drupal-core-render-class
    $field_staff_view = \Drupal\views\Views::getView('staff_directory');
    $field_staff_view->setDisplay('block_2');
    $field_staff_view->execute();
    $view_result = $field_staff_view->result;
    $field_specialists = [];

    // Put the node id's into an array then load these nodes
    foreach ($view_result as $staff) {
      $field_specialists[] = intval($staff->nid);
    }
    $nodes = Drupal\node\Entity\Node::loadMultiple($field_specialists);

    // Step through the nodes, each node is a field specialist
    foreach ($nodes as $node) {
      $color = ISUEOHelpers\Map::map_get_colorcode($count);
      $counties_served = $node->get('field_staff_profile_cty_served');

      // Step through the counties served, and add a style that fill the background for those counties
      foreach ($counties_served as $county) {
        $county_name = $this->fixCounty($county->entity->label());
        $styles .= '#' . $county_name . ' polygon{fill: ' . $color . '}' . PHP_EOL;
        $links[$county_name] = $node->toUrl()->toString();
      }
      $count = $count + 1;
    }

    // Get the map, with our custom styles applied
    $results .= '<br />' . ISUEOHelpers\Map::map_get_svg($styles, $links);

    //Add allowed tags for svg map
    $tags = FieldFilteredMarkup::allowedTags();
    array_push($tags, 'svg', 'g', 'polygon', 'path', 'style');

    // Return the results
    return [
      '#allowed_tags' => $tags,
      '#markup' => $results,
    ];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }

  // Some basic styles that should be applied to the map
  private function getDefaultStyles()
  {
    $styles = '/* Added by staff_field_specialist_map */' . PHP_EOL;
    $styles .= 'svg {max-width:800px;}' . PHP_EOL;
    $styles .= 'g.RegionNumber {display:none;}' . PHP_EOL;
    $styles .= '#Region_1 polygon, #Region_2 polygon, #Region_3 polygon, #Region_4 polygon, #Region_5 polygon, #Region_6 polygon, #Region_7 polygon, #Region_8 polygon, #Region_9 polygon, #Region_10 polygon, #Region_11 polygon, #Region_12 polygon, #Region_13 polygon, #Region_14 polygon, #Region_15 polygon, #Region_16 polygon, #Region_17 polygon, #Region_18 polygon, #Region_19 polygon, #Region_20 polygon, #Region_21 polygon, #Region_22 polygon, #Region_23 polygon, #Region_24 polygon, #Region_25 polygon, #Region_26 polygon, #Region_27 polygon { fill:#fff;stroke:black;}' . PHP_EOL;

    return $styles;
  }

  // Handle county names to match what the map uses
  private function fixCounty($county_name)
  {
    $county_name = str_replace(' ', '_', $county_name);
    $county_name = str_replace('\'', '', $county_name);
    $county_name = str_replace('Pottawattamie_-_West', 'West_Pottawattamie', $county_name);
    $county_name = str_replace('Pottawattamie_-_East', 'East_Pottawattamie', $county_name);

    return $county_name;
  }
}
