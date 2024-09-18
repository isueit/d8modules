<?php

namespace Drupal\staff_profile_reed\Utils;

use Drupal;
use Drupal\staff_profile_reed\Controller\CountyWebEditors;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drush\Drush;

/**
 * Class MyHelperFunctions.
 */
class MyHelperFunctions
{

  /**
   * See: https://drupal/.stackexchange.com/questions/269995/how-to-use-helper-private-methods-with-a-module-file
   */

  /**
   * MyHelperFunctions constructor.
   */
  public function __construct()
  {
  }

  /**
   * Get list of counties served by current user.
   *
   * @return array
   *   Array of Taxonomy Terms that represent the counties
   */
  public function getCountiesServed()
  {
    // Get the config, and make sure it looks complete
    $primary_config = \Drupal::config('staff_profile_primary.settings');

    // Find Regional Director overrides and excludes
    $regional_directors_overrides = array();
    $overrides = explode("\r\n", $primary_config->get('reed_overrides'));
    foreach ($overrides as $override) {
      list($k, $v) = explode('|', $override);
      $regional_directors_overrides[strtolower(trim($k))] = explode(';', $v);
    }


    // Initiate variables
    $user = User::load(\Drupal::currentUser()->id());
    $username = $user->getAccountName();
    $counties = [];

    // Find counties
    if ($username == 'adminn') {
      $counties = $this->overrideCounties('all');
    } elseif (array_key_exists($username, $regional_directors_overrides)) {
      // Handle overrides
      switch ($regional_directors_overrides[$username][0]) {
        case 'all':
          $counties = $this->overrideCounties('all');
          break;
        case 'north':
          $counties = $this->overrideCounties('north');
          break;
        case 'south':
          $counties = $this->overrideCounties('south');
          break;
        default:
          $counties = $this->overrideCounties('special', $regional_directors_overrides[$username]);
          break;
      }
    } else {
      // Use counties from staff directory
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'staff_profile', 'field_staff_profile_netid' => $username]);
      if ($node = reset($nodes)) {
        $counties = $node->field_staff_profile_cty_served->referencedEntities();
      }
    }

    return $counties;
  }

  private function overrideCounties($area, $override_counties = [])
  {
    $north = [
      'Allamakee', 'Black Hawk', 'Bremer', 'Buchanan', 'Buena Vista', 'Butler', 'Benton', 'Calhoun',
      'Cedar', 'Cerro Gordo', 'Cherokee', 'Chickasaw', 'Clay', 'Clayton', 'Delaware', 'Dickinson',
      'Dubuque', 'Emmet', 'Fayette', 'Floyd', 'Franklin', 'Grundy', 'Hamilton', 'Hancock', 'Hardin',
      'Howard', 'Humboldt', 'Ida', 'Jackson', 'Jones', 'Kossuth', 'Linn', 'Lyon', 'Marshall',
      'Mitchell', 'Monona', 'O\'Brien', 'Osceola', 'Palo Alto', 'Plymouth', 'Pocahontas', 'Sac',
      'Sioux', 'Story', 'Tama', 'Webster', 'Winnebago', 'Winneshiek', 'Woodbury', 'Worth', 'Wright',
    ];

    $south = [
      'Adair', 'Adams', 'Appanoose', 'Audubon', 'Boone', 'Carroll', 'Cass', 'Clarke', 'Clinton',
      'Crawford', 'Dallas', 'Davis', 'Decatur', 'Des Moines', 'Fremont', 'Greene', 'Guthrie', 'Harrison',
      'Henry', 'Iowa', 'Jasper', 'Jefferson', 'Johnson', 'Keokuk', 'Lee', 'Louisa', 'Lucas', 'Madison',
      'Mahaska', 'Marion', 'Mills', 'Monroe', 'Montgomery', 'Muscatine', 'Page', 'Polk',
      'Pottawattamie - East', 'Pottawattamie - West', 'Poweshiek', 'Ringgold', 'Scott', 'Shelby',
      'Taylor', 'Union', 'Van Buren', 'Wapello', 'Warren', 'Washington', 'Wayne',
    ];
    $counties = [];
    $terms = [];
    $termids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('counties_in_iowa');

    foreach ($termids as $term) {
      $tmp = Term::load($term->tid);
      switch ($area) {
        case 'all':
          $counties[] = $tmp;
          break;
        case 'north':
          if (in_array($tmp->label(), $north)) {
            $counties[] = $tmp;
          }
          break;
        case 'south':
          if (in_array($tmp->label(), $south)) {
            $counties[] = $tmp;
          }
          break;
        case 'special':
          if (in_array($tmp->label(), $override_counties)) {
            $counties[] = $tmp;
          }
          break;
      }
    }

    return $counties;
  }
}
