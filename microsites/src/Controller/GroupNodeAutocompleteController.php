<?php

namespace Drupal\microsites\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autocomplete endpoint that returns published nodes belonging to a group.
 *
 * Used by the menu link form to let editors link directly to group content.
 */
class GroupNodeAutocompleteController extends ControllerBase {

  public function autocomplete(Request $request, GroupInterface $group): JsonResponse {
    $typed        = mb_strtolower($request->query->get('q', ''));
    $results      = [];
    $group_type_id = $group->getGroupType()->id();

    // Collect all node-relationship plugin IDs configured for this group type.
    $node_plugin_ids = [];
    foreach (
      \Drupal::entityTypeManager()
        ->getStorage('group_relationship_type')
        ->loadMultiple() as $relationship_type
    ) {
      if ($relationship_type->getGroupTypeId() !== $group_type_id) {
        continue;
      }
      if (str_starts_with($relationship_type->getPluginId(), 'group_node:')) {
        $node_plugin_ids[] = $relationship_type->getPluginId();
      }
    }

    if (empty($node_plugin_ids)) {
      return new JsonResponse([]);
    }

    foreach ($node_plugin_ids as $plugin_id) {
      foreach ($group->getRelatedEntities($plugin_id) as $entity) {
        if (!$entity instanceof NodeInterface || !$entity->isPublished()) {
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
          'value' => $entity->label() . ' (' . $nid . ')',
          'label' => $entity->label(),
        ];
      }
    }

    return new JsonResponse(array_values($results));
  }

}
