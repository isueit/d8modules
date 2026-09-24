<?php

namespace Drupal\ts_events_details\Controller;

use DateTime;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\isueo_helpers\ISUEOHelpers;
use Exception;
use Drupal\Core\Routing\TrustedRedirectResponse as RoutingTrustedRedirectResponse;
use Drupal\isueo_helpers\Drush\Commands\ISUEOHelpersCommands;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
    // Make sure we have the right URL
    /*
    // This isn't working correctly, so commenting it out for now
    // It's close, but not quite working
    $current_url = strtolower(\Drupal::request()->getRequestUri());
    $is_main_extension_site = ISUEOHelpers\General::is_main_extension_site();
    if ($is_main_extension_site && !str_starts_with($current_url, '/calendar/')) {
      // This goes to the main calendar page, like the event isn't found
      echo (Url::fromUserInput('/calendar' . $current_url)->toString());
      return new RedirectResponse(Url::fromUserInput('/calendar' . $current_url)->toString());
    } elseif (!$is_main_extension_site && str_starts_with($current_url, '/calendar/')) {
      echo (Url::fromUserInput(substr($current_url, 9))->toString());
      return new RedirectResponse(Url::fromUserInput(substr($current_url, 9))->toString());
    }
    */

    // Do NOT cache the events details page
    \Drupal::service('page_cache_kill_switch')->trigger();
    $client = ISUEOHelpers\Typesense::getClient('events');

    try {
      $event = $client->collections['events']->documents[$eventID]->retrieve();

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

/**
 * Downloads an ICS file for a specific event.
 *
 * @param string $eventID
 *   The event ID.
 *
 * @return \Symfony\Component\HttpFoundation\Response
 *   The ICS file response.
 */
public function download_ics($eventID)
{
  // Do NOT cache the ICS download
  \Drupal::service('page_cache_kill_switch')->trigger();
    $client = ISUEOHelpers\Typesense::getClient('events');

    try {
      $event = $client->collections['events']->documents[$eventID]->retrieve();
    } catch (Exception $ex) {
    throw new NotFoundHttpException();
    }

    /*
    // If we get here, it means the event wasn't in the events collection
  $module_config = \Drupal::config('program_offering_blocks.settings');
  $buffer = ISUEOHelpers\Files::fetch_url($module_config->get('url'), true);
  $program_offerings = json_decode($buffer, TRUE);

  $event = NULL;
  foreach ($program_offerings as $offering) {
    if ($offering['id'] == $eventID || (strlen($eventID) < 10 && trim(trim($offering['Ungerboeck_Event_ID__c']), "0") == trim(trim($eventID), "0"))) {
      $event = $offering;
      break;
    }
  }

  if (!$event) {
    throw new NotFoundHttpException();
  }
*/

  // Get sessions
  //$sessions = $this->get_event_sessions($event);

  // Generate ICS content (single event or multi-session)
  if (count($event['sessions']) > 1) {
    $ics_content = $this->generate_multisession_ics($event, $event['sessions']);
  } else {
    $ics_content = $this->generate_ics($event);
  }

  // Build the filename
  $title = $event['title'];
  if (!empty($event['Delivery_Language__c']) && 'english' != strtolower($event['Delivery_Language__c'])) {
    $title .= ' - ' . $event['Delivery_Language__c'];
  }
  $filename = $this->sanitize_filename($title) . '.ics';

  // Create response
  $response = new \Symfony\Component\HttpFoundation\Response($ics_content);
  $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
  $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
  $response->headers->set('Cache-Control', 'no-cache, must-revalidate, max-age=0');
  $response->headers->set('Pragma', 'no-cache');

  return $response;
}

 /**
 * Generates ICS file content for a single-session event.
 *
 * @param array $event
 *   The event data from Salesforce.
 *
 * @return string
 *   The ICS file content.
 */
private function generate_ics($event)
{
  // Create a new calendar
  $vcalendar = new \Sabre\VObject\Component\VCalendar();

  // Get start and end times in Central Time
  $start = new \DateTime('@' . $event['Start_Time_and_Date__c'], new \DateTimeZone('America/Chicago'));
  $end = new \DateTime('@' . $event['End_Date_and_Time__c'], new \DateTimeZone('America/Chicago'));

  // Build title
  $title = $event['Name_Placeholder__c'];
  if (!empty($event['Delivery_Language__c']) && 'english' != strtolower($event['Delivery_Language__c'])) {
    $title .= ' - ' . $event['Delivery_Language__c'];
  }

  // Build location
  $location_parts = array_filter([
    $event['Event_Location_Site_Building__c'],
    $event['Event_Location_Street_Address__c'],
    $event['Event_Location__c'],
    $event['Program_State__c'],
    $event['Event_Location_Zip_Code__c'],
  ]);
  $location = implode(', ', $location_parts);

  // Build description
  $description = strip_tags($event['description'] ?? '');

  // Add contact info to description
  if (!empty($event['Contact_Information_Name__c'])) {
    $description .= "\n\nContact: " . $event['Contact_Information_Name__c'];
    if (!empty($event['Contact_Information_Email__c'])) {
      $description .= "\nEmail: " . $event['Contact_Information_Email__c'];
    }
    if (!empty($event['Contact_Information_Phone__c'])) {
      $description .= "\nPhone: " . $event['Contact_Information_Phone__c'];
    }
  }

  // Add instructor info if available
  if (!empty($event['Instructor_Information_Name__c'])) {
    $description .= "\n\nInstructor: " . $event['Instructor_Information_Name__c'];
    if (!empty($event['Instructor_Information_Email__c'])) {
      $description .= "\nEmail: " . $event['Instructor_Information_Email__c'];
    }
  }

  // Build event URL
  $eventTitle = $this->sanitize_filename($title);
  $eventID = $event['id'];
  $url = \Drupal::request()->getSchemeAndHttpHost() . (ISUEOHelpers\General::is_main_extension_site() ? '/calendar' : '') . '/event-details/' . $eventID . '/' . $eventTitle;
  // Convert to UTC to avoid timezone issues
  $start_utc = clone $start;
  $end_utc = clone $end;
  $start_utc->setTimezone(new \DateTimeZone('UTC'));
  $end_utc->setTimezone(new \DateTimeZone('UTC'));

  // Create the event
  $vevent = $vcalendar->createComponent('VEVENT');
  $vevent->UID = $event['id'] . '@' . \Drupal::request()->getHost();
  $vevent->SUMMARY = $title;
  $vevent->DESCRIPTION = $description;
  $vevent->LOCATION = $location;
  $vevent->URL = $url;
  $vevent->STATUS = 'CONFIRMED';

  // Add dates in UTC format
  $vevent->DTSTART = $start_utc;
  $vevent->DTEND = $end_utc;

  // Add registration URL as a custom property if available
  if (!empty($event['Registration_Link__c'])) {
    $vevent->{'X-REGISTRATION-URL'} = $event['Registration_Link__c'];
  }

  // Add organizer if contact info exists
  if (!empty($event['Contact_Information_Email__c'])) {
    $organizer_name = $event['Contact_Information_Name__c'] ?? 'Iowa State University Extension';
    $vevent->ORGANIZER = 'mailto:' . $event['Contact_Information_Email__c'];
    $vevent->ORGANIZER['CN'] = $organizer_name;
  }

  $vcalendar->add($vevent);

  // Return the serialized calendar
  return $vcalendar->serialize();
}

/**
 * Generates ICS with multiple events for multi-session programs.
 *
 * @param array $event
 *   The event data from Salesforce.
 * @param array $sessions
 *   The sessions array.
 *
 * @return string
 *   The ICS file content.
 */
private function generate_multisession_ics($event, $sessions)
{
  // Create a new calendar
  $vcalendar = new \Sabre\VObject\Component\VCalendar();

  // Build common properties
  $title = $event['title'];
  if (!empty($event['Delivery_Language__c']) && 'english' != strtolower($event['Delivery_Language__c'])) {
    $title .= ' - ' . $event['Delivery_Language__c'];
  }

  $location_parts = array_filter([
    $event['Event_Location_Site_Building__c'],
    $event['Event_Location_Street_Address__c'],
    $event['Event_Location__c'],
    $event['Program_State__c'],
    $event['Event_Location_Zip_Code__c'],
  ]);
  $location = implode(', ', $location_parts);

  $description = strip_tags($event['description'] ?? '');

  if (!empty($event['Contact_Information_Name__c'])) {
    $description .= "\n\nContact: " . $event['Contact_Information_Name__c'];
    if (!empty($event['Contact_Information_Email__c'])) {
      $description .= "\nEmail: " . $event['Contact_Information_Email__c'];
    }
    if (!empty($event['Contact_Information_Phone__c'])) {
      $description .= "\nPhone: " . $event['Contact_Information_Phone__c'];
    }
  }

  $eventTitle = $this->sanitize_filename($title);
  $eventID = $event['id'];
  $url = \Drupal::request()->getSchemeAndHttpHost() . (ISUEOHelpers\General::is_main_extension_site() ? '/calendar' : '') . '/event-details/' . $eventID . '/' . $eventTitle;

  // Create an event for each session
  $session_number = 1;
  //foreach ($sessions as $timestamp => $session) {
  foreach ($sessions as $timestamp) {
    // Parse the session datetime
    $session_start = new \DateTime();
    $session_start->setTimestamp($timestamp);
    $session_start->setTimezone(new \DateTimeZone('America/Chicago'));

    $session_end = new DateTime();
    $session_end->setTimestamp($event['sessions_end_date_time'][$session_number - 1]);
    $session_end->setTimezone(new \DateTimeZone('America/Chicago'));

    // Assume 1-hour duration if not specified
    //$session_end = clone $session_start;
    //$session_end->modify('+1 hour');

    // Convert to UTC
    $session_start_utc = clone $session_start;
    $session_end_utc = clone $session_end;
    $session_start_utc->setTimezone(new \DateTimeZone('UTC'));
    $session_end_utc->setTimezone(new \DateTimeZone('UTC'));

    // Create event for this session
    $vevent = $vcalendar->createComponent('VEVENT');
    $vevent->UID = $event['id'] . '-session-' . $session_number . '@' . \Drupal::request()->getHost();
    $vevent->SUMMARY = $title . ' - Session ' . $session_number;
    $vevent->DESCRIPTION = "Session $session_number of " . count($sessions) . "\n\n" . $description;
    $vevent->LOCATION = $location;
    $vevent->URL = $url;
    $vevent->STATUS = 'CONFIRMED';
    $vevent->SEQUENCE = 0;

    // Add dates in UTC
    $vevent->DTSTART = $session_start_utc;
    $vevent->DTEND = $session_end_utc;

    if (!empty($event['Contact_Information_Email__c'])) {
      $organizer_name = $event['Contact_Information_Name__c'] ?? 'Iowa State University Extension';
      $vevent->ORGANIZER = 'mailto:' . $event['Contact_Information_Email__c'];
      $vevent->ORGANIZER['CN'] = $organizer_name;
    }

    $vcalendar->add($vevent);
    $session_number++;
  }

  return $vcalendar->serialize();
}

  /**
   * Sanitizes a filename.
   *
   * @param string $filename
   *   The filename to sanitize.
   *
   * @return string
   *   The sanitized filename.
   */
  private function sanitize_filename($filename)
  {
    // Remove or replace characters that are problematic in filenames
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '-', $filename);
    // Remove multiple consecutive hyphens
    $filename = preg_replace('/-+/', '-', $filename);
    // Trim hyphens from ends
    $filename = trim($filename, '-');

    return $filename;
  }
}
