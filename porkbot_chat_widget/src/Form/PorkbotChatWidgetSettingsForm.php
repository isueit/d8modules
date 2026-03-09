<?php

namespace Drupal\porkbot_chat_widget\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Porkbot Chat Widget.
 */
class PorkbotChatWidgetSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['porkbot_chat_widget.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'porkbot_chat_widget_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('porkbot_chat_widget.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable widget'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['load_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load on admin pages'),
      '#default_value' => $config->get('load_admin'),
      '#description' => $this->t('Enable this only if the chat widget is needed in the admin UI.'),
    ];

    $form['script_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Widget script URL'),
      '#default_value' => $config->get('script_url'),
      '#description' => $this->t('URL to the hosted widget script bundle.'),
      '#maxlength' => 2048,
    ];

    $form['api_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API base URL'),
      '#default_value' => $config->get('api_base_url'),
      '#description' => $this->t('Azure Function host URL used by the Astro interface. Example: https://porkbot-extension-f0edf7egf8d5c3e2.canadacentral-01.azurewebsites.net'),
      '#maxlength' => 2048,
    ];

    $form['api_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API path prefix'),
      '#default_value' => $config->get('api_prefix') ?: '/api',
      '#description' => $this->t('Path prefix prepended to API routes. Example: /api'),
      '#maxlength' => 255,
    ];

    $form['default_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Default interface mode'),
      '#default_value' => $config->get('default_mode') ?: 'widget',
      '#options' => [
        'widget' => $this->t('Widget'),
        'full' => $this->t('Full page'),
      ],
    ];

    $form['open_by_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open widget by default'),
      '#default_value' => $config->get('open_by_default'),
      '#description' => $this->t('When enabled and default mode is Widget, the chat panel opens automatically.'),
    ];

    $form['init_method'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Init method path'),
      '#default_value' => $config->get('init_method'),
      '#description' => $this->t('Dot path to the init function on window. Example: PorkbotChatWidget.init'),
      '#maxlength' => 255,
    ];

    $form['init_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Init options (JSON object)'),
      '#default_value' => $config->get('init_options'),
      '#description' => $this->t('Optional JSON object passed to the init method.'),
      '#rows' => 8,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $enabled = (bool) $form_state->getValue('enabled');
    $script_url = trim((string) $form_state->getValue('script_url'));

    if ($enabled && $script_url === '') {
      $form_state->setErrorByName('script_url', $this->t('Provide a script URL when the widget is enabled.'));
    }

    if ($script_url !== '' && !filter_var($script_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('script_url', $this->t('Enter a valid URL.'));
    }

    $api_base_url = trim((string) $form_state->getValue('api_base_url'));
    if ($api_base_url !== '' && !filter_var($api_base_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('api_base_url', $this->t('Enter a valid API base URL.'));
    }

    $api_prefix = trim((string) $form_state->getValue('api_prefix'));
    if ($api_prefix !== '' && strpos($api_prefix, '/') !== 0) {
      $form_state->setErrorByName('api_prefix', $this->t('API path prefix must start with "/".'));
    }

    $default_mode = (string) $form_state->getValue('default_mode');
    if (!in_array($default_mode, ['widget', 'full'], TRUE)) {
      $form_state->setErrorByName('default_mode', $this->t('Choose a valid default interface mode.'));
    }

    $init_options = trim((string) $form_state->getValue('init_options'));
    if ($init_options !== '') {
      $decoded = json_decode($init_options, TRUE);
      if (!is_array($decoded)) {
        $form_state->setErrorByName('init_options', $this->t('Init options must be a valid JSON object.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory
      ->getEditable('porkbot_chat_widget.settings')
      ->set('enabled', (bool) $form_state->getValue('enabled'))
      ->set('load_admin', (bool) $form_state->getValue('load_admin'))
      ->set('script_url', trim((string) $form_state->getValue('script_url')))
      ->set('api_base_url', trim((string) $form_state->getValue('api_base_url')))
      ->set('api_prefix', $this->normalizeApiPrefix($form_state->getValue('api_prefix')))
      ->set('default_mode', (string) $form_state->getValue('default_mode'))
      ->set('open_by_default', (bool) $form_state->getValue('open_by_default'))
      ->set('init_method', trim((string) $form_state->getValue('init_method')))
      ->set('init_options', trim((string) $form_state->getValue('init_options')))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Normalize API prefix to a leading-slash path.
   */
  private function normalizeApiPrefix($value) {
    $prefix = trim((string) $value);
    if ($prefix === '') {
      return '/api';
    }

    if (strpos($prefix, '/') !== 0) {
      $prefix = '/' . $prefix;
    }

    $normalized = rtrim($prefix, '/');
    return $normalized === '' ? '/' : $normalized;
  }

}
