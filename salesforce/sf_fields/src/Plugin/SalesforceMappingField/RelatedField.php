<?php

namespace Drupal\sf_fields\Plugin\SalesforceMappingField;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\salesforce\SObject;
use Drupal\salesforce_mapping\Entity\SalesforceMappingInterface;
use Drupal\salesforce_mapping\SalesforceMappingFieldPluginBase;

/**
 * Adapter for Salesforce related object fields using dot notation.
 *
 * @Plugin(
 *   id = "RelatedField",
 *   label = @Translation("Related Field (dot notation)")
 * )
 */
class RelatedField extends SalesforceMappingFieldPluginBase {

  /**
   * {@inheritdoc}
   *
   * Adds a free-text input for the dot-notation SF path, e.g. "Account.Name".
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
      $pluginForm = parent::buildConfigurationForm($form, $form_state);

      // Build a list of available Drupal fields on this entity/bundle.
      $options = [];
      $entity = $form['#entity']; // The SalesforceMapping entity.
      $entity_type = $entity->get('drupal_entity_type');
      $bundle      = $entity->get('drupal_bundle');

      $field_definitions = \Drupal::service('entity_field.manager')
        ->getFieldDefinitions($entity_type, $bundle);

      foreach ($field_definitions as $field_name => $field_definition) {
        $options[$field_name] = $field_definition->getLabel() . ' (' . $field_name . ')';
      }
      ksort($options);

      // Add the Drupal field selector.
      $pluginForm['drupal_field_value'] += [
        '#type'          => 'select',
        '#title'         => $this->t('Drupal field'),
        '#options'       => $options,
        '#empty_option'  => $this->t('- Select -'),
        '#default_value' => $this->config('drupal_field_value'),
        '#required'      => TRUE,
      ];

      // Replace the SF field dropdown with a free-text input.
      $pluginForm['salesforce_field'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Salesforce related field'),
        '#description'   => $this->t('Dot-notation path, e.g. <code>Account.Name</code> or <code>Owner.Email</code>.'),
        '#default_value' => $this->config('salesforce_field'),
        '#required'      => TRUE,
      ];

      // Lock direction to pull-only.
      $pluginForm['direction']['#default_value'] = 'sf_drupal';
      $pluginForm['direction']['#disabled'] = TRUE;

      return $pluginForm;
  }

  /**
   * {@inheritdoc}
   *
   * Traverses the dot-notation path on the SObject to get the value.
   * e.g. "Account.Name" => $sf_object->field('Account')['Name']
   */
  public function pullValue(SObject $sf_object, EntityInterface $entity, SalesforceMappingInterface $mapping) {
    $path = $this->config('salesforce_field');

    if (strpos($path, '.') === FALSE) {
      // No dot — treat as a normal flat field.
      return $sf_object->field($path);
    }

    [$relationship, $field] = explode('.', $path, 2);

    try {
      $related = $sf_object->field($relationship);
    }
    catch (\Exception $e) {
      return NULL;
    }

    if (empty($related) || !is_array($related)) {
      return NULL;
    }

    return $related[$field] ?? NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Not used for push — this plugin is pull-only.
   */
  public function value(EntityInterface $entity, SalesforceMappingInterface $mapping) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function push() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function pull() {
    return TRUE;
  }

}
