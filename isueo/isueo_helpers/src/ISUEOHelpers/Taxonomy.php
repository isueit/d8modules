<?php
namespace Drupal\isueo_helpers\ISUEOHelpers;

use Drupal;
use Drupal\taxonomy\Entity\Term;

class Taxonomy
{
  public static function get_terms(string $taxonomy_id)
  {
    // Load Existing terms
    $found = [];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($taxonomy_id);
    foreach ($terms as $term) {
      $found[$term->name] = $term->tid;
    }
    return $found;
  }

  public static function get_term_id(string $term, array &$taxonomy_array, string $taxonomy_id) {
    $result = 0;

    if (array_key_exists($term, $taxonomy_array)) {
      $result = $taxonomy_array[$term];
    } else {
      $new = Term::create(['name' => $term, 'vid' => $taxonomy_id]);
      $new->save();
      $result = $new->id();
      $taxonomy_array[$term] = $result;
    }

    return $result;
  }
}
