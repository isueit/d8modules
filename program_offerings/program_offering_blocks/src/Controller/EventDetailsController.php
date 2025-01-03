<?php

namespace Drupal\program_offering_blocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\isueo_helpers\ISUEOHelpers;
use Drupal\Core\Link;
use Drupal\Core\Url;

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

        $description = '';
//        if (!empty($event['Planned_Program__r.Web_Description__c'])) {
//          $description = str_replace('<p><br></p>', '', $event['Planned_Program__r.Web_Description__c']) . PHP_EOL;
//        } else {
          $description .= $event['Program_Description__c'] . PHP_EOL;
//        }
        if (!empty($event['Planned_Program__r.Smugmug_ID__c'])) {
          $description = '<img class="educational_program_image" src="' . ISUEOHelpers\General::build_smugmug_url($event['Planned_Program__r.Smugmug_ID__c'], 'XL') . '" alt="" />' . $description . '<div class="clearer"></div>';
        }
//        if (isset($description)) {
          $results .= '  <div class="event_description">' . $description . '</div>' . PHP_EOL;
//        }

        $results .= '  <div class="event_contact_label">Contact Info:</div>' . PHP_EOL;
        $results .= '  <div class="event_contact_name">' . $event['Contact_Information_Name__c'] . '</div>' . PHP_EOL;
        $results .= '  <div class="event_contact_email"><a href="mailto:' . $event['Contact_Information_Email__c']  . '">' . $event['Contact_Information_Email__c'] . '</a></div>' . PHP_EOL;
        if (!empty($event['Contact_Information_Phone__c'])) {
          $results .= '  <div class="event_contact_phone">' . $event['Contact_Information_Phone__c'] . '</div>' . PHP_EOL;
        }

        if (!empty($event['Primary_Instructor_Presenter__c']) && ($event['Contact_Person__c'] <> $event['Primary_Instructor_Presenter__c'])) {
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
    // Ensure that event ID exists in the event array
    if (!isset($event['Id']) || empty($event['Id'])) {
      // Handle the missing event ID case gracefully
      return '<div class="error">Event ID is missing</div>';
    }

    $eventID = $event['Id']; // Extract the event ID from the $event array

    // Start with Date part of start time
    $startdate = strtotime($event['Start_Time_and_Date__c']);
    $enddate = strtotime($event['End_Date_and_Time__c']);
    $output = date('l, m/d/Y', $startdate);

    // If start time isn't midnight, then display the start time also
    if (date('Gi', $startdate) != '0000') {
      $output .= date(' g:i A', $startdate);
    }

    $output .= ' - ';

    // If date part of start and end dates are different, then include the end date
    if (date('z', $startdate) != date('z', $enddate)) {
      $output .= date('l, m/d/Y', $enddate);
    }

    // If the end time isn't midnight, then display the end time
    if (date('Gi', $enddate) != '0000') {
      $output .= date(' g:i A', $enddate);
    }

    $output = '<div class="event_details_dates">' . $output . '</div>' . PHP_EOL;

    // Check for Next Session and display it
    if ($event['Start_Time_and_Date__c'] != $event['Next_Start_Date__c']) {
      $tmpdate = strtotime($event['Next_Start_Date__c']);
      $tmpstr = date('l, m/d/Y', $tmpdate);
      // If start time isn't midnight, then display the start time also
      if (date('Gi', $tmpdate) != '0000') {
        $tmpstr .= date(' h:i A', $tmpdate);
      }
      $output .= '<p>Next Session: <span class="event_details_dates">' . $tmpstr . '</span></p>' . PHP_EOL;
    }

    // Generate the "Add to Calendar" link
    $add_to_calendar_url = Url::fromRoute('program_offering_blocks.ics_download', ['eventID' => $eventID]);
    $add_to_calendar_link = Link::fromTextAndUrl($this->t('Add to Calendar'), $add_to_calendar_url)->toString();
    $output .= '<div class="add-to-calendar">' . $add_to_calendar_link . '</div>' . PHP_EOL;

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

  public function generate_ics($event) {
    $ics_content = "BEGIN:VCALENDAR\r\n";
    $ics_content .= "VERSION:2.0\r\n";
    $ics_content .= "PRODID:-//Your Organization//NONSGML Event//EN\r\n";
    $ics_content .= "BEGIN:VEVENT\r\n";
    $ics_content .= "UID:" . uniqid() . "\r\n"; // Unique ID
    $ics_content .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n"; // Timestamp

    // Start date
    $start_date = gmdate('Ymd\THis\Z', strtotime($event['Start_Time_and_Date__c']));
    $ics_content .= "DTSTART:" . $start_date . "\r\n";

    // Check if the end date is set, otherwise skip it
    if (!empty($event['End_Date_and_Time__c'])) {
      $end_date = gmdate('Ymd\THis\Z', strtotime($event['End_Date_and_Time__c']));
      $ics_content .= "DTEND:" . $end_date . "\r\n";
    }

    // Event summary (title)
    $ics_content .= "SUMMARY:" . htmlspecialchars($event['Name_Placeholder__c']) . "\r\n";

    // Event description
    $ics_content .= "DESCRIPTION:" . htmlspecialchars($event['Program_Description__c']) . "\r\n";

    // Event location
    $location = $event['Event_Location_Site_Building__c'] . ", " . $event['Event_Location__c'] . ", " . $event['Program_State__c'] . ", " . $event['Event_Location_Zip_Code__c'];
    $ics_content .= "LOCATION:" . htmlspecialchars($location) . "\r\n";

    $ics_content .= "END:VEVENT\r\n";
    $ics_content .= "END:VCALENDAR\r\n";

    return $ics_content;
  }
  public function download_ics($eventID) {
    // Fetch the event details from your database or API
    $event = $this->getEventDetails($eventID); // Make sure this gets the right event info

    // Generate the .ics content
    $ics_content = $this->generate_ics($event);

    // Serve the file
    return new \Symfony\Component\HttpFoundation\Response(
      $ics_content,
      200,
      array(
        'Content-Type' => 'text/calendar',
        'Content-Disposition' => 'attachment; filename="event.ics"',
      )
    );
  }
  private function getEventDetails($eventID) {
    $module_config = \Drupal::config('program_offering_blocks.settings');
    $buffer = ISUEOHelpers\Files::fetch_url($module_config->get('url'), true);
    $program_offerings = json_decode($buffer, TRUE);

    // Loop through the events to find the one that matches the eventID
    foreach ($program_offerings as $event) {
      if ($event['Id'] == $eventID || (strlen($eventID) < 10 && trim(trim($event['Ungerboeck_Event_ID__c']), "0") == trim(trim($eventID), "0"))) {
        return $event;
      }
    }

    // Return null if no event is found
    return null;
  }


  private function get_event_links($event)
  {
    $now = strtotime('today midnight');
    $regstartdate = !empty($event['Registration_Opens__c']) ? strtotime($event['Registration_Opens__c']) : $now;
    $regenddate = !empty($event['Registration_Deadline__c']) ? strtotime($event['Registration_Deadline__c']) : $now;
    //$regenddate = date_add(new DateTime('@'.$regenddate), new DateInterval('P1D'))->getTimestamp();

    $returnStr = '  <div class="event_details_links">' . PHP_EOL;

    // Add more information link(s)
    if (!empty($event['Program_Offering_Website__c']) && $event['Registration_Link__c'] <> $event['Program_Offering_Website__c']) {
      $returnStr .= '    <div class="event_details_more_information"><a href="' . $event['Program_Offering_Website__c'] . '">More information about this event</a></div>' . PHP_EOL;
    }
    if (!empty($event['Planned_Program_Website__c']) && $event['Registration_Link__c'] <> $event['Planned_Program_Website__c'] && $event['Program_Offering_Website__c'] <> $event['Planned_Program_Website__c']) {
      $returnStr .= '    <div class="event_details_more_information"><a href="' . $event['Planned_Program_Website__c'] . '">More information about this program</a></div>' . PHP_EOL;
    }

    if (!empty($event['Registration_Link__c'])) {
     if ($now >= $regstartdate && $now <= $regenddate) {
      $returnStr .= '    <div class="event_details_registration"><a href="' . $event['Registration_Link__c'] . '">Register Online</a></div>' . PHP_EOL;
     } elseif ($now > $regenddate) {
      $returnStr .= '    <div class="event_details_registration">Registration Closed ' . date('M d, Y', $regenddate) . '</div>' . PHP_EOL;
     } else {
      $returnStr .= '    <div class="event_details_registration">Registration Opens ' . date('M d, Y', $regstartdate) . '</div>' . PHP_EOL;
     }
    }

    $returnStr .= '  </div>' . PHP_EOL;
    return $returnStr;
  }
}
