<?php

namespace Drupal\ts_forstaff_search\Plugin\Block;
use Drupal\Core\Field\FieldFilteredMarkup;

use Drupal\Core\Block\BlockBase;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides a 'For Staff Search Bar' Block.
 *
 * @Block(
 *   id = "ts_forstaff_search_search_bar_only",
 *   admin_label = @Translation("For Staff Search Bar Only"),
 *   category = @Translation("TS"),
 * )
 */
class TSForStaffSearchBarOnly extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {

    // Do NOT cache a page with this block on it
    //\Drupal::service('page_cache_kill_switch')->trigger();

    $results = '
      <div id="ts-forstaff-search-bar-only"></div>

      <script src="https://cdn.jsdelivr.net/npm/instantsearch.js@4.44.0"></script>
      <script src="https://cdn.jsdelivr.net/npm/typesense-instantsearch-adapter@2/dist/typesense-instantsearch-adapter.min.js"></script>
    ';

    //Add allowed tags for svg map
    $tags = FieldFilteredMarkup::allowedTags();
    array_push($tags, 'script', 'div', );

    $block = [];
    $block['#allowed_tags'] = $tags;
    $block['#markup'] = $results;
    $block['#attached']['library'][] = 'ts_forstaff_search/ts_forstaff_search_bar_only';
    return $block;
  }

  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }
}
