<?php

namespace Drupal\ts_events_details\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\isueo_helpers\ISUEOHelpers;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the ts_events_details  module.
 */
class EventDetailsController extends ControllerBase
{
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function ts_event_details($eventID, $eventTitle)
  {
    // Do NOT cache the events details page
    \Drupal::service('page_cache_kill_switch')->trigger();

    $raw = ISUEOHelpers\Typesense::searchCollection('events', '*', '*', '', 10, 1, 'id:' . $eventID);
    if (!$raw || $raw['found'] != 1) {
      return [
        '#title' => $eventTitle,
        '#markup' => '<p>Event details not found</p>',
      ];
    }
    $event = $raw['hits'][0]['document'];

    $element = array(
      '#theme' => 'ts_events_details',
      '#title' => $event['title'],
      '#event' => $event,
      '#attached' => ['library' => ['ts_events_details/ts_events_details']],
    );
    return $element;
  }

}
