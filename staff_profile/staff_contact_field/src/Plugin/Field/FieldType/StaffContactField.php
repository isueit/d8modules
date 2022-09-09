<?php

namespace Drupal\staff_contact_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
#use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
#use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Plugin implementation of the staff_contact_field field type.
 *
 * @FieldType(
 *   id = "staff_contact_field",
 *   label = @Translation("Staff Contact"),
 *   module = "staff_contact_field",
 *   description = @Translation("Stores a list of staff contact for the node."),
 *   category = @Translation("ISUEO"),
 *   default_widget = "staff_contact_field_default",
 * )
 *   default_formatter = "staff_contact_field_default",
 */

class StaffContactField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'contact_header' => array(
        'description' => 'Header for the contact list',
        'type' => 'varchar',
        'length' => '255',
        'not null' => FALSE,
      ),
      'contact_display' => array(
        'description' => 'Display style for the contact list',
        'type' => 'varchar',
        'length' => '255',
        'not null' => FALSE,
      ),
      'contacts' => array(
        'description' => 'Stores the staff contacts',
        'type' => 'varchar',
        'length' => '255',
        'not null' => FALSE,
      ),
    );

    $schema = array(
       'columns' => $columns,
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['contacts_header'] = DataDefinition::create('string')
      ->setLabel(t('Snippet description'));
    $properties['contacts_display'] = DataDefinition::create('string')
      ->setLabel(t('Snippet description'));
    $properties['contacts'] = DataDefinition::create('string')
      ->setLabel(t('Snippet description'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('contacts')->getValue();
    $contacts = unserialize($value);
    return empty($contacts);
  }

}
