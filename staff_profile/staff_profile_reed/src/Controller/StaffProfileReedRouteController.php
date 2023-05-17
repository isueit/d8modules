<?php
namespace Drupal\staff_profile_reed\Controller;

use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

//https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Entity%21Routing%21DefaultHtmlRouteProvider.php/8.2.x
//https://books.google.com/books?id=GUXiCwAAQBAJ&pg=PA268&lpg=PA268&dq=set+defaulthtmlrouteprovider+drupal+8&source=bl&ots=HQ9EwsIRJZ&sig=ACfU3U3ZO0QqEFAGU20gRqO1Imiz46LSkg&hl=en&sa=X&ved=2ahUKEwi-2b7q0bDuAhVFWs0KHRP1AgY4ChDoATAHegQIBxAC#v=onepage&q=set%20defaulthtmlrouteprovider%20drupal%208&f=false
/**
 * @file Contains \Drupal\staff_profile_reed\StaffProfileReedRouteController
 */
class StaffProfileReedRouteController extends DefaultHtmlRouteProvider {
  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    if ($add_cty_editor = $this->getAddCtyEditorRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.add_cty_editor");
    }
    if ($remove_cty_editor = $this->getRemoveCtyEditorRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.remove_cty_editor");
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddCtyEditorRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->bundle() == "staff_profile") {
      $route = \Drupal::service('router.route_provider')->getRouteByName('entity.staff_profile_reed.add_cty_editor_form');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getRemoveCtyEditorRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->bundle() == "staff_profile") {
      $route = \Drupal::service('router.route_provider')->getRouteByName('entity.staff_profile_reed.remove_cty_editor_form');
    }
  }
}
