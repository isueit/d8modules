<?php

namespace Drupal\smartsheet_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;

/*
 * Class SettingsForm
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smartsheet_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'smartsheet_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smartsheet_api.settings');
    $last_sync = \Drupal::state()->get('smartsheet_api.last_sync', 0);

    $form['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Status'),
      '#markup' => (empty(Settings::get('smartsheet_api_token')) ? $this->t('<strong>No Smartsheet token found.</strong> Add $settings[\'smartsheet_api_token\'] to settings.php.') : $this->t('Smartsheet token found.'))
        . '<br/>' . $this->t('Last sync: @time', ['@time' => $last_sync ? \Drupal::service('date.formatter')->format($last_sync) : $this->t('Never')]),
    ];
    $form['sync_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Sync Frequency'),
      '#description' => $this->t('How often cron syncs from Smartsheet. Syncs can only run as often as cron does.'),
      '#options' => [
        900 => $this->t('Every 15 minutes'),
        3600 => $this->t('Every hour'),
        21600 => $this->t('Every 6 hours'),
        86400 => $this->t('Every day'),
      ],
      '#default_value' => $config->get('sync_interval'),
    ];

    $form['ptc'] = [
      '#type' => 'details',
      '#title' => $this->t('PTC Class Series'),
      '#open' => TRUE,
    ];
    $form['ptc']['ptc_sheet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smartsheet Sheet ID'),
      '#description' => $this->t('Found in Smartsheet under File > Properties.'),
      '#default_value' => $config->get('ptc_sheet_id'),
      '#required' => TRUE,
    ];
    $form['ptc']['ptc_minimum_rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Number of Rows'),
      '#description' => $this->t('Minimum number of rows we should expect from Smartsheet. If we get less than this, then something\'s wrong, don\'t process the rows.'),
      '#min' => 0,
      '#default_value' => $config->get('ptc_minimum_rows'),
      '#required' => TRUE,
    ];

    $form['sync_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync now'),
      '#description' => $this->t('Run the sync when this form is saved.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('smartsheet_api.settings')
      ->set('sync_interval', (int) $form_state->getValue('sync_interval'))
      ->set('ptc_sheet_id', trim($form_state->getValue('ptc_sheet_id')))
      ->set('ptc_minimum_rows', (int) $form_state->getValue('ptc_minimum_rows'))
      ->save();
    parent::submitForm($form, $form_state);

    //If checked, run sync
    if ($form_state->getValue('sync_now')) {
      $summary = smartsheet_api_ptc_sync();
      if ($summary === NULL) {
        $this->messenger()->addError($this->t('PTC Class Series sync did not run, check the Recent log messages for smartsheet_api.'));
      } else {
        $this->messenger()->addStatus($this->t('PTC Class Series sync finished: @created created, @updated updated, @unchanged unchanged, @unpublished unpublished.', [
          '@created' => $summary['created'],
          '@updated' => $summary['updated'],
          '@unchanged' => $summary['unchanged'],
          '@unpublished' => $summary['unpublished'],
        ]));
      }
    }
  }
}
