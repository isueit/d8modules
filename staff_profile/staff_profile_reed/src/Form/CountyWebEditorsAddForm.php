<?php
namespace Drupal\staff_profile_reed\Form;

use \Drupal\Core\Form\FormBase;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\taxonomy\Entity\Term;

class CountyWebEditorsAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'staff_profile_reed_add_cty_editor';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['netid'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'staff_profile_reed.autocomplete',
      '#autocomplete_route_parameters' => [
        'field_name' => 'field_staff_profile_netid',
        'count' => 5
      ]
    ];
    $form['cty'] = [
      '#type' => 'hidden',
      '#default_value' => "NaN"
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add County Editor')
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $message = parent::validateForm($form, $form_state);
    $netid = str_replace('@iastate.edu', '', strtolower($form_state->getValue('netid')));
    $form_state->setValue('netid', $netid);

    $staff_profiles = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_staff_profile_netid' => $netid]);
    if (count($staff_profiles) == 0) {
      $form_state->setErrorByName('', $this->t('Staff Profile Not found with given netid'));
      \Drupal::messenger()->addStatus(t('Staff Profile Not found with given netid'), 'error');
    } else {
      $staff_profile = reset($staff_profiles);
      if (in_array($form_state->getValue('cty'), array_column($staff_profile->field_staff_profile_cty_author->getValue(), 'target_id'))) {
        $form_state->setErrorByName('', $this->t('Staff Profile with given netid already authorized in county'));
        \Drupal::messenger()->addStatus(t('Staff Profile with given netid already authorized in county'), 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $netid = $form_state->getValue('netid');
    $tid = $form_state->getValue('cty');
    $staff_profiles = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_staff_profile_netid' => $netid]);;
    if ($staff_profile = reset($staff_profiles)) {
      $form_state->setRedirect('entity.staff_profile_reed.add_cty_editor_form', array(
        'node' => $staff_profile->id(),
        'cty' => $tid
      ));
    }
  }
}
