<?php

namespace Drupal\isueo_helpers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'isueo_helpers.settings',
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
    $config = $this->config('isueo_helpers.settings');
    $form['typesense'] = array(
      '#type' => 'details',
      '#title' => t('Typesense'),
      '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    );
    $form['typesense']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('API Key generated from the Typesense server'),
      '#size' => 50,
      '#maxlength' => 100,
      '#default_value' => $config->get('typesense.api_key'),
    ];

    $form['typesense']['host'] = [
      '#type' => 'textfield',
      '#title' => t('Host name of the Typesense server'),
      '#description' => t('Host name: default typesense.exnet.iastate.edu'),
      '#size' => 50,
      '#maxlength' => 100,
      '#default_value' => empty($config->get('typesense.host')) ? 'typesense.exnet.iastate.edu' : $config->get('typesense.host'),
    ];

    $form['typesense']['port'] = [
      '#type' => 'textfield',
      '#title' => t('Port'),
      '#description' => t('Port for server: default 443'),
      '#size' => 17,
      '#maxlength' => 30,
      '#default_value' => empty($config->get('typesense.port')) ? 443 : $config->get('typesense.port'),
    ];

    $form['typesense']['protocol'] = [
      '#type' => 'textfield',
      '#title' => t('Protocol'),
      '#description' => t('Protocol for server: default https'),
      '#size' => 17,
      '#maxlength' => 30,
      '#default_value' => empty($config->get('typesense.protocol')) ? 'https' : $config->get('typesense.protocol'),
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

    $this->config('isueo_helpers.settings')
      ->set('typesense.api_key', $form_state->getValue('api_key'))
      ->set('typesense.host', $form_state->getValue('host'))
      ->set('typesense.port', $form_state->getValue('port'))
      ->set('typesense.protocol', $form_state->getValue('protocol'))
      ->save();
  }
}
