<?php

namespace Drupal\staff_profile_primary\Form;

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
      'staff_profile_primary.settings',
    ]; }

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
      $config = $this->config('staff_profile_primary.settings');
      $form['minimum_staff'] = array(
        '#type' => 'number',
        '#title' => $this->t('Minimum Number of Staff'),
        '#description' => $this->t('Minimum number of staff we should expect from the database. If we get less than this, then something\'s wrong, don\'t process the records.'),
        '#maxlength' => 4,
        '#size' => 4,
        '#default_value' => !empty($config->get('minimum_staff')) ? $config->get('minimum_staff') : 800,
        '#required' => TRUE,
      );
      $form['reed_overrides'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Regional Director Overrides'),
        '#description' => $this->t('Format: netid|override<br/>Override can be none, north, south, all, or a list of counties, ie Adair;Adams;Story;Pottawattamie - West<br/>Counties should match what\'s in the Counties in Iowa Vocabulary/Taxonomy'),
        '#rows' => 10,
        '#cols' => 14,
        '#default_value' => !empty($config->get('reed_overrides')) ? $config->get('reed_overrides') : '',
      );

      return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      parent::submitForm($form, $form_state);
      //If checked, run sync
      $this->config('staff_profile_primary.settings')
        ->set('minimum_staff', $form_state->getValue('minimum_staff'))
        ->set('reed_overrides', $form_state->getValue('reed_overrides'))
        ->save();

  }
}
