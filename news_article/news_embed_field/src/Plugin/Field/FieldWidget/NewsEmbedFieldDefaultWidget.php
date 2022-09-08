<?php

/**
 * @file
 * Contains \Drupal\news_embed_field\Plugin\Field\FieldWidget\SnippetsDefaultWidget.
 */

namespace Drupal\news_embed_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'news_embed_field_default' widget.
 *
 * @FieldWidget(
 *   id = "news_embed_field_default",
 *   label = @Translation("News Embed Field default"),
 *   field_types = {
 *     "news_embed_field"
 *   }
 * )
 */

class NewsEmbedFieldDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['embed_container'] = array(
      '#type' => 'details',
      '#title' => $this->t('Embed News Article'),
      '#attributes' => array(
        'class' => 'news_embed_widget',
      ),
      '#open' => FALSE,
    );
    $element['embed_container']['url'] = array(
      '#title' => $this->t('URL of Source News Article'),
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => isset($items[$delta]->url) ? $items[$delta]->url : NULL,
    );
    $element['embed_container']['local_info'] = array(
      '#title' => $this->t('Local Information about this article'),
      '#type' => 'text_format',
      '#default_value' => isset($items[$delta]->local_info) ? $items[$delta]->local_info : NULL,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * This function is necessary because our fields are in a container, and also to handle the ckeditor (text_format) field
   * Adapted from https://drupal.stackexchange.com/questions/246523/values-dont-get-saved-to-the-database-when-fields-are-wrapped-in-a-container
   *
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Step through each instance of the field, in case there is more than 1
    for ($i=0; $i < count($values); ++$i) {
      // Handle the url (textfield), basically move the value up one level, without the extra container array
      if (isset($values[$i]['embed_container']['url'])) {
        $values[$i]['url'] = $values[$i]['embed_container']['url'];
      }
      // Handle the local_info (text_format) field
      // Adapted from https://stackoverflow.com/questions/40914045/drupal-8-custom-field-primitive-error
      if (count($values[$i]['embed_container']['local_info'])) {
        $values[$i]['local_info'] = $values[$i]['embed_container']['local_info']['value'];
      } else {
        $values[$i]['local_info'] = $values[$i]['local_info'] !== '' ? $values[$i]['local_info']  : 'hfkdshfksd';
      }
    }
    return $values;
  }

}
