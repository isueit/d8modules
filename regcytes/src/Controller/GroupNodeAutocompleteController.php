<?php

namespace Drupal\regcytes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GroupNodeAutocompleteController extends ControllerBase {

  public function autocomplete(Request $request, GroupInterface $group): JsonResponse {
    $typed = mb_strtolower($request->query->get('q', ''));
    $results = [];

    // Find all group_relationship_type config entities that:
    // - belong to this group's type
    // - are for node entities (plugin ID starts with group_node:)
    $group_type_id = $group->getGroupType()->id();

    $relationship_types = \Drupal::entityTypeManager()
      ->getStorage('group_relationship_type')
      ->loadMultiple();

    $node_plugin_ids = [];
    foreach ($relationship_types as $relationship_type) {
      // Each relationship type has a group_type and a plugin_id.
      if ($relationship_type->getGroupTypeId() !== $group_type_id) {
        continue;
      }
      $plugin_id = $relationship_type->getPluginId();
      if (str_starts_with($plugin_id, 'group_node:')) {
        $node_plugin_ids[] = $plugin_id;
      }
    }

    if (empty($node_plugin_ids)) {
      return new JsonResponse($results);
    }

    // Load related nodes for each installed node plugin.
    foreach ($node_plugin_ids as $plugin_id) {
      $entities = $group->getRelatedEntities($plugin_id);

      foreach ($entities as $entity) {
        if (!$entity instanceof NodeInterface) {
          continue;
        }
        if (!$entity->isPublished()) {
          continue;
        }
        if ($typed && stripos($entity->label(), $typed) === FALSE) {
          continue;
        }
        $nid = $entity->id();
        if (isset($results[$nid])) {
          continue;
        }
        $results[$nid] = [
          //'value' => $entity->label() . ' (entity:node/' . $nid . ')',
          'value' => $entity->label() . ' (' . $nid . ')',
          'label' => $entity->label(),
        ];
      }
    }

    return new JsonResponse(array_values($results));
  }

}
