<?php

namespace Drupal\ts_events_search\Plugin\Block;

use Drupal\Core\Field\FieldFilteredMarkup;

use Drupal\Core\Block\BlockBase;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides a 'Typesense Events Search' Block.
 *
 * @Block(
 *   id = "ts_events_search_results",
 *   admin_label = @Translation("Typesense Events Search Results"),
 *   category = @Translation("TS"),
 * )
 */
class TSEventsSearchResults extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {

    // Do NOT cache a page with this block on it
    //\Drupal::service('page_cache_kill_switch')->trigger();

    $results = '
          <div class="search-results">
          <div class="search-results-facets-outer_wrapper">
          <div class="search-results-facets-inner_wrapper">
          <div class="search-results-bar-wrapper">
          <div id="search-results-bar"></div>
          </div>
            <div class="search-results-facets">
              <div id="county"></div>
              <div id="audience"></div>
              <div id="categories"></div>
              <div id="topics"></div>
              <div id="program-unit"></div>
            </div>
            </div>
            <div id="current-refinements"></div>
            </div>
            <div class="search-results-snipets">
              <div id="stats"></div>
              <div id="hits"></div>
            </div>
          </div>

        <script src="https://cdn.jsdelivr.net/npm/typesense-instantsearch-adapter@2/dist/typesense-instantsearch-adapter.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/instantsearch.js@4"></script>
    ';

    //Add allowed tags for svg map
    $tags = FieldFilteredMarkup::allowedTags();
    array_push($tags, 'script', 'div', 'img', 'src', 'h3', 'link');

    $block = [];
    $block['#allowed_tags'] = $tags;
    $block['#markup'] = $results;
    $block['#attached']['library'][] = 'ts_events_search/ts_events_search_results';
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

