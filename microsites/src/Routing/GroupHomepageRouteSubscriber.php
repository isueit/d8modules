<?php

namespace Drupal\microsites\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Replaces the canonical group route with the microsites homepage redirect.
 */
class GroupHomepageRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('entity.group.canonical')) {
      $route->setDefault('_controller', '\Drupal\microsites\Controller\GroupHomepageController::view');
      $route->setDefault('_title_callback', '\Drupal\microsites\Controller\GroupHomepageController::title');
    }
  }

}
