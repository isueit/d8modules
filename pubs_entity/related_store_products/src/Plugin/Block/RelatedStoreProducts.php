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
    $results .= '<ul class="related_products_list">' . PHP_EOL;

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

  public function blockForm($form, FormStateInterface $form_state)
  {
    $config = $this->getConfiguration();
    $max_pubs = isset($config['default_max_pubs']) ? $config['default_max_pubs'] : 5;

    $form['default_max_pubs'] = array(
      '#type' => 'textfield',
      '#title' => t('Default Maximum Number of Products to Display'),
      '#description' => t('Zero (0) means display all products. Used whe a specific number for a content type isn\'t found') . '<br/><br/>',
      '#size' => 15,
      '#default_value' => $max_pubs,
    );

    $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($types as $type) {
      $form['max_pubs_' . $type->id()] = array(
        '#type' => 'textfield',
        '#title' => t('Maximum Number of products for ' . $type->label() . ' content type'),
        '#description' => t('Zero (0) means display all products'),
        '#size' => 15,
        '#default_value' => isset($config['max_pubs_' . $type->id()]) ? $config['max_pubs_' . $type->id()] : $max_pubs,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();

    $this->configuration['default_max_pubs'] = $values['default_max_pubs'];

    $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($types as $type) {
      $this->configuration['max_pubs_' . $type->id()] = isset($values['max_pubs_' . $type->id()]) ? $values['max_pubs_' . $type->id()] : $values['default_max_pubs'];
    }
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

    // If we don't have a product list yet, try getting products from Pubs Entity Type
    if (empty($ids)) {
      $entityIds = \Drupal::entityQuery('pubs_entity')->sort('weight', 'DESC')->execute();
      $entities = \Drupal::entityTypeManager()->getStorage('pubs_entity')->loadMultiple($entityIds);
      foreach ($entities as $entity) {
        $ids[] = $entity->field_product_id->value;
      }
    }

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
