<?php
namespace Drupal\staff_profile_primary\Commands;
use Drush\Commands\DrushCommands;
use \Drupal\node\Entity\Node;

/**
 * Drush Commands
 * @package Drupal\staff_profile_primary\Commands
 */
class StaffProfilePrimaryCommands extends DrushCommands {

  /**
   * Drush command to add Web Editor and Drupal Training to Staff Profiles
   * @command staff_profile_primary:addeditor
   *
   * @aliases add-editor
   *
   * @param string $netid
   *  Net ID of user
   * @options course
   *  The course to be added, use the drupal version number
   *
   * @usage staff_profile_primary:addeditor bwebster --course=8
   *  Add Web Editor and Drupal 8 Qualifications to staff profile bwebster
   * @usage add-editor bwebster
   *  Add Web Editor to staff profile bwebster
   */
  public function addeditor($netid, $options = ['course' => ""]) {
    $nids = \Drupal::entityQuery('node')->accessCheck(false)->condition('type', 'staff_profile')->condition('field_staff_profile_netid', $netid)->execute();

    if ($nid = reset($nids) && $nid !== FALSE) {
      $node =  Node::load(reset($nids));

      $qual_objs = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('editor_qualifications');
      $qualifications = [];
      foreach ($qual_objs as $key => $value) {
        $qualifications[$value->name] = $value->tid;
      }

      if (!in_array($qualifications["Web Editor"], array_column($node->field_staff_profile_quals->getValue(), 'target_id'))) {
        $node->field_staff_profile_quals[] = ['target_id' => $qualifications["Web Editor"]];
      }

      if ($options['course'] != "" && is_numeric($options['course']) && array_key_exists("Drupal " . $options['course'] . " Training", $qualifications)) {
        if (!in_array($qualifications["Drupal " . $options['course'] . " Training"], array_column($node->field_staff_profile_quals->getValue(), 'target_id'))) {
          $node->field_staff_profile_quals[] = ['target_id' => $qualifications["Drupal " . $options['course'] . " Training"]];
        }
        $this->output()->writeln("Added Web Editor and Drupal " . $options['course'] . " Training to Staff Profile: " . $netid);
      } else {
        $this->output()->writeln("Added Web Editor to Staff Profile: " . $netid);
        if ($options['course'] != "" && is_numeric($options['course'])) {
          $this->output()->writeln("Drupal " . $options['course'] . " Training qualification not found");
        } elseif ($options['course'] != "") {
          $this->output()->writeln("use --course=[drupal major version], ex: --course=8");
        }
      }
      $node->save();
    } else {
      $this->output()->writeln("No Staff Profile found with NetID: " . $netid);
    }
  }
}
