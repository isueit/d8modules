<?php

namespace Drupal\regcytes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GroupHomepageController extends ControllerBase {

  public function view(GroupInterface $group) {
    $group = _regcytes_get_group_from_request();
    if (!$group) {
      throw new AccessDeniedHttpException();
    }

    // The group has no public path alias — /group/{id} is the only way to
    // reach this controller. Redirect to the event_homepage node, which holds
    // the public alias (e.g. /conference-slug).
    foreach (\Drupal::entityTypeManager()
      ->getStorage('group_relationship')
      ->loadByProperties(['gid' => $group->id()]) as $relationship) {
      $entity = $relationship->getEntity();
      if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'event_homepage') {
        if ($entity->access('view')) {
          return $this->redirect('entity.node.canonical', ['node' => $entity->id()], [], 301);
        }
      }
    }

    throw new AccessDeniedHttpException();
  }

  public function title(GroupInterface $group) {
    return $group->label();
  }
}
