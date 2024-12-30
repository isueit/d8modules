<?php

/**
 * @file
 * Contains Drupal\drupal8_field_formatters\Plugin\Field\FieldFormatter\SmugmugIdFormatter.
 */

namespace Drupal\staff_profile\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Plugin implementation of the 'smugmug_id_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "smugmug_id_formatter",
 *   label = @Translation("Smugmug Image"),
 *   weight = "10",
 *   field_types = {
 *     "string",
 *     "text",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class SmugmugIdFormatter extends FormatterBase
{
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $element = [];
    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $htmloutput = '';

      if (!empty($item->value)) {
        $htmloutput .= PHP_EOL . '<img class="staff_profile_smugmug" src="' . ISUEOHelpers\General::build_smugmug_url($item->value, 'XL') . '" alt="Staff photo" />' ;
      }

      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $htmloutput,
      ];
    }

    // Check that we're returning an image, if not, return our blank image
    if (empty($element[0]['#markup'])) {
      $element[0]['#markup'] = '<img class="staff_profile_smugmug staff_profile_blank_image" src="' .  \Drupal::request()->getBaseUrl() . '/modules/custom/d8modules/staff_profile/staff_profile/images/blank_image.png" alt="" />';
      $element[0]['#type'] = 'markup';
    }

    return $element;
  }
}
