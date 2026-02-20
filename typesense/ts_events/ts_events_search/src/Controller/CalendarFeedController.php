<?php

namespace Drupal\ts_events_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\isueo_helpers\ISUEOHelpers;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates ICS calendar feeds for events.
 */
class CalendarFeedController extends ControllerBase
{
  /**
   * Generates a full calendar feed with all events.
   */
  public function feed()
  {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $module_config = \Drupal::config('program_offering_blocks.settings');
    $buffer = ISUEOHelpers\Files::fetch_url($module_config->get('url'), true);
    $events = json_decode($buffer, TRUE);

    if (empty($events)) {
      return new Response('No events found', 404);
    }

    // Filter to only future events
    $future_events = $this->filter_future_events($events);

    if (empty($future_events)) {
      return new Response('No upcoming events found', 404);
    }

    $ics_content = $this->generate_calendar_ics($future_events);

    $response = new Response($ics_content);
    $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="iowa-state-extension-calendar.ics"');
    $response->headers->set('Cache-Control', 'no-cache, must-revalidate, max-age=0');
    $response->headers->set('Pragma', 'no-cache');

    return $response;
  }

  /**
   * Generates a filtered calendar feed based on query parameters.
   */
  public function filtered_feed(Request $request)
  {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $module_config = \Drupal::config('program_offering_blocks.settings');
    $buffer = ISUEOHelpers\Files::fetch_url($module_config->get('url'), true);
    $all_events = json_decode($buffer, TRUE);

    if (empty($all_events)) {
      return new Response('No events found', 404);
    }

    // Get filter parameters matching your Typesense attributes
    $filters = [
      'county' => $request->query->get('county'),
      'program_unit' => $request->query->get('program_unit'),
      'delivery_method' => $request->query->get('delivery_method'),
      'delivery_language' => $request->query->get('delivery_language'),
      'plp_program' => $request->query->get('plp_program'),
      'query' => $request->query->get('query'),
    ];

    // Filter events
    $filtered_events = $this->filter_events($all_events, $filters);

    if (empty($filtered_events)) {
      return new Response('No events match your filters', 404);
    }

    $ics_content = $this->generate_calendar_ics($filtered_events);
    $filename = $this->build_filename($filters);

    $response = new Response($ics_content);
    $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->headers->set('Cache-Control', 'no-cache, must-revalidate, max-age=0');
    $response->headers->set('Pragma', 'no-cache');

    return $response;
  }

  /**
   * Filters events to only future events.
   */
  private function filter_future_events($events)
  {
    $now = time();
    $future = [];
    
    foreach ($events as $event) {
      if (!empty($event['Start_Time_and_Date__c'])) {
        $start = strtotime($event['Start_Time_and_Date__c']);
        if ($start >= $now) {
          $future[] = $event;
        }
      }
    }
    
    return $future;
  }

