<?php

namespace Drupal\educational_programs_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
#use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
#use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Plugin implementation of the educational_programs_field field type.
 *
 * @FieldType(
 *   id = "educational_programs_field",
 *   label = @Translation("Educational Programs"),
 *   module = "educational_programs_field",
 *   description = @Translation("Display information from MyData about a given Educational Program."),
 *   category = @Translation("ISUEO"),
 *   default_widget = "educational_programs_field_default",
 *   default_formatter = "educational_programs_field_default",
 * )
 */

class EducationalProgramsField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'term_id' => array(
        'description' => 'The taxonomy term id for the Educational Program',
        'type' => 'varchar',
        'length' => '255',
        'not null' => FALSE,
      ),
      'auto_redirect' => array(
        'description' => 'Whether to redirect automatically to program page',
        'type' => 'int',
        'size' => 'tiny',
        'not null' => true,
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
    $properties['term_id'] = DataDefinition::create('string')
      ->setLabel(t('Snippet description'));
    $properties['auto_redirect'] = DataDefinition::create('boolean')
      ->setLabel(t('Snippet description'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('term_id')->getValue();
    return empty($value);
  }

}
