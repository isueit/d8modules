<?php

namespace Drupal\ts_website\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\isueo_helpers\ISUEOHelpers\Typesense;

/**
 * Returns responses for my_module routes.
 */
class WebsiteInformationController extends ControllerBase
{

  /**
   * Renders the custom page layout.
   *
   * @return array
   *   A renderable array mapping to the custom theme template.
   */
  public function renderPage()
  {
    $typesense = [];
    $folder = _ts_website_get_folder();
    $drush_alias = explode('.', $folder)[0];

    $client = Typesense::getClient('websites');
    try {
      $typesense = $client->collections['websites']->documents[$drush_alias]->retrieve();

      return [
        '#theme' => 'website_information_theme',
        '#page_title' => $this->t('Website Information'),
        '#website' => $typesense,
      ];
    } catch (Exception $ex) {
      Drupal::logger('ts_website')->alert($ex->getMessage());
    }
  }
}
