<?php
namespace Drupal\county_job_openings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class CreateCountyJobOpeningForm
 */
class CreateCountyJobOpeningForm extends FormBase {
  public function getFormId() {
    return 'create_county_job_opening_form';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $template_id = $form_state->getValue('job_template');
    $template = Node::load($template_id);
    
    $node = Node::create([
      'type' => 'job_openings',
      'title' => $template->getTitle(),
      'status' => 0
    ]);
    $node->body->value = $template->body->value;
    $node->body->format = $template->body->format;
    
    $node->field_requirements->value = $template->field_requirements->value;
    $node->field_requirements->format = $template->field_requirements->format;
    $node->save();
    
    //Assumes location of form is base/node/add/county_job_openings_templated
    $response = new RedirectResponse('../../node/' . $node->id() . '/edit', '302');
    $response->send();
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $nids = \Drupal::entityQuery('node')->condition('type','template_jobs')->execute();
    $nodes =  Node::loadMultiple($nids);
    $jobs_templates = [];
    foreach ($nodes as $id => $node) {
      $jobs_templates[$node->id()] = $this->t($node->getTitle());
    }

    $form['job_template'] = array(
      '#type' => 'radios',
      '#title' => t('County Template'),
      '#required' => TRUE,
      '#options' => $jobs_templates
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

  function validataForm(array &$form, FormStateInterface $form_state) {
  }

}