<?php

namespace Drupal\ts_extension_content\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\isueo_helpers\ISUEOHelpers\Typesense;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ts_extension_content.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ts_extension_content.settings');

    $form['typesense']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('API Key generated from the Typesense server'),
      '#size' => 50,
      '#maxlength' => 100,
      '#default_value' => $config->get('api_key'),
      '#required' => true,
    ];

    $form['collection'] = [
      '#type' => 'textfield',
      '#title' => t('Collection Name'),
      '#description' => t('Typesense Collection to write to'),
      '#size' => 50,
      '#maxlength' => 100,
      '#default_value' => empty($config->get('collection')) ? 'extension_content' : $config->get('collection'),
      '#required' => true,
    ];

    $urlParts = explode('/' , $_SERVER['REQUEST_URI']);
    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => t('Site Name'),
      '#description' => t('Name of this Drupal site, should be the same as used in drush configuration'),
      '#size' => 50,
      '#maxlength' => 100,
      '#default_value' => empty($config->get('site_name')) ? $urlParts[1] : $config->get('site_name'),
      '#required' => true,
    ];

    $form['home_url'] = [
      '#type' => 'textfield',
      '#title' => t('Home URL'),
      '#description' => t('URL of the home directory'),
      '#size' => 50,
      '#maxlength' => 100,
      '#default_value' => empty($config->get('home_url')) ? 'https://www.extension.iastate.edu/' . $urlParts[1] : $config->get('home_url'),
      '#required' => true,
    ];

    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    $contentTypes = [];
    foreach ($types as $type) {
      $contentTypes[$type->id()] = $type->id();
    }

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Content Types'),
      '#description' => t('Content Types you would like to include in the index'),
      '#options' => $contentTypes,
      '#default_value' => empty($config->get('content_types')) ? [] : array_values($config->get('content_types')),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);

    $contentTypes = [];
    foreach ($form_state->getValue('content_types') as $type) {
      if (!empty($type)) {
        $contentTypes[$type] = $type;
      }
    }

    $this->config('ts_extension_content.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('collection', $form_state->getValue('collection'))
      ->set('site_name', $form_state->getValue('site_name'))
      ->set('home_url', rtrim(trim($form_state->getValue('home_url')), '/'))
      ->set('content_types', $contentTypes)
      ->save();

      ts_extension_content_index_all_nodes();

  }
}

