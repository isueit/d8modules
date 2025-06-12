<?php

namespace Drupal\staff_profile_reed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;

class AutocompleteController extends ControllerBase {
  public function handleAutocomplete(Request $request) {
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $matches = array();
      $database_replica = \Drupal::service('database.replica');
      $query = \Drupal::entityQuery('node')->accessCheck(false)->condition('type', 'staff_profile')->condition('status', 1)->condition('field_staff_profile_netid', '%'.$database_replica->escapeLike($string).'%', 'LIKE');
      $nids = $query->execute();
      $result = entity_load_multiple('node', $nids);
      foreach ($result as $row) {
        $matches[] = ['value' => $row->field_staff_profile_netid->value, 'label' => $row->title->value . ' [' . $row->field_staff_profile_email->value . ']'];
      }
    }
    return new JsonResponse($matches);
  }
}
