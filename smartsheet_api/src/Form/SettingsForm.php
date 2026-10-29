<?php

namespace Drupal\smartsheet_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;

/*
 * Class SettingsForm
 *
 * Submodules add their own settings to this form with hook_form_smartsheet_api_settings_form_alter()
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
    $form['sync_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync now'),
      '#description' => $this->t('Run every enabled sync when this form is saved.'),
      '#weight' => 90,
    ];
    // Runs after the form alters, so syncNow comes after any submodule's submit handler
    $form['#after_build'][] = [static::class, 'addSyncNowSubmit'];

    return parent::buildForm($form, $form_state);
  }

  /**
   * #after_build callback, adds syncNow as the last submit handler
   */
  public static function addSyncNowSubmit(array $form, FormStateInterface $form_state) {
    $form['#submit'][] = [static::class, 'syncNow'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('smartsheet_api.settings')
      ->set('sync_interval', (int) $form_state->getValue('sync_interval'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Run the syncs after every submit handler, including submodules', has saved its settings
   */
  public static function syncNow(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('sync_now')) {
      return;
    }

    $results = smartsheet_api_sync();
    if (empty($results)) {
      \Drupal::messenger()->addWarning(t('No Smartsheet syncs are enabled. Enable a submodule, like PTC Class Series.'));
    }
    foreach ($results as $label => $summary) {
      if ($summary === NULL) {
        \Drupal::messenger()->addError(t('@label sync did not run, check the Recent log messages.', ['@label' => $label]));
      } else {
        \Drupal::messenger()->addStatus(t('@label sync finished: @summary.', ['@label' => $label, '@summary' => smartsheet_api_summary_text($summary)]));
      }
    }
  }
}
