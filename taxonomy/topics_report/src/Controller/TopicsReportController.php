<?php

namespace Drupal\topics_report\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the ts_events_details  module.
 */
class TopicsReportController extends ControllerBase
{
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function topics_report()
  {
    // Do NOT cache the events details page
    \Drupal::service('page_cache_kill_switch')->trigger();

    $topics = [];
    $headers = ['ID', 'Name', 'News Articles', 'PLP Programs', 'Totals',];

    // Define your vocabulary machine name
    $vid = 'categories_topics';

    // Load the taxonomy term storage and fetch the tree
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $topics = [];

    foreach ($terms as $term) {
      // $term is a stdClass object containing basic details
      $topics[$term->tid]['name'] = $term->name;
    }

    $node_types = ['news_article', 'plp_program'];
    // 1. Get the node storage handler
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // 2. Query for node IDs matching the specific type
    foreach ($node_types as $node_type) {
      $nids = $node_storage->getQuery()
        ->condition('type', $node_type)
        ->condition('status', 1) // Optional: Only get published nodes
        ->accessCheck(FALSE)     // Required in Drupal 10/11: TRUE to enforce permissions, FALSE to bypass
        ->execute();

      // 3. Load the node objects
      $nodes = $node_storage->loadMultiple($nids);
      $fields = ['field_plp_program_category', 'field_plp_program_topics', 'field_category_topic'];

      // Loop through your nodes
      foreach ($nodes as $node) {

        foreach ($fields as $field) {
          if ($node->hasField($field)) {
            $field_values = $node->get($field)->getValue();

            foreach ($field_values as $value) {
              // Taxonomy fields store references under 'target_id'
              $tid = $value['target_id'];
              if (array_key_exists($tid, $topics)) {
                $topics[$tid][$node_type] = (array_key_exists($node_type, $topics[$tid])) ? $topics[$tid][$node_type] + 1 : 1;
              }
            }
          }
        }
      }
      foreach ($topics as $key => $topic) {
        if (!array_key_exists($node_type, $topic)) {
          $topics[$key][$node_type] = 0;
        }
      }
    }

    foreach ($node_types as $node_type) {
      foreach ($topics as $key => $topic) {
        $topics[$key]['total'] = ($topic['total'] ?? 0) + $topic[$node_type];
      }
    }

    uasort($topics, function ($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    $element = [
      '#theme' => 'topics_report',
      '#topics' => $topics,
      '#headers' => $headers,
    ];
    return $element;
  }
}
