<?php

namespace Drupal\smugmug_media_type\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Plugin implementation of the smugmug_media_type field type.
 *
 * @FieldType(
 *   id = "smugmug_media_type",
 *   label = @Translation("Smugmug Embed"),
 *   description = @Translation("Stores a smugmug image and then outputs some embed code."),
 *   category = @Translation("Media"),
 *   default_widget = "smugmug_media_type_textfield",
 *   default_formatter = "smugmug_media_type_image",
 *   constraints = {"SmugmugEmbedValidation" = {}}
 * )
 */
class ImageEmbedField extends FieldItemBase {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\smugmug_media_type\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL, $provider_manager = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $provider_manager = \Drupal::service('smugmug_media_type.provider_manager');
    return new static($definition, $name, $parent, $provider_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 256,
        ],
        'alt' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Image url'))
      ->setRequired(TRUE);
    $properties['alt'] = DataDefinition::create('string')
      ->setLabel(t('Alternative Text'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['allowed_providers'] = [
      '#title' => $this->t('Allowed Providers'),
      '#description' => $this->t('Restrict users from entering information from the following providers. If none are selected any image provider can be used.'),
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('allowed_providers'),
      '#options' => $this->providerManager->getProvidersOptionList(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'allowed_providers' => [],
    ];
  }

}
