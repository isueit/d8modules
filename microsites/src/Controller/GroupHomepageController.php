<?php

namespace Drupal\microsites\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redirects /group/{id} to the group's homepage node.
 *
 * The homepage is the first published node of MICROSITES_HOMEPAGE_BUNDLE
 * belonging to the group. If no such node exists, falls back to the first
 * published node of any type in the group.
 */
class GroupHomepageController extends ControllerBase {

  public function view(GroupInterface $group) {
    $homepage = $this->findHomepageNode($group);

    if ($homepage && $homepage->access('view')) {
      return $this->redirect('entity.node.canonical', ['node' => $homepage->id()], [], 301);
    }

    throw new NotFoundHttpException();
  }

  public function title(GroupInterface $group): string {
    return $group->label();
  }

  /**
   * Finds the homepage node for a group.
   *
   * Prefers a node of MICROSITES_HOMEPAGE_BUNDLE; falls back to the first
   * published node of any type.
   */
  private function findHomepageNode(GroupInterface $group): ?NodeInterface {
    $fallback = NULL;

    foreach (
      \Drupal::entityTypeManager()
        ->getStorage('group_relationship')
        ->loadByProperties(['gid' => $group->id()]) as $relationship
    ) {
      $entity = $relationship->getEntity();
      if (!$entity instanceof NodeInterface || !$entity->isPublished()) {
        continue;
      }
      if ($entity->bundle() === MICROSITES_HOMEPAGE_BUNDLE) {
        return $entity;
      }
      if ($fallback === NULL) {
        $fallback = $entity;
      }
    }

    return $fallback;
  }

}
