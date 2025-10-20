<?php

namespace Drupal\newsletter\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the 'module_image_widget' widget.
 *
 * @FieldWidget(
 *   id = "module_image_widget",
 *   label = @Translation("Module image picker"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
class ModuleImageWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thumbnail_width' => 150,
      'thumbnail_height' => 150,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['thumbnail_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Thumbnail width'),
      '#default_value' => $this->getSetting('thumbnail_width'),
      '#min' => 50,
      '#max' => 500,
    ];

    $elements['thumbnail_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Thumbnail height'),
      '#default_value' => $this->getSetting('thumbnail_height'),
      '#min' => 50,
      '#max' => 500,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    
    // Get module path - use base_path for web-accessible URL
    $module_handler = \Drupal::service('extension.list.module');
    $module_path = $module_handler->getPath('newsletter');
    $images_path = $module_path . '/images';
    $base_path = base_path();
    
    $thumbnail_width = $this->getSetting('thumbnail_width');
    $thumbnail_height = $this->getSetting('thumbnail_height');

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);

    // Use radios element type
    $element += [
      '#type' => 'radios',
      '#default_value' => reset($selected) ?: '_none',
      '#options' => [
        '_none' => $this->t('- None -'),
      ],
    ];

    // Process each option and add image preview
    foreach ($options as $option => $label) {
      // Skip "none" option
      if ($option === '_none') {
        $element['#options'][$option] = $label;
        continue;
      }

      // Try to find matching image file
      $extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
      $image_url = NULL;
      
      foreach ($extensions as $ext) {
        $file_path = DRUPAL_ROOT . '/' . $images_path . '/' . $option . '.' . $ext;
        if (file_exists($file_path)) {
          $image_url = $base_path . $images_path . '/' . $option . '.' . $ext;
          break;
        }
      }
      
      if ($image_url) {
        // Create label with image
        $image_markup = '<img src="' . htmlspecialchars($image_url) . '" ' .
          'alt="' . htmlspecialchars($label) . '" ' .
          'class="module-image-thumbnail" ' .
          'width="' . $thumbnail_width . '" ' .
          'height="' . $thumbnail_height . '" />';
        
        $element['#options'][$option] = '<span class="image-option-label">' . 
          $image_markup . 
          '<span class="image-filename">' . htmlspecialchars($label) . '</span>' .
          '</span>';
      } else {
        // No image, just use label
        $element['#options'][$option] = $label;
      }
    }

    $element['#attributes']['class'][] = 'module-image-picker';
    $element['#attached']['library'][] = 'newsletter/module_image_widget';

    return $element;
  }

/**
 * {@inheritdoc}
 */
public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
  foreach ($values as &$value) {
    if (!empty($value['value']) && $value['value'] === '_none') {
      $value['value'] = NULL;
    }
  }
  return $values;
}


  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return FALSE;
  }

}