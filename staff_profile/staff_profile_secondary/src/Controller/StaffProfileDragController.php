<?php

namespace Drupal\staff_profile_secondary\Controller;

use Drupal\Core\Controller\ControllerBase;

/*
 * Provides a search results response for module route to search-results
 */

class StaffProfileDragController extends ControllerBase {
  /*
   * @return array
   *  Returns form for results
   */
  public function formPage() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $form = \Drupal::formBuilder()->getForm('Drupal\staff_profile_secondary\Form\StaffProfileDragForm');
    // $form['form_id']['#access'] = FALSE;
    // $form['form_build_id']['#access'] = FALSE;
    // $form['form_token']['#access'] = FALSE;
    // $form['search']['search_submit']['#name'] = FALSE;
    return $form;
  }
}
