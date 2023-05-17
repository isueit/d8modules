<?php

namespace Drupal\protect_nodes\Form;

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
      'protect_nodes.settings',
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
      $config = $this->config('protect_nodes.settings');
      $form['protect_nodes'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Prevents users from deleting these nodes, or changing the title and/or URL'),
        '#description' => $this->t('Example (Separate each url with a new line): <br/>&nbsp;&nbsp;/homepage<br/>&nbsp;&nbsp;/events'),
        '#size' => 64,
        '#default_value' => !empty($config->get('protect_nodes')) ? preg_replace('/,/',"\r\n",$config->get('protect_nodes')) : '',
      );

      $form['protect_titles'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Prevents users from changing the title and/or URL'),
        '#description' => $this->t('Example (Separate each url with a new line): <br/>&nbsp;&nbsp;/homepage<br/>&nbsp;&nbsp;/events'),
        '#size' => 64,
        '#default_value' => !empty($config->get('protect_titles')) ? preg_replace('/,/',"\r\n",$config->get('protect_titles')) : '',
      );

      return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      parent::submitForm($form, $form_state);

      $nodes = explode(PHP_EOL, trim($form_state->getValue('protect_nodes')));
      $trimmed_nodes = [];
      foreach($nodes as $nodes) {
        $trimmed_nodes[] = trim($nodes);
      }

      $titles = explode(PHP_EOL, trim($form_state->getValue('protect_titles')));
      $trimmed_titles = [];
      foreach($titles as $titles) {
        $trimmed_titles[] = trim($titles);
      }
      //If checked, run sync
      $this->config('protect_nodes.settings')
        ->set('protect_nodes', implode(',', $trimmed_nodes))
        ->set('protect_titles', implode(',', $trimmed_titles))
        ->save();
  }
}
