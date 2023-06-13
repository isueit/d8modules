<?php

namespace Drupal\staff_profile_teams\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'County Staff' Block.
 *
 * @Block(
 *   id = "teams_block",
 *   admin_label = @Translation("Teams Block"),
 *   category = @Translation("Staff Profile"),
 * )
 */
class TeamsBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $result = [];

      $result['view'] = [
        '#type' => 'view',
        '#name' => 'staff_profile_teams',
        '#display_id' => 'page_1',
//        '#arguments' => 'crops',
        '#embed' => TRUE,
      ];

    return $result;
  }
}
