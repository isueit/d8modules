<?php

namespace Drupal\staff_profile_reed\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Component\Datetime\Time;

class CountyWebEditorsConfirmAdd extends ContentEntityConfirmFormBase {

  private  $county;

  //TODO: Look for a way to add this as a controller for staff_profile to automatically pick up entity
  function __construct(EntityRepository $repo, EntityTypeBundleInfo $bundle_info, Time $time) {
    parent::__construct($repo, $bundle_info, $time);
    $this->setEntityTypeManager(\Drupal::entityTypeManager());
    $this->setModuleHandler(\Drupal::moduleHandler());
    $this->setEntity(\Drupal::routeMatch()->getParameter('node'));


    $tid = \Drupal::routeMatch()->getParameter('cty');

    $this->county = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
  }



  public function getQuestion() {
    return $this->t('Are you sure you want to add %name to %cty county?', array('%name' => $this->entity->label(), '%cty' => $this->county->label()));
  }

  public function getCancelUrl() {
    return new Url('staff_profile_reed.county_web_editors');
  }

  public function getConfirmText() {
    return $this->t('Add to County');
  }


  public function getDescription() {
    //return $this->t('Adds staff_profile from users authorized in county Web Editors.');
    return '';
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $web_editor_qual = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Web Editor', 'vid' => 'editor_qualifications']);
    $web_editor = reset($web_editor_qual);
    $web_editor_qual_id = $web_editor->id();

    $this->entity->field_staff_profile_cty_author[] = ['target_id' => $this->county->id()];
    $this->entity->save();

    if (!in_array($web_editor_qual_id, array_column($this->entity->field_staff_profile_quals->getValue(), 'target_id'))) {
      $needs_training = true;
    } else {
      $needs_training = false;
    }
    //Send notification emails
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'staff_profile_reed';
    $params['name'] = $this->entity->label();
    $params['netid'] = $this->entity->field_staff_profile_email->value;
    $params['county'] = $this->county->label();
    $params['reg_director'] = \Drupal::currentUser()->getAccountName();
    $params['needstraining'] = $needs_training;
    $send = true; //TODO: Set to true to send emails, default false to prevent spam from being sent

    //Send to staff_profile
    $staff_profile_key = 'request_staff_profile_editor_training_profile';
    $staff_profile_email = $this->entity->field_staff_profile_email->value;
    //$staff_profile_email = 'extensionweb@iastate.edu'; //TODO Remove in production
    $langcode = $this->entity->getOwner()->getPreferredLangcode();
    $staff_profile_result = $mailManager->mail($module, $staff_profile_key, $staff_profile_email, $langcode, $params, NULL, $send);

    //Send to regional director
    $director_key = 'request_staff_profile_editor_training_reg_director';
    $reg_director_email = \Drupal::currentUser()->getEmail();
    //$reg_director_email = 'extensionweb@iastate.edu'; //TODO remove in production
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $reg_dir_result = $mailManager->mail($module, $director_key, $reg_director_email, $langcode, $params, NULL, $send);

    //Send to extweb
    $extweb_key = 'request_staff_profile_editor_training_extweb';
    $extweb_email = 'extensionweb@iastate.edu';
    $langcode = 'en';
    $ext_result = $mailManager->mail($module, $extweb_key, $extweb_email, $langcode, $params, NULL, $send);

    if (!array_key_exists('result', $reg_dir_result) || !array_key_exists('result', $ext_result) || !array_key_exists('result', $staff_profile_result)) {
      \Drupal::messenger()->addStatus(t('There was a problem sending notification emails to:'
      . (!array_key_exists('result', $reg_dir_result) ? " Regional Director" . (!array_key_exists('result', $ext_result) || !array_key_exists('result', $staff_profile_result) ? "," : "") : "")
      . (!array_key_exists('result', $ext_result) ? " ExtensionWeb" . (!array_key_exists('result', $staff_profile_result) ? "," : "") : "")
      . (!array_key_exists('result', $staff_profile_result) ? " Staff Profile: " . $this->entity->field_staff_profile_email->value : "") . '.'), 'error');
    } else {
      \Drupal::messenger()->addStatus(('Notification emails sent.'));
    }
    $form_state->setRedirect('staff_profile_reed.county_web_editors');
  }
}
