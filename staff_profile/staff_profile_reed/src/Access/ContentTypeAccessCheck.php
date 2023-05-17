<?php
namespace Drupal\staff_profile_reed\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if node type matches the one provided in the route configuration.
 */
class ContentTypeAccessCheck implements AccessInterface {

  public function access(Route $route, NodeInterface $node) {
    return AccessResult::allowedIf($node->getType() == $route->getRequirement('_content_type'));
  }
}
