<?php

namespace Drupal\related_store_products\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Related Store Products' Block.
 *
 * @Block(
 *   id = "related_store_products",
 *   admin_label = @Translation("Store Products"),
 *   category = @Translation("Store Products"),
 * )
 */
class RelatedStoreProducts extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $max_to_display = 5;
    $ids = $this->getIds();
    $results = '';
    $count = 0;
    $related_products = [];
    $displayed_products = [];
    $results .= '<ul class="product_related_list">' . PHP_EOL;

    foreach ($ids as $key => $value) {
      $pub_details = json_decode(file_get_contents('https://store.extension.iastate.edu/api/products/' . $value));

      if (!empty($pub_details->Title)) {
        $results .= '<li><a href="https://store.extension.iastate.edu/product/' . $pub_details->ProductID . '">' . $pub_details->Title . '</a></li>' . PHP_EOL;
        $displayed_products[] = $pub_details->ProductID;
        $count++;

        if ($pub_details->RelatedProductIds) {
          foreach ($pub_details->RelatedProductIds as $relatedId) {
            $related_products[] = $relatedId;
          }
        }

        if ($count >= $max_to_display) {
          break;
        }
      }
    }

    foreach ($related_products as $product) {
      if ($count >= $max_to_display) {
        break;
      }
      $pub_details = json_decode(file_get_contents('https://store.extension.iastate.edu/api/products/' . $product));

      if (!empty($pub_details->Title) && !in_array($pub_details->ProductID, $displayed_products)) {
        $results .= '<li><a href="https://store.extension.iastate.edu/product/' . $pub_details->ProductID . '">' . $pub_details->Title . '</a></li>' . PHP_EOL;
        $displayed_products[] = $pub_details->ProductID;
        $count++;
      }
    }

    $results .= '</ul>' . PHP_EOL;


    return [
      '#markup' => $results,
    ];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }

  private function getIds()
  {
    $ids = [];

    // Get the current node
    $node = \Drupal::routeMatch()->getParameter('node');

    // For nodes, include the field_related_store_products values, assumes only one
    if ($node) {
      if (!empty($node->field_related_store_products->value)) {
        $stripped = preg_replace('/\s+/', '', $node->field_related_store_products->value);
        $ids = array_merge($ids, explode(';', $stripped));
      }
    }

    // Should add something here to try to pull related products from MyData when looking at Educational Programs

    // If we don't have products yet, include products from the new/updated list
    if (empty($ids)) {
      $pubs_top = json_decode(file_get_contents('https://store.extension.iastate.edu/api/products/top'));

      foreach ($pubs_top as $pub) {
        $ids[] = $pub->ProductID;
      }
    }

    return array_unique($ids);
  }
}
