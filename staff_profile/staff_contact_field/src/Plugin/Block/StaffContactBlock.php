<?php

namespace Drupal\staff_contact_field\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Staff Contact' Block.
 *
 * @Block(
 *   id = "staff_contact_field",
 *   admin_label = @Translation("Staff Contact Block"),
 *   category = @Translation("Staff Contact "),
 * )
 */
class StaffContactBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    global $base_url;
    //Initialize some variables
    $results = '';

    // Get the Staff Profiles from this site
    $nids = \Drupal::entityQuery('node')->accessCheck(false)->condition('type', 'staff_profile')->condition('status', 1)->sort('field_staff_profile_last_name')->sort('field_staff_profile_first_name')->execute();
    $staff_nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);

    // Get the corrent node
    $node = \Drupal::routeMatch()->getParameter('node');

    // Make sure it is a node
    if ($node instanceof \Drupal\node\NodeInterface) {
      // Step through the fields of the node
      $fields = $node->getFields();
      foreach ($fields as $field) {
        // Looking for staff_contact_field type(s)
        if ($field->getFieldDefinition()->getType() == 'staff_contact_field') {
          // Handle multiple values for one field
          foreach ($field->getValue() as $conact_list) {
            // Set up some html elements
            $results .= '<div class="contact_group">' . PHP_EOL;
            $contacts = unserialize($conact_list['contacts']);
            arsort($contacts);
            $results .= '<div class="staff_contact_header">' . $conact_list['contact_header'] . '</div>' . PHP_EOL;
            $results .= '<ul class="staff_contact_list">' . PHP_EOL;

            foreach ($contacts as $key => $value) {
              // Find the right staff member and output...
              foreach ($staff_nodes as $staff_member) {
                if ($staff_member->id() == $key) {
                  /*
                  switch ($conact_list['contact_display']) {
                    case 'short':
                      $results .= '<li><a href="' . $base_url . \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $staff_member->id()) . '">' . $staff_member->getTitle() . '</a></li>' . PHP_EOL;
                      break;
                    case 'medium':
                      $results .= '<li>';
                      $results .= '<a href="' . $base_url . \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $staff_member->id()) . '">' . $staff_member->getTitle() . '</a><br/>' . PHP_EOL;
                      $results .= $staff_member->get('field_staff_profile_pref_title')->value . '<br/>'. PHP_EOL;
                      $results .= '<a href="mailto:' . $staff_member->get('field_staff_profile_email')->value . '">' . $staff_member->get('field_staff_profile_email')->value . '</a><br/>' . PHP_EOL;
                      $results .= $staff_member->get('field_staff_profile_pref_phone')->value . PHP_EOL;
                      $results .= '</li>';
                      break;
                    case 'long':
                      $smugmug_id = $staff_member->get('field_staff_profile_smugmug')->value;
                      $results .= '<li>';
                      $results .= '<img src="https://photos.smugmug.com/photos/' . $smugmug_id . '/0/XL/' . $smugmug_id . '-XL.jpg"/>' . '<br/>' . PHP_EOL;
                      $results .= '<a href="' . $base_url . \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $staff_member->id()) . '">' . $staff_member->getTitle() . '</a><br/>' . PHP_EOL;
                      $results .= $staff_member->get('field_staff_profile_pref_title')->value . '<br/>'. PHP_EOL;
                      $results .= '<a href="mailto:' . $staff_member->get('field_staff_profile_email')->value . '">' . $staff_member->get('field_staff_profile_email')->value . '</a><br/>' . PHP_EOL;
                      $results .= $staff_member->get('field_staff_profile_pref_phone')->value . PHP_EOL;
                      $results .= '</li>';
                      break;
                  }
                  */

                      $smugmug_id = $staff_member->get('field_staff_profile_smugmug')->value;
                      $results .= '<li>';
                      if ($conact_list['contact_display'] == 'long') {
                        $results .= '<div class="contact_photo">';
                        if (!empty($smugmug_id)) {
                          $results .= '<img src="https://photos.smugmug.com/photos/' . $smugmug_id . '/0/XL/' . $smugmug_id . '-XL.jpg" alt=" ' . $staff_member->getTitle() . '"/>';
                        }
                        $results .= '</div>' . PHP_EOL;
                      }
                      $results .= '<div class="contact_name">' . '<a href="' . $base_url . \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $staff_member->id()) . '">' . $staff_member->getTitle() . '</a></div>' . PHP_EOL;
                      if ($conact_list['contact_display'] != 'short') {
                        if (!empty($staff_member->get('field_staff_profile_pref_title')->value)) {
                          $results .= '<div>' . $staff_member->get('field_staff_profile_pref_title')->value . '</div>'. PHP_EOL;
                        }
                        $results .= '<div>' . '<a href="mailto:' . $staff_member->get('field_staff_profile_email')->value . '">' . $staff_member->get('field_staff_profile_email')->value . '</a></div>' . PHP_EOL;
                        $results .= '<div>' . $staff_member->get('field_staff_profile_pref_phone')->value . '</div>' . PHP_EOL;
                        $results .= '<hr/>' . PHP_EOL;
                      }
                      $results .= '</li>';

                }
              }
            }
             // close out html elements
            $results .= '</ul>' . PHP_EOL;
            $results .= '</div>' . PHP_EOL;
          }
        }
      }
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
