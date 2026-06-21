<?php

namespace Drupal\ts_events_details\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\isueo_helpers\ISUEOHelpers;
use Exception;
use Drupal\Core\Routing\TrustedRedirectResponse as RoutingTrustedRedirectResponse;

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
    $client = ISUEOHelpers\Typesense::getClient('events');

    try {
      $event = $client->collections['events_programs']->documents[$eventID]->retrieve();

      $element = array(
        '#theme' => 'ts_events_details',
        '#title' => $event['title'],
        '#event' => $event,
        '#attached' => ['library' => ['ts_events_details/ts_events_details']],
      );
      return $element;
    } catch (Exception $ex) {
    }

    // If we get here, it means the event wasn't in the events collection
    try {
      $past_event = $client->collections['events_programs']->documents[$eventID]->retrieve();
      $raw = ISUEOHelpers\Typesense::searchCollection('plp_programs', $past_event['Planned_Program__c'], 'field_plp_program_event_pgm_ids');
      $program = $raw['hits'][0]['document'];
      return new RoutingTrustedRedirectResponse('https://www.extension.iastate.edu' . $program['url']);
    } catch (Exception $ex) {
      return new RoutingTrustedRedirectResponse('https://www.extension.iastate.edu/calendar');
    }
  }
}
