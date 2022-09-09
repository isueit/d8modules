<?php

namespace Drupal\council_members\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Council Members' Block.
 *
 * @Block(
 *   id = "council_members",
 *   admin_label = @Translation("Council Members"),
 *   category = @Translation("Council Members"),
 * )
 */
class CouncilMembersBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    //Initialize some variables
    $config = \Drupal::config('council_members.settings');
    $results = '';
    $county_name = $config->get('county');
    $feed_url = $config->get('feed_url');

    // Make sure we have a County name and a URL for the feed
    if (!empty($county_name) && !empty($feed_url)) {
      $local_members = array();
      $active_council_members = json_decode(file_get_contents($feed_url), true);
      $results .= '<p class="council_note">(Number) is the year the term expires</p>';
      $results .= '<ul class="council_list">' . PHP_EOL;

      // Find council members for this county, and clean up some data
      foreach ($active_council_members as $member) {
        if ($member['Client_County__c'] == $county_name . ' County Extension') {
          if (empty($member['Extension_Council_Position__c'])) {
            $member['Extension_Council_Position__c'] = 'Member';
          }
          if (empty($member['hed__Contact__r.MailingState']) || $member['hed__Contact__r.MailingState'] === 'Iowa') {
            $member['hed__Contact__r.MailingState'] = 'IA';
          }
          $local_members[] = $member;
        }
      }

      // Display the council members
      $positions = ['Chair', 'Vice Chair', 'Secretary', 'Treasurer', 'Member'];
      foreach ($positions as $position) {
        foreach ($local_members as $member) {
          if ($member['Extension_Council_Position__c'] === $position) {
            $results .= '<li>' . PHP_EOL;
            $results .= '  <div class="council_name">'. $member['hed__Contact__r.FirstName'] . ' ' . $member['hed__Contact__r.LastName'] . '</div>' . PHP_EOL;
            $results .= '  <div class="council_date">(' . substr($member['hed__End_Date__c'], 0, 4) . ')</div>' . PHP_EOL;
            if ($position != 'Member') {
              $results .= '  <div class="council_position">' . $member['Extension_Council_Position__c'] . '</div>' . PHP_EOL;
            }
            $results .= '  <div class="council_city">'. $member['hed__Contact__r.MailingCity'] . ', ' . $member['hed__Contact__r.MailingState'] . '</div>' . PHP_EOL;
            $results .= '</li>' . PHP_EOL;
          }
        }
      }
      $results .= '</ul>' . PHP_EOL;
    }


    return [
      '#markup' => $results,
    ];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }
}
