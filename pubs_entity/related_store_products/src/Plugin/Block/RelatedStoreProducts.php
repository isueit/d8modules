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
 *   admin_label = @Translation("Related Store Products"),
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
    $ids = $this->getIds();
    $first = true;
    $results = '';

    foreach ($ids as $key => $value) {
      $pub_details = json_decode(file_get_contents('https://store.extension.iastate.edu/api/products/' . $value));

      if (!empty($pub_details->Title)) {
        if ($first) {
          $results .= '<div class="products_header">Related Store Products</div>' . PHP_EOL;
          $first = false;
        } else {
          $results .= '<hr class="product_separator" />' . PHP_EOL;
        }

        $results .= '<a href="https://store.extension.iastate.edu/product/' . $pub_details->ProductID . '">' . PHP_EOL;
        //$results .= '  <img alt="Thumbnail of ' . $pub_details->Title . '" src="' . $pub_details->ThumbnailURI . '" />' . PHP_EOL;
        $results .= '  <img src="' . $pub_details->ThumbnailURI . '" alt="" />' . PHP_EOL;
        $results .= '  <div class="product_title">' . $pub_details->Title . '</div>' . PHP_EOL;
        $results .= '</a>' . PHP_EOL;

        $results .= '<ul class="product_related_list">' . PHP_EOL;
        $count = 0;
        foreach ($pub_details->RelatedProductIds as $relatedId) {
          $related_details = json_decode(file_get_contents('https://store.extension.iastate.edu/api/products/' . $relatedId));
          if (!empty($related_details->Title)) {
            $results .= '<li><a href="https://store.extension.iastate.edu/product/' . $related_details->ProductID . '">' . PHP_EOL;
            //$results .= '  <img alt="Thumbnail of ' . $related_details->Title . '" src="' . $related_details->ThumbnailURI . '" />' . PHP_EOL;
            $results .= '  ' . $related_details->Title . PHP_EOL;
            $results .= '</a></li>' . PHP_EOL;

            if (++$count >= 5) {
              break;
            }
          }
        }
        $results .= '</ul>' . PHP_EOL;
      }
    }

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

    if ($node) {
      if (!empty($node->field_related_store_products->value)) {
        $stripped = preg_replace('/\s+/', '', $node->field_related_store_products->value);
        $ids = array_merge($ids, explode(';', $stripped));
      }

    }

    return array_unique($ids);
  }
}
