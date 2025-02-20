<?php

namespace Drupal\program_offering_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\isueo_helpers\ISUEOHelpers;

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

    $title = 'Sorry, event not found';

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

    $element = array(
      '#theme' => count($sessions) > 1 ? 'program_offering_details_multisession' : 'program_offering_details_singlesession',
      '#title' => $title,
      '#smugmug_id' => $event['Planned_Program__r.Smugmug_ID__c'],
      '#date' => $this->handle_dates($event),
      '#online' => 'Online' == $event['Event_Location__c'],
      '#address' => $address,
      '#description' => $event['Program_Description__c'],
      '#contact' => $contact,
      '#instructor' => $instructor,
      '#sessions' => $sessions,
      '#urls' => [
        'event' => $event['Program_Offering_Website__c'],
        'program' => $event['Planned_Program_Website__c'],
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
}
