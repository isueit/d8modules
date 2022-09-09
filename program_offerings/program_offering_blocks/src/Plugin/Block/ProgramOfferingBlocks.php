<?php

namespace Drupal\program_offering_blocks\Plugin\Block;

use DateInterval;
use DateTime;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;


use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\program_offering_blocks\Controller\Helpers;

/**
 * Provides a 'ProgramOfferingBlocks' block plugin.
 *
 * @Block(
 *   id = "program_offering_blocks",
 *   admin_label = @Translation("Program Offering blocks"),
 *   deriver = "Drupal\program_offering_blocks\Plugin\Derivative\ProgramOfferingBlocks"
 * )
 */


class ProgramOfferingBlocks extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    global $base_url;

    // Do NOT cache a page with this block on it
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Initalize variables
    $results = '';
    $count = 0;
    $id = $this->getDerivativeID();
    $config = $this->getConfiguration();
    $module_config = \Drupal::config('program_offering_blocks.settings');

    // Show annoncement if there is one
    if (!empty($config['announcement_text'])) {
      $results .= PHP_EOL . '<div class="program_offering_blocks_announcement_text">' . $config['announcement_text'] . '</div>' . PHP_EOL;
    }

    // Get the maximum events to display
    $max_events = intval($config['max_events']);
    if ($max_events == 0) {
      $max_events = PHP_INT_MAX;
    }

    // Get the current node
    $node = \Drupal::routeMatch()->getParameter('node');

    // Check for a filter in the query string
    $querystring_filter = \Drupal::request()->query->get('filter');
    if (!empty($querystring_filter)) {
      $querystring_filter = urldecode($querystring_filter);
    }

    // Combine all search filters, into a $search_terms array()
    $string_of_search_terms = $this->build_search_string(!empty($node->field_event_title_filter->value) ? $node->field_event_title_filter->value : '', $config['title_search']);
    $string_of_search_terms = $this->build_search_string($string_of_search_terms, $querystring_filter);
    $search_terms_array = explode('|', $string_of_search_terms);

    // Set the timeout to 2 seconds, Get the events from the JSON feed, then reset timeout to previous value
    $default_socket_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 2);
    $buffer = file_get_contents($module_config->get('url'));
    ini_set('default_socket_timeout', $default_socket_timeout);
    $json_events = json_decode($buffer, TRUE);

    $results .= PHP_EOL . '<ul class="program_offering_blocks program_offering_blocks_' . $id . '">' . PHP_EOL;

    foreach ($json_events as $event) {
      $display_event = TRUE;
      if (!empty($config['program_area']) && $config['program_area'] != $event['PrimaryProgramUnit__c']) {
        $display_event = FALSE;
      }

      // Do we only display planned programs
      // This may someday need to be a separate function if we have to distingish the type of Associated Products, but this is OK for now
      if ($config['only_planned_programs'] && empty($event['All_Associated_Product_ID_s__c'])) {
        $display_event = FALSE;
      }

      // Skip nonpublic events unless "show_nonpublic_events" checkbox is selected in block config
      if ($event['Public_Event__c'] == '0' && !$config['show_nonpublic_events']) {
        $display_event = FALSE;
      }

      if (!empty($string_of_search_terms)) {
        if (!$this->search_term_in_title(strtolower($event['Name_Placeholder__c']), $search_terms_array)) {
          $display_event = FALSE;
        }
      }

      if (!empty($config['county'])) {
        $search_county = strtolower($config['county']) . ' county';
        if ($search_county == 'pottawattamie - west county') {
          $search_county = 'west pottawattamie county';
        }
        if ($search_county == 'pottawattamie - east county') {
          $search_county = 'east pottawattamie county';
        }
        if (
          !(strpos(strtolower($event['Account__c.Name']), $search_county) !== FALSE)
          && !(strpos(strtolower($event['Additional_Counties__c']), $search_county) !== FALSE)
        ) {
          $display_event = FALSE;
        }
      }

      if ($display_event) {
        if ($count < $max_events) {
          $start_date = strtotime($event['Next_Start_Date__c']);
          $results .= '  <li class="event">' . PHP_EOL;
          $results .= '    <div class="event_date"><span class="event_day">' . date('d', $start_date) . '</span>
            <span class="event_month">' . date('M', $start_date) . '</span>
            <span class="event_time">' . date('g:i', $start_date) . '</span><span class="event_ampm">' . date('A', $start_date) . '</span></div>';

          $results .= $this->format_title($event, $config) . PHP_EOL;
          $results .= '    <div class="event_venue">';
          $results .= $event['Event_Location__c'] == 'Online' ? 'Online' : $event['Event_Location__c'] . ', ' . $event['Program_State__c'];
          $results .= '</div>' . PHP_EOL;

          $startDate = date($config['format_with_time'], strtotime($event['Next_Start_Date__c']));
          $results .= '    <div class="event_startdate">' . $startDate . '</div>' . PHP_EOL;

          $results .= '  </li>' . PHP_EOL;
        }
        $count++;
      }
    }

    $results .= '</ul>' . PHP_EOL;

    // Use Javascript to hide block if it's not showing any events (Should this be an option in config?)
    if (0 == $count) {
      $results .= '<script>document.getElementById("block-programofferingblock' . $id . '").style.display = "none";</script>';
    }

    if (!empty($config['show_more_page']) && !empty($config['show_more_text']) && $count > $max_events) {
      $results .= '<a class="events_show_more btn-outline-white" href="' . $base_url . '/' . $config['show_more_page'] . '?filter=' . urlencode($string_of_search_terms) . '">' . $config['show_more_text'] . '</a><br />';
    }

    return [
      '#markup' => $this->t($results),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account, $return_as_object = FALSE)
  {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $config = $this->getConfiguration();

    $form['max_events'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum Number of Events to Display'),
      '#description' => t('Zero (0) means display all events'),
      '#size' => 15,
      '#default_value' => $config['max_events'],
    );

    $form['event_details_page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Link to Details Page'),
      '#description' => t('When checked, every event title will link to a details page, otherwise, titles will link to registration pages where available'),
      '#default_value' => $config['event_details_page'],
    );

    $form['format_with_time'] = array(
      '#type' => 'textfield',
      '#title' => t('Date/Time Format'),
      '#description' => t('Format of the date, see <a href="http://php.net/manual/en/function.date.php">php date manual</a>'),
      '#default_value' => $config['format_with_time'],
    );

    $form['format_without_time'] = array(
      '#type' => 'textfield',
      '#title' => t('Date Only Format'),
      '#description' => t('Use this format when the time is 12:00 am (midnight)'),
      '#default_value' => $config['format_without_time'],
    );

    $form['title_search'] = array(
      '#type' => 'textfield',
      '#title' => t('Restrict Search by Title'),
      '#description' => t('Only show events with this search term in title, blank means show all events'),
      '#default_value' => $config['title_search'],
    );

    $form['show_more_page'] = array(
      '#type' => 'textfield',
      '#title' => t('Show More Events Page'),
      '#description' => t('Path to add to base URL where all events are listed'),
      '#default_value' => $config['show_more_page'],
    );
    $form['show_more_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text for Show More Events Link'),
      '#description' => t('Text that\'s used in the anchor tag'),
      '#default_value' => $config['show_more_text'],
    );

    $form['announcement_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Announcement Text'),
      '#description' => t('Text to be placed above the events in this block. Used for special announcements, ie COVID-19 cancellations'),
      '#size' => 75,
      '#maxlength' => 300,
      '#default_value' => $config['announcement_text'],
    );

    $form['show_nonpublic_events'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include Non-public Events'),
      '#description' => t('When checked, it will show all matching events, including events marked as not for the public'),
      '#default_value' => $config['show_nonpublic_events'],
    );

    $form['only_planned_programs'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Only Planned Programs'),
      '#description' => t('When checked, only show events that are in the list of planned programs. Skip the other lists'),
      '#default_value' => $config['only_planned_programs'],
    );

    $form['program_area'] = array(
      '#type' => 'select',
      '#options' => [
        '' => $this->t('Include All'),
        '4-H Youth Development' => $this->t('4-H Youth Development'),
        'Agriculture and Natural Resources' => $this->t('Agriculture and Natural Resources'),
        'Community and Economic Development' => $this->t('Community and Economic Development'),
        'County Services' => $this->t('County Services'),
        'Human Sciences' => $this->t('Human Sciences'),
        'Professional Development' => $this->t('Professional Development'),
      ],
      '#title' => t('Program Area'),
      '#description' => t('If something is selected, then only show events for that program area'),
      '#default_value' => $config['program_area'],
    );

    // Get the list of Iowa Counties
    $taxonomy_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'counties_in_iowa']);
    if (sizeof($taxonomy_terms) > 0) {
      $counties = array('' => 'Include All');
      foreach ($taxonomy_terms as $taxonomy_term) {
        $counties[$taxonomy_term->label()] = $taxonomy_term->label();
      }

      $form['county'] = array(
        '#type' => 'select',
        '#options' => $counties,
        '#title' => t('Limit By county'),
        '#description' => t('If something is selected, then only show events for that county'),
        '#default_value' => $config['county'],
      );
    }

    $form['placement'] = array(
      '#type' => 'textfield',
      '#title' => t('Placed on Page'),
      '#description' => t('Documentation: what page(s) the block is placed on'),
      '#size' => 75,
      '#maxlength' => 300,
      '#default_value' => $config['placement'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();

    $this->configuration['event_details_page'] = $values['event_details_page'];
    $this->configuration['max_events'] = $values['max_events'];
    $this->configuration['format_with_time'] = $values['format_with_time'];
    $this->configuration['format_without_time'] = $values['format_without_time'];
    $this->configuration['title_search'] = $values['title_search'];
    $this->configuration['show_more_page'] = $values['show_more_page'];
    $this->configuration['show_more_text'] = $values['show_more_text'];
    $this->configuration['announcement_text'] = $values['announcement_text'];
    $this->configuration['show_nonpublic_events'] = $values['show_nonpublic_events'];
    $this->configuration['only_planned_programs'] = $values['only_planned_programs'];
    $this->configuration['program_area'] = $values['program_area'];
    $this->configuration['county'] = array_key_exists('county', $values) ? $values['county'] : '';
    $this->configuration['placement'] = $values['placement'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return array(
      'event_details_page' => TRUE,
      'max_events' => 0,
      'format_with_time' => 'M j, Y, g:i a',
      'format_without_time' => 'M j, Y',
      'title_search' => '',
      'show_more_page' => '',
      'show_more_text' => 'More',
      'announcement_text' => '',
      'show_nonpublic_events' => FALSE,
      'only_planned_programs' => FALSE,
      'program_area' => '',
      'county' => '',
      'placement' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge()
  {
    return 0;
  }

  /**
   * Format Date
   */
  private function format_date_time($datetime)
  {
    $config = $this->getConfiguration();

    if (date('Gi', $datetime) == '0000') {
      $datetimestr = date($config['format_without_time'], $datetime);
    } else {
      $datetimestr = date($config['format_with_time'], $datetime);
    }

    return $datetimestr;
  }
  /**
   * Format the title and get it ready to output
   */
  private function format_title($event, $config)
  {
    $title = '<div class="event_title">';
    $title_text = $event['Name_Placeholder__c'];

    // Append language to the end of the title, when it's not English
    if (!empty($event['Delivery_Language__c']) && 'english' != strtolower($event['Delivery_Language__c'])) {
      $title_text .= ' - ' . $event['Delivery_Language__c'];
    }

    if ($config['event_details_page']) {
      $title .= '<a href="' . base_path() . 'event_details/' . $event['Id'] . '/' . str_replace('/', '-', $event['Name_Placeholder__c']) . '">' . $title_text . '</a>';
    } else {
      $now = strtotime('today midnight');
      $regstartdate = !empty($event['Registration_Opens__c']) ? strtotime($event['Registration_Opens__c']) : $now;
      $regenddate = !empty($event['Registration_Deadline__c']) ? strtotime($event['Registration_Deadline__c']) : $now;
      $regenddate = date_add(new DateTime('@' . $regenddate), new DateInterval('P1D'))->getTimestamp();

      if (!empty($event['Registration_Link__c']) && ($now >= $regstartdate && $now <= $regenddate)) {
        $title .= '<a href="' . $event['Registration_Link__c'] . '">' . $title_text . '</a>';
      } elseif (!empty($event['Planned_Program_Website__c'])) {
        $title .= '<a href="' . $event['Planned_Program_Website__c'] . '">' . $title_text . '</a>';
      } else {
        $title .= $title_text;
      }
    }
    $title .= '</div>';

    return $title;
  }

  /**
   * Combine two search strings into 1
   */
  private function build_search_string($str1, $str2)
  {
    $return_string = '';
    $str1 = trim(strtolower($str1));
    $str2 = trim(strtolower($str2));
    if (!empty($str1) && !empty($str2)) {
      $return_string = $str1 . '|' . $str2;
    } elseif (!empty($str1)) {
      $return_string = $str1;
    } else {
      $return_string = $str2;
    }

    return $return_string;
  }

  /**
   * Determine if search term is in title
   */
  private function search_term_in_title($title, $search_terms)
  {
    $found_term = FALSE;
    foreach ($search_terms as $search_term) {
      $search_term = trim($search_term);
      if (!empty($search_term) &&  strpos(strtolower($title), $search_term) !== false) {
        $found_term = TRUE;
      }
    }
    return $found_term;
  }
}
