<?php

namespace Drupal\staff_profile_reed\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Component\Datetime\Time;

/**
 * @ingroup staff_profile_reed
 */
class CountyWebEditorsConfirmRemove extends ContentEntityConfirmFormBase {
  //County to be removed
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
    return $this->t('Are you sure you want to remove %name from %cty county?', array('%name' => $this->entity->label(), '%cty' => $this->county->label()));
  }

  //Fix Incoming
  //https://www.drupal.org/project/drupal/issues/2582295
  public function getCancelUrl() {
    return new Url('staff_profile_reed.county_web_editors');
  }

  public function getConfirmText() {
    return $this->t('Remove from County');
  }

  public function getDescription() {
    //return $this->t('Removes staff_profile from users authorized in %cty county Web Editors.', array('%cty' => $this->county->label()));
    return '';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Remove item
    $staff_profile = $this->getEntity();
    $ctys = $staff_profile->get('field_staff_profile_cty_author')->getValue();
    $key = array_search($this->county->id(), array_column($ctys, 'target_id'));
    $staff_profile->get('field_staff_profile_cty_author')->removeItem($key);
    $staff_profile->save();

    //Send Mail
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'staff_profile_reed';
    $params['name'] = $this->entity->label();
    $params['netid'] = $this->entity->field_staff_profile_email->value;
    $params['county'] = $this->county->label();
    $params['reg_director'] = \Drupal::currentUser()->getAccountName();
    $send = true; //TODO: Set to true to send emails, default false to prevent spam from being sent

    //Send to regional director
    $director_key = 'remove_staff_profile_editor_cty_reg_director';
    $reg_director_email = \Drupal::currentUser()->getEmail();
    //$reg_director_email = 'extensionweb@iastate.edu'; //TODO Remove on production
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $reg_dir_result = $mailManager->mail($module, $director_key, $reg_director_email, $langcode, $params, NULL, $send);

    //Send to extweb
    $extweb_key = 'remove_staff_profile_editor_cty_extweb';
    $extweb_email = 'extensionweb@iastate.edu';
    $langcode = 'en';
    $ext_result = $mailManager->mail($module, $extweb_key, $extweb_email, $langcode, $params, NULL, $send);

    if (!array_key_exists('result', $reg_dir_result) || !array_key_exists('result', $ext_result)) {
      \Drupal::messenger()->addStatus(t('There was a problem sending notification emails to' . (!array_key_exists('result', $reg_dir_result) ? " Regional Director" . (!array_key_exists('result', $ext_result) && !array_key_exists('result', $reg_dir_result) ? "," : "") : "") . (!array_key_exists('result', $ext_result) ? " ExtensionWeb" : "")), 'error');
    } else {
      \Drupal::messenger()->addStatus(t('Notification emails sent.'));
    }

    $this->logger('staff_profile_reed')->notice('Removed %title from county editor in %cty county', array('%title' => $this->entity->label(), '%cty' => $this->county->label()));
    $form_state->setRedirect('staff_profile_reed.county_web_editors');
  }
}
