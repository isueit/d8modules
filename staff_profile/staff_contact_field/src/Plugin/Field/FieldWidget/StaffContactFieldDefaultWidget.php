<?php

/**
 * @file
 * Contains \Drupal\staff_contact_field\Plugin\Field\FieldWidget\StaffContactFieldDefaultWidget.
 */

namespace Drupal\staff_contact_field\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\migrate\process\Substr;

/**
 * Plugin implementation of the 'staff_contact_field_default' widget.
 *
 * @FieldWidget(
 *   id = "staff_contact_field_default",
 *   label = @Translation("Staff Contact Field default"),
 *   field_types = {
 *     "staff_contact_field"
 *   }
 * )
 */

class StaffContactFieldDefaultWidget extends WidgetBase
{

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
  {
    // Get the Staff Profiles from this site
    $nids = \Drupal::entityQuery('node')->condition('type','staff_profile')->condition('status', 1)->sort('field_staff_profile_last_name')->sort('field_staff_profile_first_name')->execute();
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
    $default_header = isset($items[$delta]->contact_header) ? $items[$delta]->contact_header : 'Staff Contacts';
    $default_display = isset($items[$delta]->contact_display) ? $items[$delta]->contact_display : 'medium';

    $saved_contacts = unserialize($items[$delta]->contacts);
    if (is_bool($saved_contacts)) { $saved_contacts = [];}
    $stafflist = [];

    foreach ($nodes as $node) {
      $stafflist[$node->id()] = [
        $node->getTitle(),
        $node->field_staff_profile_last_name->value,
        $node->field_staff_profile_first_name->value,
        array_key_exists($node->id(), $saved_contacts) ? $saved_contacts[$node->id()] : 0];
    }

    uasort($stafflist, ['Drupal\staff_contact_field\Plugin\Field\FieldWidget\StaffContactFieldDefaultWidget' , 'staff_contact_compare2']);

    $element['embed_container'] = array(
      '#type' => 'details',
      '#title' => $this->t($default_header),
      '#attributes' => array(
        'class' => 'staff_contact_widget',
      ),
      '#open' => FALSE,
      '#description' => 'Select positive numbers to show staff member as a contact for this page, 0 or less means staff member is not a primary contact for the page',
    );

    $element['embed_container']['contact_header'] = array(
      '#title' => $this->t('Section Header'),
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_header,
    );

    $element['embed_container']['contact_display'] = array(
      '#title' => $this->t('Display Type'),
      '#type' => 'radios',
      '#options' => ['long' => 'Contact Info with Picture', 'medium' => 'Name and Contact Info', 'short' => 'Name only'],
      //'#options' => ['short' => 'Name only', 'medium' => 'Name and contact', 'long' => 'Long, includes image'],
      '#default_value' => $default_display,
    );

    foreach ($stafflist as $key => $staffmember) {
      $element['embed_container']['contact_' . $key] = array(
        '#title' => $staffmember[0],
        '#type' => 'weight',
        '#maxlength' => 255,
        '#default_value' => $staffmember[3],
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * This function is necessary because our fields are in a container, and also to handle the ckeditor (text_format) field
   * Adapted from https://drupal.stackexchange.com/questions/246523/values-dont-get-saved-to-the-database-when-fields-are-wrapped-in-a-container
   *
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
  {
    // Step through each instance of the field, in case there is more than 1
    for ($i = 0; $i < count($values); ++$i) {
      $tmparray = [];

      foreach ($values[$i]['embed_container'] as $key => $value) {
        if (substr($key, 0, 8) == 'contact_' && $value > 0) {
          $tmparray[intval(str_replace('contact_', '', $key))] = $value;
        }
      }

      //$values[$i]['contacts'] = serialize([234=>5]);
      $values[$i]['contacts'] = serialize($tmparray);

      // Handle the contact_header (textfield), basically move the value up one level, without the extra container array
      if (isset($values[$i]['embed_container']['contact_header'])) {
        $values[$i]['contact_header'] = $values[$i]['embed_container']['contact_header'];
      }
      if (isset($values[$i]['embed_container']['contact_display'])) {
        $values[$i]['contact_display'] = $values[$i]['embed_container']['contact_display'];
      }
    }
    return $values;
  }

  private function staff_contact_compare2(array $a, array $b)
  {
    $return_value = 0;

    if ($a[3] != $b[3]) {
      $return_value = $b[3] <=> $a[3]; // Highest weight comes first
    } elseif ($a[1] != $b[1]) {
      $return_value = $a[1] <=> $b[1]; // Next by last name
    } else {
      $return_value = $a[2] <=> $b[2]; // Finally by first name
    }

    return $return_value;
  }
}
