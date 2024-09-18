<?php

namespace Drupal\staff_profile_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides a 'Staff Profile Map' Block.
 *
 * @Block(
 *   id = "staff_profile_map",
 *   admin_label = @Translation("Staff Profile Map block"),
 *   category = @Translation("Staff Profile Map"),
 * )
 */
class StaffProfileMap extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    //Initialize some variables
    $results = '';
    $config = $this->getConfiguration();
    $styles = $this->default_styles($config['max_size']);
    $displayMap = false;

    //Make sure we're on a node
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      //Handle Counties Served
      foreach ($node->get('field_staff_profile_cty_served') as $ctyServed) {
        $county_name = $ctyServed->entity->label();
        if (!empty($county_name)) {
          $styles .= '#' . $this->fixCounty($county_name) . ' polygon {fill:' . $config['served_color'] . '}' . PHP_EOL;
          $styles .= '#' . $this->fixCounty($county_name) . ' g.CountyName {display:inline;}' . PHP_EOL;
          $displayMap = true;
        }
      }

      //Handle Base County
      if ($node->get('field_staff_profile_base_county')->entity != null) {
        $base_county = $node->field_staff_profile_base_county->entity->label();
        if (!empty($base_county)) {
          $styles .= '#' . $this->fixCounty($base_county) . ' polygon {fill:' . $config['base_color'] . ';}' . PHP_EOL;
          $styles .= '#' . $this->fixCounty($county_name) . ' g.CountyName {display:inline;}' . PHP_EOL;
          $displayMap = true;
        }
      }
    }

    //Add allowed tags for svg map
    $tags = FieldFilteredMarkup::allowedTags();
    array_push($tags, 'svg', 'g', 'polygon', 'path', 'style');

    //Add the map to results when appropriate
    if ($displayMap) {
      $results .= ISUEOHelpers\Map::map_get_svg($styles);
    }

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

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $config = $this->getConfiguration();

    $form['max_size'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum Width of Map, in pixels'),
      //'#description' => t('Zero (0) means display all events'),
      '#size' => 15,
      '#default_value' => $config['max_size'],
    );

    $form['base_color'] = array(
      '#type' => 'textfield',
      '#title' => t('Color to shade the Base County'),
      //'#description' => t('Zero (0) means display all events'),
      '#size' => 15,
      '#default_value' => $config['base_color'],
    );

    $form['served_color'] = array(
      '#type' => 'textfield',
      '#title' => t('Color to shade the Counties Served'),
      //'#description' => t('Zero (0) means display all events'),
      '#size' => 15,
      '#default_value' => $config['served_color'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();

    $this->configuration['max_size'] = $values['max_size'];
    $this->configuration['base_color'] = $values['base_color'];
    $this->configuration['served_color'] = $values['served_color'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return array(
      'max_size' => '400',
      'base_color' => '#CC0000',
      'served_color' => '#F1BE48',

    );
  }

  private function default_styles($max_size)
  {
    $return_string = '';


    $return_string .= '/* Added for Staff Directory Fill */' . PHP_EOL;

    $return_string .= 'svg {max-width:' . $max_size . 'px;}' . PHP_EOL;
    $return_string .= 'g.RegionNumber {display:none;}' . PHP_EOL;
    $return_string .= 'g.CountyName {display:none;}' . PHP_EOL;
    $return_string .= '#Region_1 polygon, #Region_2 polygon, #Region_3 polygon, #Region_4 polygon, #Region_5 polygon, #Region_6 polygon, #Region_7 polygon, #Region_8 polygon, #Region_9 polygon, #Region_10 polygon, #Region_11 polygon, #Region_12 polygon, #Region_13 polygon, #Region_14 polygon, #Region_15 polygon, #Region_16 polygon, #Region_17 polygon, #Region_18 polygon, #Region_19 polygon, #Region_20 polygon, #Region_21 polygon, #Region_22 polygon, #Region_23 polygon, #Region_24 polygon, #Region_25 polygon, #Region_26 polygon, #Region_27 polygon {fill:#ffffff; stroke:black;}' . PHP_EOL;

    return $return_string;
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
