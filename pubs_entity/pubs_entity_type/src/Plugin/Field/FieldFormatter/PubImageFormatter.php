<?php
namespace Drupal\pubs_entity_type\Plugin\Field\FieldFormatter;

use \Drupal\Core\Field\FieldItemListInterface;
use \Drupal\Core\Field\FieldItemInterface;
use \Drupal\Core\Field\FormatterBase;


/**
 * Plugin field formatter for pubs_entity_type
 *
 * @FieldFormatter(
 *   id = "remote_pubs_image",
 *   label = @Translation("Pubs Entity Image"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class PubImageFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
   public function viewElements(FieldItemListInterface $items, $langcode) {
     $element = array();
     foreach ($items as $delta => $item) {
       $element[$delta] = [
         '#type' => 'markup',
         '#markup' => $this->getEmbedCode($item),
         '#allowed_tags' => ['img', 'a', 'div'],
       ];
     }
     return $element;
   }

   /**
    * {@inheritdoc}
    */
   protected function getEmbedCode($value) {
     $url = '';
     $alt = '';
     if (is_string($value)) {
       $url = $value;
     } elseif ($value instanceof FieldItemInterface) {
       $class = get_class($value);
       $property = $class::mainPropertyName();
       if ($property) {
         $url = $value->$property;
         $alt = $value->getEntity()->name->value;
       }
     }
     return '<img src="' . $url . '" alt="' . $alt . '">';
   }
}
