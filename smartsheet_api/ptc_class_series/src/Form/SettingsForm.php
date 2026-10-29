<?php

namespace Drupal\ptc_class_series\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/*
 * Class SettingsForm
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ptc_class_series.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ptc_class_series_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ptc_class_series.settings');

    $form['workshop_descriptions'] = [
      '#type' => 'details',
      '#title' => $this->t('Workshop Descriptions'),
      '#description' => $this->t('Shown on every class page for that workshop. Changes show right away, without a sync.'),
      '#open' => TRUE,
    ];
    foreach (ptc_class_series_workshops() as $workshop => $label) {
      $form['workshop_descriptions']['workshop_' . $workshop] = [
        '#type' => 'textarea',
        '#title' => $label,
        '#rows' => 4,
        '#default_value' => $config->get('workshop_descriptions.' . $workshop),
      ];
    }

    $form['smartsheet'] = [
      '#type' => 'details',
      '#title' => $this->t('Smartsheet'),
      '#description' => $this->t('The token, sync frequency, and Sync now are on the <a href=":url">Smartsheet API settings</a> page.', [':url' => Url::fromRoute('smartsheet_api.settings_form')->toString()]),
      '#open' => TRUE,
    ];
    $form['smartsheet']['sheet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smartsheet Sheet ID'),
      '#description' => $this->t('Found in Smartsheet under File > Properties.'),
      '#default_value' => $config->get('sheet_id'),
      '#required' => TRUE,
    ];
    $form['smartsheet']['minimum_rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Number of Rows'),
      '#description' => $this->t('Minimum number of rows we should expect from Smartsheet. If we get less than this, then something\'s wrong, don\'t process the rows.'),
      '#min' => 0,
      '#default_value' => $config->get('minimum_rows'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ptc_class_series.settings')
      ->set('sheet_id', trim($form_state->getValue('sheet_id')))
      ->set('minimum_rows', (int) $form_state->getValue('minimum_rows'));
    foreach (array_keys(ptc_class_series_workshops()) as $workshop) {
      $config->set('workshop_descriptions.' . $workshop, trim($form_state->getValue('workshop_' . $workshop)));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
