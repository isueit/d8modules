<?php

/**
 * @file
 * Contains \Drupal\educational_programs_field\Plugin\Field\FieldWidget\SnippetsDefaultWidget.
 */

namespace Drupal\educational_programs_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'educational_programs_field_default' widget.
 *
 * @FieldWidget(
 *   id = "educational_programs_field_default",
 *   label = @Translation("Educational Programs Field default"),
 *   field_types = {
 *     "educational_programs_field"
 *   }
 * )
 */

class EducationalProgramsFieldDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    //$all_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('category');
  $taxonomyStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $all_terms = $taxonomyStorage->loadByProperties(['vid' => 'educational_programs', 'status' => true]);
  ksort($all_terms);
  $options = array();
  $units = array();
  foreach ($all_terms as $term) {
	// If we would compare to the numeric value of
    // zero (0) PHP would cast both arguments to numbers. In the case of
    // string IDs the ID would always be casted to a 0 causing the
    // condition to always be TRUE.
    if ($term->parent->target_id == '0') {
      $units[$term->id()] = $term->getName();
      $options[$term->getName()] = array();
      //\Drupal::logger('blah')->info($term->id().' - '.$term->getName() . ' - ' . count($term->parent) . ': ' . $term->parent->target_id);
    }
  }
  foreach ($all_terms as $term) {
    if ($term->parent->target_id != '0') {
      $options[$units[$term->parent->target_id]][$term->id()] = $term->getName();
    }
  }
  ksort($options);
  foreach($options as $key => $value) {
    asort($options[$key]);
  }
    $element['term_id'] = array(
      '#type' => 'select',
      //'#target_type' => 'taxonomy_term',
      '#title' => $this->t('Select an Educational Program'),
      '#default_value' => isset($items[$delta]->term_id) ? $items[$delta]->term_id : NULL,
      //'#tags' => TRUE,
      '#options' => $options,
      '#empty_option' => '-- Select a value --',
    );

    $element['auto_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Redirect Public Users to Educational Program Webpage'),
      '#default_value' => isset($items[$delta]->auto_redirect) ? $items[$delta]->auto_redirect : 0,
    ];

    $element['hide_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the image that comes from MyData, used when local info includes an image'),
      '#default_value' => isset($items[$delta]->hide_image) ? $items[$delta]->hide_image : 0,
    ];

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
      if (isset($values[$i]['term_id'])) {
        $values[$i]['term_id'] = $values[$i]['term_id'];
      }
    }
    return $values;
  }

}