  /**
   * Filters events based on criteria (matching Typesense attributes).
   */
  private function filter_events($events, $filters)
  {
    $filtered = [];
    $now = time();

    foreach ($events as $event) {
      // Only include future events
      if (!empty($event['Start_Time_and_Date__c'])) {
        $start = strtotime($event['Start_Time_and_Date__c']);
        if ($start < $now) {
          continue;
        }
      }

      $include = true;

      // Filter by county
      if (!empty($filters['county']) && $include) {
        if (empty($event['County__c']) || stripos($event['County__c'], $filters['county']) === false) {
          $include = false;
        }
      }

      // Filter by program unit
      if (!empty($filters['program_unit']) && $include) {
        if (empty($event['PrimaryProgramUnit__c']) || $event['PrimaryProgramUnit__c'] !== $filters['program_unit']) {
          $include = false;
        }
      }

      // Filter by delivery method
      if (!empty($filters['delivery_method']) && $include) {
        $delivery_method = strtolower($event['Event_Location__c'] ?? '');
        $filter_method = strtolower($filters['delivery_method']);
        
        if ($filter_method === 'online' && $delivery_method !== 'online') {
          $include = false;
        } elseif ($filter_method === 'in-person' && $delivery_method === 'online') {
          $include = false;
        }
      }

      // Filter by delivery language
      if (!empty($filters['delivery_language']) && $include) {
        if (empty($event['Delivery_Language__c']) || stripos($event['Delivery_Language__c'], $filters['delivery_language']) === false) {
          $include = false;
        }
      }

      // Filter by program ID
      if (!empty($filters['plp_program']) && $include) {
        if (empty($event['Planned_Program__c']) || stripos($event['Planned_Program__c'], $filters['plp_program']) === false) {
          $include = false;
        }
      }

      // Filter by search query
      if (!empty($filters['query']) && $include) {
        $search = strtolower($filters['query']);
        $title = strtolower($event['Name_Placeholder__c'] ?? '');
        $description = strtolower($event['Program_Description__c'] ?? '');
        $county = strtolower($event['County__c'] ?? '');
        
        if (stripos($title, $search) === false && 
            stripos($description, $search) === false && 
            stripos($county, $search) === false) {
          $include = false;
        }
      }

      if ($include) {
        $filtered[] = $event;
      }
    }

    return $filtered;
  }

  /**
   * Generates an ICS calendar with multiple events.
   */
  private function generate_calendar_ics($events)
  {
    $vcalendar = new \Sabre\VObject\Component\VCalendar();

    foreach ($events as $event) {
      if (empty($event['Start_Time_and_Date__c']) || empty($event['End_Date_and_Time__c'])) {
        continue;
      }

      $start = new \DateTime($event['Start_Time_and_Date__c'], new \DateTimeZone('America/Chicago'));
      $end = new \DateTime($event['End_Date_and_Time__c'], new \DateTimeZone('America/Chicago'));

      $start_utc = clone $start;
      $end_utc = clone $end;
      $start_utc->setTimezone(new \DateTimeZone('UTC'));
      $end_utc->setTimezone(new \DateTimeZone('UTC'));

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
      }

      $eventTitle = $this->sanitize_filename($title);
      $eventID = $event['Id'];
      $url = \Drupal::request()->getSchemeAndHttpHost() . '/ts-event-details/' . $eventID . '/' . $eventTitle;

      $vevent = $vcalendar->createComponent('VEVENT');
      $vevent->UID = $event['Id'] . '@' . \Drupal::request()->getHost();
      $vevent->SUMMARY = $title;
      $vevent->DESCRIPTION = $description;
      $vevent->LOCATION = $location;
      $vevent->URL = $url;
      $vevent->STATUS = 'CONFIRMED';
      $vevent->DTSTART = $start_utc;
      $vevent->DTEND = $end_utc;

      if (!empty($event['Contact_Information_Email__c'])) {
        $organizer_name = $event['Contact_Information_Name__c'] ?? 'Iowa State University Extension';
        $vevent->ORGANIZER = 'mailto:' . $event['Contact_Information_Email__c'];
        $vevent->ORGANIZER['CN'] = $organizer_name;
      }

      $vcalendar->add($vevent);
    }

    return $vcalendar->serialize();
  }

  /**
   * Builds a filename based on applied filters.
   */
  private function build_filename($filters)
  {
    $parts = ['iowa-state-extension'];

    if (!empty($filters['county'])) {
      $parts[] = $this->sanitize_filename($filters['county']);
    }
    if (!empty($filters['program_unit'])) {
      $parts[] = $this->sanitize_filename($filters['program_unit']);
    }
    if (!empty($filters['delivery_method'])) {
      $parts[] = $filters['delivery_method'];
    }

    return implode('-', $parts) . '-calendar.ics';
  }

  /**
   * Sanitizes a filename.
   */
  private function sanitize_filename($filename)
  {
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '-', $filename);
    $filename = preg_replace('/-+/', '-', $filename);
    $filename = trim($filename, '-');
    return strtolower($filename);
  }
}