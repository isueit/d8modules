<?php

namespace Drupal\staff_field_specialist_map\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
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
    $colors = ['#c8102e', '#7c2529', '#4a4a4a', '#f5f5f5', '#ebebeb', '#008540', ];
    $colors = ['#c8102e', '#7c2529', '#c84a4a', '#f5c8f5', '#ebebc8', '#008540', ];
    $number_of_colors = count($colors);
    $count = 0;

    $results = '';

    // See: https://pixelthis.gr/content/drupal-9-gettings-views-custom-block-di-using-drupal-core-render-class
    $field_staff_view = \Drupal\views\Views::getView('staff_directory');
    $field_staff_view->setDisplay('block_2');
    $field_staff_view->execute();
    $view_result = $field_staff_view->result;
    $field_specialists = [];

    foreach ($view_result as $staff) {
      $field_specialists[] = intval($staff->nid);
    }


    $nodes = Drupal\node\Entity\Node::loadMultiple($field_specialists);
    $styles = $this->getDefaultStyles();

    foreach ($nodes as $node) {
      $color = $colors[$count % $number_of_colors];
      $counties_served = $node->get('field_staff_profile_cty_served');
      foreach ($counties_served as $county) {
        $styles .= '#' . $this->fixCounty($county->entity->label()) . ' polygon{fill: ' . $color . '}' . PHP_EOL;
      }
      $count = $count + 1;
    }

    $results .= '<br />' . ISUEOHelpers::map_get_svg($styles);

    //Add allowed tags for svg map
    $tags = FieldFilteredMarkup::allowedTags();
    array_push($tags, 'svg', 'g', 'polygon', 'path', 'style');


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

  private function getDefaultStyles()
  {
    $styles = '/* Added by staff_field_specialist_map */' . PHP_EOL;
    $styles .= 'svg {max-width:800px;}' . PHP_EOL;
    $styles .= 'g.RegionNumber {display:none;}' . PHP_EOL;
    $styles .= '#Region_1 polygon, #Region_2 polygon, #Region_3 polygon, #Region_4 polygon, #Region_5 polygon, #Region_6 polygon, #Region_7 polygon, #Region_8 polygon, #Region_9 polygon, #Region_10 polygon, #Region_11 polygon, #Region_12 polygon, #Region_13 polygon, #Region_14 polygon, #Region_15 polygon, #Region_16 polygon, #Region_17 polygon, #Region_18 polygon, #Region_19 polygon, #Region_20 polygon, #Region_21 polygon, #Region_22 polygon, #Region_23 polygon, #Region_24 polygon, #Region_25 polygon, #Region_26 polygon, #Region_27 polygon {fill:#ffffff; stroke:black;}' . PHP_EOL;

    return $styles;
  }

  private function fixCounty($county_name)
  {
    $county_name = str_replace(' ', '_', $county_name);
    $county_name = str_replace('\'', '', $county_name);
    $county_name = str_replace('Pottawattamie_-_West', 'West_Pottawattamie', $county_name);
    $county_name = str_replace('Pottawattamie_-_East', 'East_Pottawattamie', $county_name);

    return $county_name;
  }
}
