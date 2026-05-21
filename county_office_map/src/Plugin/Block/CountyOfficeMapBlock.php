<?php

namespace Drupal\county_office_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'County Office Map' Block.
 *
 * @Block(
 *   id = "county_office_map_block",
 *   admin_label = @Translation("County Office Map"),
 *   category = @Translation("ISUEO"),
 * )
 */
class CountyOfficeMapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load all county terms from the counties_in_iowa vocabulary
    $county_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'counties_in_iowa']);
    
    $counties = [];
    
    foreach ($county_terms as $term) {
      $county_name = $term->getName();
      $svg_id = $this->getCountySvgId($county_name);
      
      $county_data = [
        'tid' => $term->id(),
        'name' => $county_name,
        'svg_id' => $svg_id,
      ];
      
      if ($term->hasField('field_address') && !$term->get('field_address')->isEmpty()) {
        $county_data['address'] = $term->get('field_address')->value;
      }
      
      if ($term->hasField('field_phone') && !$term->get('field_phone')->isEmpty()) {
        $county_data['phone'] = $term->get('field_phone')->value;
      }
      
      if ($term->hasField('field_region') && !$term->get('field_region')->isEmpty()) {
        $county_data['region'] = $term->get('field_region')->value;
      }
      
      if ($term->hasField('field_website') && !$term->get('field_website')->isEmpty()) {
        $county_data['website'] = rtrim($term->get('field_website')->uri, '/') . '/';
      }
      
      // Find Regional Director for this county
      $regional_director = $this->getRegionalDirector($term->id());
      if ($regional_director) {
        $county_data['regional_director'] = $regional_director;
      }
      
      $counties[$svg_id] = $county_data;
    }
    
    // Get the SVG file and read it
    $module_path = \Drupal::service('extension.list.module')->getPath('county_office_map');
    $svg_full_path = DRUPAL_ROOT . '/' . $module_path . '/assets/iowa-map.svg';
    
    $svg_content = '';
    if (file_exists($svg_full_path)) {
      $svg_content = file_get_contents($svg_full_path);
      // Strip XML declaration
      $svg_content = preg_replace('/<\?xml.*?\?>\s*/s', '', $svg_content);
    }
    
    return [
      'content' => [
        '#svg_content' => $svg_content,
        '#counties_count' => count($counties),
        '#attached' => [
          'library' => ['county_office_map/map'],
          'drupalSettings' => [
            'countyOfficeMap' => [
              'counties' => $counties,
            ],
          ],
        ],
        '#cache' => [
          'tags' => ['taxonomy_term_list:counties_in_iowa', 'node_list:staff_profile'],
          'max-age' => 3600,
        ],
      ],
    ];
  }
  
  /**
   * Get Regional Director for a given county.
   */
  private function getRegionalDirector($county_tid) {
    // First, get the "Regional Director" term ID from staff_positions vocabulary
    $position_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'staff_positions',
        'name' => 'Regional Director',
      ]);
    
    if (empty($position_terms)) {
      return NULL;
    }
    
    $regional_director_tid = reset($position_terms)->id();
    
    // Query for staff profiles with Regional Director position
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'staff_profile')
      ->condition('status', 1)
      ->condition('field_staff_profile_position', $regional_director_tid);
    
    // Check if this county is in base_county OR counties_served
    $or_group = $query->orConditionGroup()
      ->condition('field_staff_profile_base_county', $county_tid)
      ->condition('field_staff_profile_cty_served', $county_tid);
    
    $query->condition($or_group);
    $nids = $query->execute();
    
    if (empty($nids)) {
      return NULL;
    }
    
    // Load the first matching staff profile
    $staff_node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load(reset($nids));
    
    if (!$staff_node) {
      return NULL;
    }
    
    $director_data = [];
    
    // Name: Use preferred name + last name, or fall back to title
    if (!$staff_node->get('field_staff_profile_pref_name')->isEmpty()) {
      $first_name = $staff_node->get('field_staff_profile_pref_name')->value;
      $last_name = $staff_node->get('field_staff_profile_last_name')->value ?? '';
      $director_data['name'] = trim($first_name . ' ' . $last_name);
    } else {
      $director_data['name'] = $staff_node->getTitle();
    }
    
    // Email
    if (!$staff_node->get('field_staff_profile_email')->isEmpty()) {
      $director_data['email'] = $staff_node->get('field_staff_profile_email')->value;
    }
        
  // NetID for building profile URL
    $netid = '';
    if (!$staff_node->get('field_staff_profile_netid')->isEmpty()) {
      $netid = $staff_node->get('field_staff_profile_netid')->value;
    } elseif (!empty($director_data['email'])) {
      // Extract from email if netid field is empty
      $netid = explode('@', $director_data['email'])[0];
    }
    
    if (!empty($netid)) {
      $director_data['netid'] = $netid;
    }
    
    // Phone: Preferred phone, or fall back to regular phone
    if (!$staff_node->get('field_staff_profile_pref_phone')->isEmpty()) {
      $director_data['phone'] = $staff_node->get('field_staff_profile_pref_phone')->value;
    } elseif (!$staff_node->get('field_staff_profile_phone')->isEmpty()) {
      $director_data['phone'] = $staff_node->get('field_staff_profile_phone')->value;
    }
    
    // Image: Get Smugmug image URL
    if (!$staff_node->get('field_staff_profile_smugmug')->isEmpty()) {
      $smugmug_field = $staff_node->get('field_staff_profile_smugmug')->first();
      if ($smugmug_field && !empty($smugmug_field->value)) {
        // Use the ISUEO helper to build the Smugmug URL
        $smugmug_id = $smugmug_field->value;
        $director_data['image_url'] = \Drupal\isueo_helpers\ISUEOHelpers\General::build_smugmug_url($smugmug_id, 'M');
      }
    }
    
    // If no Smugmug image, use blank image
    if (empty($director_data['image_url'])) {
      $director_data['image_url'] = \Drupal::request()->getBaseUrl() . '/modules/custom/d8modules/staff_profile/staff_profile/images/blank_image.png';
    }
    
    return $director_data;
  }
  
  /**
   * Convert county name to SVG ID.
   */
  private function getCountySvgId($county_name) {
    $name = str_replace(' County', '', $county_name);
    
    $special_cases = [
      "O'Brien" => 'obrien',
      'Black Hawk' => 'blackhawk',
      'Van Buren' => 'vanburen',
      'Cerro Gordo' => 'cerrogordo',
      'Buena Vista' => 'buenavista',
      'Palo Alto' => 'paloalto',
      'Des Moines' => 'desmoines',
      'Pottawattamie - East' => 'eastpottawattamie',
      'Pottawattamie - West' => 'westpottawattamie',
    ];
    
    return $special_cases[$name] ?? strtolower(str_replace(' ', '', $name));
  }

}