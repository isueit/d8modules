<?php

namespace Drupal\staff_profile_reed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'County Staff' Block.
 *
 * @Block(
 *   id = "reed_county_staff",
 *   admin_label = @Translation("County Staff"),
 *   category = @Translation("Staff Profile"),
 * )
 */
class CountyStaff extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $counties = \Drupal::service('staff_profile_reed.helper_functions')->getCountiesServed();

    $result = [
      '#type' => 'container',
      '#attributes' => ['class' => ['isueo-dashboard']],
    ];

    $result['#markup'] = '<p>These are the staff members housed in each county.</p>';

    foreach ($counties as $key => $county) {
      $result[$county->label()] = array(
        '#type' => 'fieldset',
        "#title" => $this->t($county->label())
      );
      //$result[$county->label()]['description'] = [
      //'#markup' => '<p>Could also put links or info about ' . $this->t($county->label()) . ' County here</p>',
      //];
      $result[$county->label()]['view'] = [
        '#type' => 'view',
        '#name' => 'regional_director_county',
        '#display_id' => 'county_staff',
        '#arguments' => [$county->id()],
        '#embed' => TRUE,
      ];
    }
    return $result;
  }
}
