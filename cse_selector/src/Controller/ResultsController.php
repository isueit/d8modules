<?php

namespace Drupal\cse_selector\Controller;
use Drupal\Core\Controller\ControllerBase;

/*
 * Provides a search results response for module route to search-results
 */

class ResultsController extends ControllerBase {
  /*
   * @return array
   *  Returns form for results
   */
  public function resultsPage() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $form['Search'] = \Drupal::formBuilder()->getForm('Drupal\cse_selector\Form\CSESearchForm');
    $form['Results'] = \Drupal::formBuilder()->getForm('Drupal\cse_selector\Form\ResultsForm');
    $form['Results']['form_id']['#access'] = FALSE;
    $form['Results']['form_build_id']['#access'] = FALSE;
    $form['Results']['form_token']['#access'] = FALSE;
    $form['Results']['search']['search_submit']['#name'] = FALSE;
    return $form;
  }
}
