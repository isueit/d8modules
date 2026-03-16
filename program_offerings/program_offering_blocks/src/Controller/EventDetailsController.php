<?php

namespace Drupal\program_offering_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\isueo_helpers\ISUEOHelpers;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the program_offering_blocks  module.
 */
class EventDetailsController extends ControllerBase
{
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function event_details($eventID, $eventTitle)
  {
    // Do NOT cache the events details page
    \Drupal::service('page_cache_kill_switch')->trigger();

    $title = '';

    $module_config = \Drupal::config('program_offering_blocks.settings');
    $buffer = ISUEOHelpers\Files::fetch_url($module_config->get('url'), true);
    $program_offerings = json_decode($buffer, TRUE);

    foreach ($program_offerings as $event) {
      if ($event['Id'] == $eventID  || (strlen($eventID) < 10 && trim(trim($event['Ungerboeck_Event_ID__c']), "0") == trim(trim($eventID), "0"))) {

        $title = $event['Name_Placeholder__c'];

        // Append language to the end of the title, when it's not English
        if (!empty($event['Delivery_Language__c']) && 'english' != strtolower($event['Delivery_Language__c'])) {
          $title .= ' - ' . $event['Delivery_Language__c'];
        }

        $address = [
          'building' => $event['Event_Location_Site_Building__c'],
          'street' => $event['Event_Location_Street_Address__c'],
          'city' => $event['Event_Location__c'],
          'state' => $event['Program_State__c'],
          'zip' => $event['Event_Location_Zip_Code__c'],
        ];

        $contact = [
          'name' => $event['Contact_Information_Name__c'],
          'email' => $event['Contact_Information_Email__c'],
          'phone' => $event['Contact_Information_Phone__c'],
        ];
        $instructor = [
          'name' => $event['Instructor_Information_Name__c'],
          'email' => $event['Instructor_Information_Email__c'],
          'phone' => $event['Instructor_Information_Phone__c'],
        ];

        $sessions = $this->get_event_sessions($event);

        // We've found the correct event, quit looking for the right event
        break;
      }
    }

    // If we don't have a title, then throw a 404 exception
    if (empty($title)) {
      throw new NotFoundHttpException();
    }

    $element = array(
      '#theme' => count($sessions) > 1 ? 'program_offering_details_multisession' : 'program_offering_details_singlesession',
      '#title' => $title,
      '#eventID' => $eventID,
      '#smugmug_id' => $event['Planned_Program__r.Smugmug_ID__c'],
      '#date' => $this->handle_dates($event),
      '#online' => 'Online' == $event['Event_Location__c'],
      '#address' => $address,
      '#description' => $event['Program_Description__c'],
      '#contact' => $contact,
      '#instructor' => $instructor,
      '#sessions' => $sessions,
      '#urls' => [
        'event' => empty($event['Program_Offering_Website__c']) ? '' : trim($event['Program_Offering_Website__c'], '\/'),
        'program' => empty($event['Planned_Program_Website__c']) ? '' : trim($event['Planned_Program_Website__c'], '\/'),
      ],
      '#registration' => $this->get_registration_info($event),
      '#attached' => ['library' => ['program_offering_blocks/program_offering_blocks_details']],
    );
    return $element;
  }

  private function handle_dates($event)
  {
    // Start with Date part of start time
    $startdate = strtoTime($event['Start_Time_and_Date__c']);
    $enddate = strtoTime($event['End_Date_and_Time__c']);
    $nextdate = strtotime($event['Next_Start_Date__c']);

    $date = [
      'start_date' => date('F j, Y', $startdate),
      'start_time' => date('Gi', $startdate) <> '0000' ? date('g:i A', $startdate) : '',
      'start_day' => date('l', $startdate),
      'end_date' => date('F j, Y', $enddate),
      'end_time' => date('Gi', $enddate) <> '0000' ? date('g:i A', $enddate) : '',
      'end_day' => date('l', $enddate),
      'next_date' => date('F j, Y', $nextdate),
      'next_time' => date('Gi', $nextdate) <> '0000' ? date('g:i A', $nextdate) : '',
      'next_day' => date('l', $nextdate),
    ];

    return $date;
  }

