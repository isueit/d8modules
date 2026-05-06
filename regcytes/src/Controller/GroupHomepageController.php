<?php

// This help to display Homepage on the Group page

namespace Drupal\regcytes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;

class GroupHomepageController extends ControllerBase {

  public function view(GroupInterface $group) {
    $group = _regcytes_get_group_from_request();
    $nid = NULL;
    if ($group) {
      $group_id = $group->id();
      $content_type = 'event_homepage';

      // Load all group relationships for this group
      $relationships = \Drupal::entityTypeManager()
        ->getStorage('group_relationship') // still 'group_content' in 3.x storage
        ->loadByProperties([
          'gid' => $group_id,
        ]);

      // Extract node IDs
      $nids = [];
      foreach ($relationships as $relationship) {
        $entity = $relationship->getEntity();
        if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === $content_type) {
          $nids[] = $entity->id();
        }
      }

      if ($nids) {
        $nid = $nids[0];
      }
    }

    if ($nid) {
      $node = $this->entityTypeManager()->getStorage('node')->load($nid);
      if ($node && $node->access('view')) {
        $view_builder = $this->entityTypeManager()->getViewBuilder('node');
        return $view_builder->view($node, 'full');
      }
    }

    // Fallback: render the default group entity view.
    $view_builder = $this->entityTypeManager()->getViewBuilder('group');
    return $view_builder->view($group, 'full');
  }

  public function title(GroupInterface $group) {
    return $group->label();
  }
}
