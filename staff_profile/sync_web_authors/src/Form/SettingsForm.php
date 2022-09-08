<?php

namespace Drupal\sync_web_authors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/*
 * Class SettingsForm
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sync_web_authors.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
   public function getFormID() {
     return 'settings_form';
   }

   /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
      $config = $this->config('sync_web_authors.settings');
      $form['sync_url'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('URL of JSON Feed'),
        '#description' => $this->t('URL of JSON feeds for sync. Use %20 instead of spaces. Examples: https://www.extension.iastate.edu/staffdir/feeds/cty-authors/dallas or https://www.extension.iastate.edu/staffdir/feeds/site-authors/4H'),
        '#size' => 64,
        '#default_value' => !empty($config->get('sync_url')) ? preg_replace('/,/',"\r\n",$config->get('sync_url')) : '',
        '#required' => TRUE,
      );

      return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      parent::submitForm($form, $form_state);
      //If checked, run sync
      $this->config('sync_web_authors.settings')
        ->set('sync_url', preg_replace('/\s+(?=[hw])/',',',trim($form_state->getValue('sync_url'))))
        ->save();
  }
}
