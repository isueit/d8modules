<?php

namespace Drupal\plp_programs\Plugin\Block;

use Drupal\Core\Field\FieldFilteredMarkup;

use Drupal\Core\Block\BlockBase;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides a 'PLP Programs Search' Block.
 *
 * @Block(
 *   id = "plp_programs_search_results",
 *   admin_label = @Translation("PLP Programs Search Results"),
 *   category = @Translation("PLP"),
 * )
 */
class PLPProgramsSearchResults extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {

    // Do NOT cache a page with this block on it
    //\Drupal::service('page_cache_kill_switch')->trigger();

    $results = '
        <div class="container">
          <div id="searchbox"></div>

          <div class="search-panel__filters">
            <div id="brand"></div>
            <div id="type"></div>
            <div id="price"></div>
            <div id="price2"></div>
            <div id="category"></div>
          </div>

          <div id="hits"></div>
          <div id="pagination"></div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/algoliasearch@4/dist/algoliasearch-lite.umd.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/instantsearch.js@4"></script>
        <script src="/modules/custom/d8modules/program_landing_page/plp_programs/js/app.js" type="module"></script>
        <link href="/modules/custom/d8modules/program_landing_page/plp_programs/css/Dropdown.css" media="all" rel="stylesheet" />
        <link href="/modules/custom/d8modules/program_landing_page/plp_programs/css/app.css" media="all" rel="stylesheet" />
    ';

    //Add allowed tags for svg map
    $tags = FieldFilteredMarkup::allowedTags();
    array_push($tags, 'script', 'div', 'img', 'src', 'h3', 'link');

    $block = [];
    $block['#allowed_tags'] = $tags;
    $block['#markup'] = $results;
  //  $block['#attached']['library'][] = 'plp_programs/plp_programs_search_results';
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
