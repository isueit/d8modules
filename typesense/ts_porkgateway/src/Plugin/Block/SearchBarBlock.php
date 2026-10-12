<?php

namespace Drupal\ts_porkgateway\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Search Bar Block for Porkgateway.
 *
 * @Block(
 *   id = "ts_porkgateway_searchbar",
 *   admin_label = @Translation("Search Bar"),
 *   category = @Translation("Pork Gateway"),
 * )
 */
class SearchBarBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {

    // Do NOT cache a page with this block on it
    \Drupal::service('page_cache_kill_switch')->trigger();

    return [
      '#theme' => 'ts_porkgateway_searchbar',
      '#attached' => [
        'library' => [
          'ts_porkgateway/searchbar_assets',
        ],
      ],
    ];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }
}