  private function get_event_sessions($event)
  {
    $sessions = [];
    $session_names = [
      'Start_Time_and_Date__c',
      'Second_Session_Date_Time__c',
      'Third_Session_Begining_Date_and_Time__c',
      'Fourth_Session_Beginning_Date_and_Time__c',
      'Fifth_Session_Beginning_Date_and_Time__c',
      'Sixth_Session_Beginning_Date_and_Time__c',
      'Seventh_Session_Beginning_Date_and_Time__c',
      'Eighth_Session_Beginning_Date_and_Time__c',
      'Ninth_Session_Beginning_Date_and_Time__c',
      'Tenth_Session_Beginning_Date_and_Time__c',
      'Eleventh_Session_Start_Date__c',
      'Twelfth_Session_Start_Date__c',
    ];

    foreach ($session_names as $session_name) {
      if (!empty($event[$session_name])) {
        $tmpdate = strtotime($event[$session_name]);
        $sessions[date('U', $tmpdate)] = [
          'date' => date('F j, Y', $tmpdate),
          'time' => date('g:i a', $tmpdate),
          'day' => date('l', $tmpdate),
          'is_next' => $event[$session_name] == $event['Next_Start_Date__c'],
        ];
      }
    }

    ksort($sessions);
    return $sessions;
  }

  private function get_registration_info($event) {
    $now = strtotime('today midnight');
    $regstartdate = !empty($event['Registration_Opens__c']) ? strtotime($event['Registration_Opens__c']) : $now;
    $regenddate = !empty($event['Registration_Deadline__c']) ? strtotime($event['Registration_Deadline__c']) : $now;

    $registration = [
    ];

    if (!empty($event['Registration_Link__c'])) {
      if ($now >= $regstartdate && $now <= $regenddate) {
        $registration['url'] = $event['Registration_Link__c'];
      } elseif ($now > $regenddate) {
        $registration['url'] = '';
        $registration['closes']['date'] = date('F j, Y', $regenddate);
        $registration['closes']['time'] = '11:59 PM';
        $registration['closes']['day'] = date('l', $regenddate);
      } else {
        $registration['url'] = '';
        $registration['opens']['date'] = date('F j, Y', $regstartdate);
        $registration['opens']['time'] = '00:00 AM';
        $registration['opens']['day'] = date('l', $regstartdate);
      }
    }

    return $registration;
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

  $module_config = \Drupal::config('program_offering_blocks.settings');
  $buffer = ISUEOHelpers\Files::fetch_url($module_config->get('url'), true);
  $program_offerings = json_decode($buffer, TRUE);

  $event = NULL;
  foreach ($program_offerings as $offering) {
    if ($offering['Id'] == $eventID || (strlen($eventID) < 10 && trim(trim($offering['Ungerboeck_Event_ID__c']), "0") == trim(trim($eventID), "0"))) {
      $event = $offering;
      break;
    }
  }

  if (!$event) {
    throw new NotFoundHttpException();
  }

  // Get sessions
  $sessions = $this->get_event_sessions($event);

  // Generate ICS content (single event or multi-session)
  if (count($sessions) > 1) {
    $ics_content = $this->generate_multisession_ics($event, $sessions);
  } else {
    $ics_content = $this->generate_ics($event);
  }

  // Build the filename
  $title = $event['Name_Placeholder__c'];
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
  $start = new \DateTime($event['Start_Time_and_Date__c'], new \DateTimeZone('America/Chicago'));
  $end = new \DateTime($event['End_Date_and_Time__c'], new \DateTimeZone('America/Chicago'));

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
  $description = strip_tags($event['Program_Description__c'] ?? '');
  
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
  $eventID = $event['Id'];
  $url = \Drupal::request()->getSchemeAndHttpHost() . '/event-details/' . $eventID . '/' . $eventTitle;

  // Convert to UTC to avoid timezone issues
  $start_utc = clone $start;
  $end_utc = clone $end;
  $start_utc->setTimezone(new \DateTimeZone('UTC'));
  $end_utc->setTimezone(new \DateTimeZone('UTC'));

  // Create the event
  $vevent = $vcalendar->createComponent('VEVENT');
  $vevent->UID = $event['Id'] . '@' . \Drupal::request()->getHost();
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
  $title = $event['Name_Placeholder__c'];
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

  $description = strip_tags($event['Program_Description__c'] ?? '');
  
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
  $eventID = $event['Id'];
  $url = \Drupal::request()->getSchemeAndHttpHost() . '/event-details/' . $eventID . '/' . $eventTitle;

  // Create an event for each session
  $session_number = 1;
  foreach ($sessions as $timestamp => $session) {
    // Parse the session datetime
    $session_start = new \DateTime();
    $session_start->setTimestamp($timestamp);
    $session_start->setTimezone(new \DateTimeZone('America/Chicago'));
    
    // Assume 1-hour duration if not specified
    $session_end = clone $session_start;
    $session_end->modify('+1 hour');

    // Convert to UTC
    $session_start_utc = clone $session_start;
    $session_end_utc = clone $session_end;
    $session_start_utc->setTimezone(new \DateTimeZone('UTC'));
    $session_end_utc->setTimezone(new \DateTimeZone('UTC'));

    // Create event for this session
    $vevent = $vcalendar->createComponent('VEVENT');
    $vevent->UID = $event['Id'] . '-session-' . $session_number . '@' . \Drupal::request()->getHost();
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
