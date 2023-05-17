<?php
namespace Drupal\staff_profile_reed\Controller;

use \Drupal\Core\Controller\ControllerBase;
use \Drupal\user\Entity\User;
/**
 * Regional Director Panels
 */
class CouncilMembers extends ControllerBase {

  public function panel() {
    $council_members = [];
    $counties = \Drupal::service('staff_profile_reed.helper_functions')->getCountiesServed();
    $raw = json_decode(file_get_contents('https://datastore.exnet.iastate.edu/mydata/ExtensionCouncilMembers.json'), true);
    foreach ($raw as $member) {
      $member['Client_County__c'] = str_replace('East Pottawattamie', 'Pottawattamie - East', $member['Client_County__c']);
      $member['Client_County__c'] = str_replace('West Pottawattamie', 'Pottawattamie - West', $member['Client_County__c']);
      $council_members[$member['Client_County__c']][] = $member;
    }

    $result = [];

    $result['#markup'] = '<p>These are the council members for each county.<br/>
        To add a member, you need to go to MyData, to modify/remove a member, click on their name below.
      </p>';

    foreach ($counties as $key => $county) {
      $chair = $vchair = $secretary = $treasurer = $other = '';
      foreach ($council_members[$county->label() . ' County Extension'] as $member) {
        $list_item = '  <li><a href="https://extension-iastate.lightning.force.com/lightning/r/Contact/' . $member['hed__Contact__r.Id'] . '/view">' . $member['hed__Contact__r.FirstName'] . ' ' . $member['hed__Contact__r.LastName'] . '</a>';
        $list_item .= ' (' . substr($member['hed__End_Date__c'], 0, 4) . ')';
        $list_item .= $member['Extension_Council_Position__c'] == 'Member' ? '' : ' - ' . $member['Extension_Council_Position__c'];
        $list_item .= '</li>' . PHP_EOL;
        switch ($member['Extension_Council_Position__c']){
          case 'Chair':
            $chair .= $list_item;
            break;
          case 'Vice Chair':
            $vchair .= $list_item;
            break;
          case 'Secretary':
            $secretary .= $list_item;
            break;
          case 'Treasurer':
            $treasurer .= $list_item;
            break;
          default:
            $other .= $list_item;
            break;
        }
      }
      $html = PHP_EOL . '<ul>' . PHP_EOL;
      $html .= $chair . $vchair . $secretary . $treasurer . $other;
      $html .= '</ul>' . PHP_EOL;
      $result[$county->label()] = array(
        '#type' => 'fieldset',
        "#title" => $this->t($county->label())
      );

      $result[$county->label()]['description'] = [
        '#markup' => $html,
      ];
    }
    return $result;
  }

}
