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

    $results = '';
    $results .= PHP_EOL . '<div class="program_offering_blocks ungerboeck_eventlist_details">' . PHP_EOL;
    $title = 'Sorry, event not found';

    //    $eventID = intval($eventID);
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
        
        if (!empty($event['Planned_Program__r.Smugmug_ID__c'])) {
          $results .= '<img class="educational_program_image" src="https://photos.smugmug.com/photos/' . $event['Planned_Program__r.Smugmug_ID__c'] . '/0/XL/' . $event['Planned_Program__r.Smugmug_ID__c'] . '-XL.jpg" alt="" />' . '<div class="clearer"></div>';
        }

        $results .= ' <div class="event_details_wrapper">' . PHP_EOL;
        $results .= $this->handle_dates($event) . PHP_EOL;

        if ('Online' == $event['Event_Location__c']) {
          $event_address = 'Online';
        } else {
          $event_address = $event['Event_Location_Site_Building__c'] . ', ' . PHP_EOL;
          $event_address .= $event['Event_Location_Street_Address__c'] . ', ' . PHP_EOL;
          $event_address .= $event['Event_Location__c'] . ', ';
          $event_address .= $event['Program_State__c'] . ' ';
          $event_address .= $event['Event_Location_Zip_Code__c'] . PHP_EOL;
        }
        $results .= '  <div class="event_address_wrapper">' . $event_address . '  </div>' . PHP_EOL;
    
        $description = '';
        //        if (!empty($event['Planned_Program__r.Web_Description__c'])) {
        //          $description = str_replace('<p><br></p>', '', $event['Planned_Program__r.Web_Description__c']) . PHP_EOL;
        //        } else {
        $description .= $event['Program_Description__c'] . PHP_EOL;
        //        }
      
        $results .= '  <div class="event_contact_wrapper">' . '  <a href="mailto:' . $event['Contact_Information_Email__c']  . '">' . $event['Contact_Information_Name__c'] . '</a>';
        if (!empty($event['Contact_Information_Phone__c'])) {
          $results .='  <span>' . $event['Contact_Information_Phone__c'] . '</span>' . '</div>' . PHP_EOL;
        }

        if (!empty($event['Primary_Instructor_Presenter__c']) && ($event['Contact_Person__c'] <> $event['Primary_Instructor_Presenter__c'])) {
          $results .= '  <div class="event_instructor_wrapper">' . PHP_EOL;
          $results .= '  <a href="mailto:' . $event['Instructor_Information_Email__c']  . '">' . $event['Instructor_Information_Name__c'] . '</a>';
          if (!empty($event['Instructor_Information_Phone__c'])) {
            $results .= '  <span>' . $event['Instructor_Information_Phone__c'] . '</span>' . PHP_EOL;
          }
          $results .= ' </div>' . PHP_EOL;
        }
        $results .= PHP_EOL . '</div>' . PHP_EOL;

        //        if (isset($description)) {
          if (!empty($event['Program_Description__c'])) {
        $results .= '  <div class="event_description">' . $description;
        if (!empty($event['Planned_Program_Website__c']) && $event['Registration_Link__c'] <> $event['Planned_Program_Website__c'] && $event['Program_Offering_Website__c'] <> $event['Planned_Program_Website__c']) {
          $results .= '    <span class="event_details_more_information"><a href="' . $event['Planned_Program_Website__c'] . '" aria-label="Learn more about ' . $event['Name_Placeholder__c'] . '">Learn more about this program.</a></span>' . PHP_EOL;
        }
        $results .=' </div>';
        //        }
          }

        $results .= $this->get_event_sessions($event);
        $results .= $this->get_event_links($event);

        // We've found the correct event, quit looking for the right event
        break;
      }
    }

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
    $output = date('l, F j, Y', $startdate);

    // If start time isn't midnight, then display the start time also
   // if (date('Gi', $startdate) <> '0000') {
     // $output .= date(' g:i A', $startdate);
    //}

    //$output .= ' to ';

    // If date part of start and end dates are different, then include the end date
    if (date('z', $startdate) <> date('z', $enddate)) {
      $output .= '  to ' . date('l, F j, Y', $enddate);
    }

    // If the end time isn't midnight, then display the end time
    //if (date('Gi', $enddate) <> '0000') {
     // $output .= date(' g:i A', $enddate);
   // }

    $output = '  <div class="event_details_dates">' . $output . '</div>' . PHP_EOL;
    if ($event['Start_Time_and_Date__c'] != $event['Next_Start_Date__c']) {
      $tmpdate = strtotime($event['Next_Start_Date__c']);
      $tmpstr = date('l, F j, Y', $tmpdate);
      // If start time isn't midnight, then display the start time also
      if (date('Gi', $tmpdate) <> '0000') {
        $tmpstr .= ' at ' . date(' g:i a', $tmpdate);
      }
      $output .= '  <p class="event_details_next_session"><em>Next Session: <span class="event_details_next_dates">' . $tmpstr . '</span></em></p>' . PHP_EOL;
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
        $tmpstr = date('l, F j, Y g:i a', strtoTime($event[$session_name]));
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
  // KRISTI - Separate link functions so they can be placed differently.
  private function get_event_links($event)
  {
    $now = strtotime('today midnight');
    $regstartdate = !empty($event['Registration_Opens__c']) ? strtotime($event['Registration_Opens__c']) : $now;
    $regenddate = !empty($event['Registration_Deadline__c']) ? strtotime($event['Registration_Deadline__c']) : $now;
    //$regenddate = date_add(new DateTime('@'.$regenddate), new DateInterval('P1D'))->getTimestamp();

    $returnStr = '  <div class="event_details_links">' . PHP_EOL;

    // Add more information link(s)
    if (!empty($event['Program_Offering_Website__c']) && $event['Registration_Link__c'] <> $event['Program_Offering_Website__c']) {
      $returnStr .= '    <div class="event_details_more_information"><a href="' . $event['Program_Offering_Website__c'] . '" class="btn btn-outline-secondary" aria-label="Learn more about ' . $event['Name_Placeholder__c'] . '">Event Information</a></div>' . PHP_EOL;
    }
   // if (!empty($event['Planned_Program_Website__c']) && $event['Registration_Link__c'] <> $event['Planned_Program_Website__c'] && $event['Program_Offering_Website__c'] <> $event['Planned_Program_Website__c']) {
     // $returnStr .= '    <div class="event_details_more_information"><a href="' . $event['Planned_Program_Website__c'] . '" class="btn btn-outline-secondary" aria-label="Learn more about ' . $event['Name_Placeholder__c'] . '">Learn more</a></div>' . PHP_EOL;
    //}

    if (!empty($event['Registration_Link__c'])) {
      $returnStr .= ' <h2 class="isu-block-title h4">Registration</h2>' . PHP_EOL;
      if ($now >= $regstartdate && $now <= $regenddate) {
        $returnStr .= '    <div class="event_details_registration"><a href="' . $event['Registration_Link__c'] . '" aria-label="Register for ' . $event['Name_Placeholder__c'] . '">Register Online</a></div>' . PHP_EOL;
      } elseif ($now > $regenddate) {
        $returnStr .= '    <div class="event_details_registration">Registration for this event closed on ' . date('F j, Y.', $regenddate) . '</div>' . PHP_EOL;
      } else {
        $returnStr .= '    <div class="event_details_registration">Registration for this event opens on ' . date('F j, Y.', $regstartdate) . '</div>' . PHP_EOL;
      }
    }

    $returnStr .= '  </div>' . PHP_EOL;
    return $returnStr;
  }
}
