<?php

namespace Drupal\educational_programs_page\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Implementing our example JSON api.
 */
class JsonApiController {

  /**
   * Callback for the API.
   */
  public function renderApi() {

    return new JsonResponse([
      'data' => $this->getResults(),
      'method' => 'GET',
    ]);
  }

  /**
   * A helper function returning results.
   */
  public function getResults() {
    $educationalPrograms = [];

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'educational_programs_page', 'status' => 1]);

    foreach($nodes as $node) {
      $myNode = $node->toArray();
      $taxonomyTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($myNode['field_educational_program'][0]['term_id']);

      // Get the sessions from Layout Builder
      $layout = $node->get('layout_builder__layout');
      $sections = $layout->getSections();

      $educationalPrograms[] = [

        'nodeID' => $node->id(),
        'nodeURL' => substr(\Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$node->id()), 1),
        'nodeSections' => count($sections),
        //'title' => $node->getTitle(),
        'termID' => $myNode['field_educational_program'][0]['term_id'],
        'termRedirected' => $myNode['field_educational_program'][0]['auto_redirect'],
        'termName' => $taxonomyTerm->label(),
        'termDescription' => $taxonomyTerm->getDescription(),
        //'title' => $node->get('field_educational_program_field'),
      ];
    }
    return $educationalPrograms;
  }
}
