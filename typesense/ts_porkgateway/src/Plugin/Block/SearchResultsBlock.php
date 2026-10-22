<?php

namespace Drupal\ts_porkgateway\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Search Results Block for Porkgateway.
 *
 * @Block(
 *   id = "ts_porkgateway_searchresults",
 *   admin_label = @Translation("Search Results"),
 *   category = @Translation("Pork Gateway"),
 * )
 */
class SearchResultsBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {

    // Do NOT cache a page with this block on it
    \Drupal::service('page_cache_kill_switch')->trigger();

    return [
      '#theme' => 'ts_porkgateway_searchresults',
      '#attached' => [
        'library' => [
          'ts_porkgateway/searchresults_assets',
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

