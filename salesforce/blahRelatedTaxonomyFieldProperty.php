<?php

namespace Drupal\sf_fields\Plugin\SalesforceMappingField;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\salesforce\SObject;
use Drupal\salesforce_mapping\Entity\SalesforceMappingInterface;
use Drupal\salesforce_mapping\SalesforceMappingFieldPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Adapter for Salesforce fields that should map to a Drupal taxonomy term
 * reference field, including multi-value fields.
 *
 * Pulls a (possibly dot-notation) value off the Salesforce object, splits
 * it on a configurable delimiter, then looks up a taxonomy term matching
 * each resulting name in the configured vocabulary. Optionally creates
 * terms that don't exist yet. Returns an array of tids, which Drupal's
 * entity_reference field system accepts for both single- and multi-value
 * fields (single-cardinality fields simply use the first item).
 *
 * @Plugin(
 *   id = "related_taxonomy_field_property",
 *   label = @Translation("Related Taxonomy Field (dot notation)")
 * )
 */
class RelatedTaxonomyFieldProperty extends SalesforceMappingFieldPluginBase
{

  /**
   * {@inheritdoc}
   *
   * Adds the free-text SF path plus vocabulary/create-term settings.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $pluginForm = parent::buildConfigurationForm($form, $form_state);

    // Build a list of available Drupal fields on this entity/bundle.
    $options = [];
    $entity = $form['#entity']; // The SalesforceMapping entity.
    $entity_type = $entity->get('drupal_entity_type');
    $bundle      = $entity->get('drupal_bundle');

    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($entity_type, $bundle);

    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() === 'entity_reference' && $field_definition->getSetting('target_type') === 'taxonomy_term') {
        $options[$field_name] = $field_definition->getLabel() . ' (' . $field_name . ')' . $field_definition->getType();
        echo '**' . $field_name . '**';
      }
    }
    ksort($options);

    // Which vocabulary to look the term up in / create it in.
    $pluginForm['vocabulary'] = [
      '#title'         => $this->t('****Target vocabulary'),
      '#type'          => 'select',
      //'#description'   => $this->t('Machine name of the taxonomy vocabulary to search/create terms in, e.g. <code>industry</code>.'),
      '#options'       => $options,
      '#empty_option'  => $this->t('- Select** -'),
      '#default_value' => $this->config('vocabulary'),
      '#required'      => TRUE,
    ];

    // Same free-text dot-notation input as the text-field version.
    $pluginForm['salesforce_field'] = [
      '#title'         => $this->t('Salesforce related field'),
      '#type'          => 'textfield',
      '#description'   => $this->t(
        'Enter a dot-notation path to a related Salesforce field, e.g. <code>Account.Industry</code> or <code>Opportunity.Type</code>. The value returned will be matched against taxonomy term names.'
      ),
      '#default_value' => $this->config('salesforce_field'),
      '#required'      => TRUE,
    ];

    // Whether to auto-create a term when no match is found.
    $pluginForm['create_term_if_missing'] = [
      '#title'         => $this->t('Create term if it does not exist'),
      '#type'          => 'checkbox',
      '#description'   => $this->t('If unchecked, unmatched values are skipped instead of creating a new term.'),
      '#default_value' => $this->config('create_term_if_missing') ?? TRUE,
    ];

    // Delimiter used to split a multi-value SF string into separate terms.
    $pluginForm['delimiter'] = [
      '#title'         => $this->t('Multi-value delimiter'),
      '#type'          => 'textfield',
      '#size'          => 5,
      '#description'   => $this->t(
        'Character(s) used to split the Salesforce value into multiple terms. Use <code>;</code> for Salesforce multi-select picklists, which return values joined by semicolons by default. Leave the default in place if the field is always single-value.'
      ),
      '#default_value' => $this->config('delimiter') ?? ';',
      '#required'      => TRUE,
    ];

    // This plugin is pull-only, same as the text version.
    $pluginForm['direction']['#default_value'] = 'sf_drupal';
    $pluginForm['direction']['#disabled'] = TRUE;

    return $pluginForm;
  }

  /**
   * {@inheritdoc}
   *
   * Traverses the dot-notation path on the SObject, splits the resulting
   * string on the configured delimiter, and resolves (or creates) a
   * taxonomy term for each piece. Returns an array of tids so it works
   * for both single- and multi-value taxonomy reference fields.
   */
  public function pullValue(SObject $sf_object, EntityInterface $entity, SalesforceMappingInterface $mapping)
  {
    $path = $this->config('salesforce_field');
    $value = $this->extractValue($sf_object, $path);

    if ($value === NULL || $value === '') {
      return NULL;
    }

    $delimiter = $this->config('delimiter') ?: ';';

    $names = array_map('trim', explode($delimiter, (string) $value));
    $names = array_filter($names, function ($name) {
      return $name !== '';
    });

    if (empty($names)) {
      return NULL;
    }

    $tids = [];
    foreach ($names as $name) {
      $tid = $this->getOrCreateTermId($name);
      if ($tid !== NULL) {
        $tids[] = $tid;
      }
    }

    return empty($tids) ? NULL : $tids;
  }

  /**
   * Resolves a dot-notation (or flat) path against the Salesforce object.
   */
  protected function extractValue(SObject $sf_object, string $path)
  {
    if (strpos($path, '.') === FALSE) {
      try {
        return $sf_object->field($path);
      } catch (\Exception $e) {
        return NULL;
      }
    }

    [$relationship, $field] = explode('.', $path, 2);

    try {
      $related = $sf_object->field($relationship);
    } catch (\Exception $e) {
      return NULL;
    }

    if (empty($related) || !is_array($related)) {
      return NULL;
    }

    return $related[$field] ?? NULL;
  }

  /**
   * Finds a taxonomy term by name in the configured vocabulary, creating
   * one if allowed and none is found.
   */
  protected function getOrCreateTermId(string $name): ?int
  {
    $vocabulary = $this->config('vocabulary');

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'name' => $name,
        'vid' => $vocabulary,
      ]);

    if (!empty($terms)) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = reset($terms);
      return (int) $term->id();
    }

    if (!$this->config('create_term_if_missing')) {
      return NULL;
    }

    $term = Term::create([
      'name' => $name,
      'vid' => $vocabulary,
    ]);
    $term->save();

    return (int) $term->id();
  }

  /**
   * {@inheritdoc}
   *
   * Not used for push — this plugin is pull-only.
   */
  public function value(EntityInterface $entity, SalesforceMappingInterface $mapping)
  {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function push()
  {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function pull()
  {
    return TRUE;
  }
}
