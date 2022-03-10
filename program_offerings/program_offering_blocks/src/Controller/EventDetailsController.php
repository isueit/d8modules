<?php

namespace Drupal\program_offering_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;

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

    $results = '';
    $results .= PHP_EOL . '<div class="program_offering_blocks ungerboeck_eventlist_details">' . PHP_EOL;
    $title = 'Sorry, event not found';

    //    $eventID = intval($eventID);
    $module_config = \Drupal::config('program_offering_blocks.settings');
    $buffer = file_get_contents($module_config->get('url'));
    $program_offerings = json_decode($buffer, TRUE);

    foreach ($program_offerings as $event) {
      if ($event['Id'] == $eventID  || (strlen($eventID) < 10 && trim(trim($event['Ungerboeck_Event_ID__c']), "0") == trim(trim($eventID),"0"))) {

        $title = $event['Name_Placeholder__c'];

        // Append language to the end of the title, when it's not English
        if (!empty($event['Delivery_Language__c']) && 'english' != strtolower($event['Delivery_Language__c'])) {
          $title .= ' - ' . $event['Delivery_Language__c'];
        }

        $results .= $this->handle_dates($event) . PHP_EOL;

        if ('Online' == $event['Event_Location__c']) {
          $event_address = 'Online';
        } else {
          $event_address = $event['Event_Location_Site_Building__c'] . '<br/>' . PHP_EOL;
          $event_address .= $event['Event_Location_Street_Address__c'] . '<br/>' . PHP_EOL;
          $event_address .= $event['Event_Location__c'] . ', ';
          $event_address .= $event['Program_State__c'] . ' ';
          $event_address .= $event['Event_Location_Zip_Code__c'] . '<br/>' . PHP_EOL;
        }
        $results .= '  <div class="event_address">' . $event_address . '  </div>' . PHP_EOL;


        if (!empty($event['Planned_Program__r.Web_Description__c'])) {
          $description = str_replace('<p><br></p>', '', $event['Planned_Program__r.Web_Description__c']) . PHP_EOL;
        } else {
          $results .= $event['Program_Description__c'] . PHP_EOL;
        }
        if (!empty($event['Planned_Program__r.Smugmug_ID__c'])) {
          $description = '<img class="educational_program_image" src="https://photos.smugmug.com/photos/' . $event['Planned_Program__r.Smugmug_ID__c'] . '/0/XL/' . $event['Planned_Program__r.Smugmug_ID__c'] . '-XL.jpg" alt="" />' . $description . '<div class="clearer"></div>';
        }
        $results .= '  <div class="event_description">' . $description . PHP_EOL;


        $results .= '  <div class="event_contact_label">Contact Info:</div>' . PHP_EOL;
        $results .= '  <div class="event_contact_name">' . $event['Contact_Information_Name__c'] . '</div>' . PHP_EOL;
        $results .= '  <div class="event_contact_email"><a href="mailto:' . $event['Contact_Information_Email__c']  . '">' . $event['Contact_Information_Email__c'] . '</a></div>' . PHP_EOL;
        if (!empty($event['Contact_Information_Phone__c'])) {
          $results .= '  <div class="event_contact_phone">' . $event['Contact_Information_Phone__c'] . '</div>' . PHP_EOL;
        }

        if ($event['Contact_Person__c'] <> $event['Primary_Instructor_Presenter__c']) {
          $results .= '  <div class="event_contact_label">Primary Instructor:</div>' . PHP_EOL;
          $results .= '  <div class="event_contact_name">' . $event['Instructor_Information_Name__c'] . '</div>' . PHP_EOL;
          $results .= '  <div class="event_contact_email"><a href="mailto:' . $event['Instructor_Information_Email__c']  . '">' . $event['Instructor_Information_Email__c'] . '</a></div>' . PHP_EOL;
          if (!empty($event['Instructor_Information_Phone__c'])) {
            $results .= '  <div class="event_contact_phone">' . $event['Instructor_Information_Phone__c'] . '</div>' . PHP_EOL;
          }
        }

        $results .= $this->get_event_sessions($event);
        $results .= $this->get_event_links($event);

        // We've found the correct event, quit looking for the right event
        break;
      }
    }
    $results .= PHP_EOL . '</div>' . PHP_EOL;

    $element = array(
      '#title' => $title,
      '#markup' => $results,
      '#attached' => ['library' => ['program_offering_blocks/program_offering_blocks_details']],
    );
    return $element;
  }

  private function handle_dates($event)
  {
    // Start with Date part of start time
    $startdate = strtoTime($event['Start_Time_and_Date__c']);
    $enddate = strtoTime($event['End_Date_and_Time__c']);
    $output = date('l, m/d/Y', $startdate);

    // If start time isn't midnight, then display the start time also
    if (date('Gi', $startdate) <> '0000') {
      $output .= date(' g:i A', $startdate);
    }

    $output .= ' - ';

    // If date part of start and end dates are different, then include the end date
    if (date('z', $startdate) <> date('z', $enddate)) {
      $output .= date('l, m/d/y', $enddate);
    }

    // If the end time isn't midnight, then display the end time
    if (date('Gi', $enddate) <> '0000') {
      $output .= date(' g:i A', $enddate);
    }

    $output = '  <div class="event_details_dates">' . $output . '</div>' . PHP_EOL;
    if ($event['Start_Time_and_Date__c'] != $event['Next_Start_Date__c']) {
      $tmpdate = strtotime($event['Next_Start_Date__c']);
      $tmpstr = date('l, m/d/y', $tmpdate);
      // If start time isn't midnight, then display the start time also
      if (date('Gi', $tmpdate) <> '0000') {
        $tmpstr .= date(' h:i A', $tmpdate);
      }
      $output .= '  <p>Next Session: <span class="event_details_dates">' . $tmpstr . '</span></p>' . PHP_EOL;
    }

    return $output;
  }

  private function get_event_sessions($event)
  {
    $count = 0;
    $returnStr = '';
    $event_sessions = '';
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
        $multiple_sessions = true;
        $tmpstr = date('l, m/d/Y g:i A', strtoTime($event[$session_name]));
        if ($event[$session_name] == $event['Next_Start_Date__c']) {
          $tmpstr = '<span class="next_session">' . $tmpstr . '</span>';
        }
        $event_sessions .= '<li>' . $tmpstr . '</li>' . PHP_EOL;
        $count++;
      }
    }

    if ($count > 1) {
      $returnStr .= '<div class="event_sessions">Sessions:<br/>' . PHP_EOL;
      $returnStr .= '<ol>' . PHP_EOL;
      $returnStr .= $event_sessions;
      $returnStr .= '</ol>' . PHP_EOL;
      $returnStr .= '</div>' . PHP_EOL;
    }

    return $returnStr;
  }

  private function get_event_links($event)
  {
    $now = strtotime('today midnight');
    $regstartdate = !empty($event['Registration_Opens__c']) ? strtotime($event['Registration_Opens__c']) : $now;
    $regenddate = !empty($event['Registration_Deadline__c']) ? strtotime($event['Registration_Deadline__c']) : $now;
    //$regenddate = date_add(new DateTime('@'.$regenddate), new DateInterval('P1D'))->getTimestamp();

    $returnStr = '  <div class="event_details_links">' . PHP_EOL;

    if (!empty($event['Registration_Link__c'])) {

     if ($now >= $regstartdate && $now <= $regenddate) {
      $returnStr .= '    <div class="event_details_registration"><a href="' . $event['Registration_Link__c'] . '">Register Online</a></div>' . PHP_EOL;
     } elseif ($now > $regenddate) {
      $returnStr .= '    <div class="event_details_registration">Registration Closed ' . date('M d, Y', $regenddate) . '</div>' . PHP_EOL;
     } else {
      $returnStr .= '    <div class="event_details_registration">Registration Opens ' . date('M d, Y', $regstartdate) . '</div>' . PHP_EOL;
     }
    }

    //if (!empty($event['Program_Offering_Website__c']) && $event['Registration_Link__c'] <> $event['Program_Offering_Website__c']) {
    //  $returnStr .= '    <div class="event_details_more_information"><a href="' . $event['Program_Offering_Website__c'] . '">More information about event</a></div>' . PHP_EOL;
    //} elseif (!empty($event['Planned_Program_Website__c']) && $event['Registration_Link__c'] <> $event['Planned_Program_Website__c']) {
    //  $returnStr .= '    <div class="event_details_more_information"><a href="' . $event['Planned_Program_Website__c'] . '">More information about event</a></div>' . PHP_EOL;
    //}
    if (!empty($event['Planned_Program_Website__c']) && $event['Registration_Link__c'] <> $event['Planned_Program_Website__c']) {
      $returnStr .= '    <div class="event_details_more_information"><a href="' . $event['Planned_Program_Website__c'] . '">More information about event</a></div>' . PHP_EOL;
    }

    $returnStr .= '  </div>' . PHP_EOL;
    return $returnStr;
  }
}
