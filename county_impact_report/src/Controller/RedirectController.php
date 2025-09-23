<?php

namespace Drupal\county_impact_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines MyController class.
 */
class RedirectController extends ControllerBase {

  /**
   * Displays a custom page.
   *
   * @return array
   *   A renderable array.
   */
  public function content() {
      $nids = \Drupal::entityQuery('node')
        ->accessCheck(true)
        ->condition('type', 'county_impact_report')
        ->condition('status', 1) // Only published nodes
        ->sort('created', 'DESC') // Order by creation date, descending
        //->range(0, 1) // Get only the first (latest) one
        ->execute();

      if (!empty($nids)) {
        $latest_nid = reset($nids); // Get the first NID from the result set
        $node = Node::load($latest_nid);
        return New RedirectResponse($node->toUrl()->toString());
      }

    return [
      '#type' => 'markup',
      '#cache' => ['max-age' => 0], // Prevents caching for this render array
      '#markup' => $this->t('Unable to find current impact report'),
    ];
  }

}
