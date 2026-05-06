<?php

// This help to display Homepage on the Group page

namespace Drupal\regcytes\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class GroupHomepageRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group.canonical')) {
      $route->setDefault('_controller', '\Drupal\regcytes\Controller\GroupHomepageController::view');
      $route->setDefault('_title_callback', '\Drupal\regcytes\Controller\GroupHomepageController::title');
    }
  }
}
