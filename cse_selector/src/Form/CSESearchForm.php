<?php
/**
 * @file
 * Contains \Drupal\cse_selector\Form\CSESearchForm
 */
namespace Drupal\cse_selector\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class CSESearchForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cse_search_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('cse_selector.settings');
    $cse_search_type = $config->get('cse_selector_default_search_type');
    $cse_url_text = $config->get('cse_selector_url_text');
    $cse_results_page_name = $config->get('cse_selector_results_page_name');
    $get_results = \Drupal::request()->query->all();

    $form['#method'] = 'get';
    $url = \Drupal\Core\Url::fromRoute('cse_selector.cse_selector_search_results');
    $form['#action'] = $url->toString();

    $form['search'] = array(
      '#type' => 'fieldset',
      '#title' => t('')
    );
    $form['search'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => ['form-group', 'isu-form-type_search', 'js-form-item', 'form-item', 'js-form-type-search', 'form-item-keys', 'js-form-item-keys', 'form-no-label']
      )
    );
    $form['submit'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => ['form-actions', 'js-form-wrapper', 'form-wrapper']
      ),
    );
    $form['search']['search_broadness'] = array(
      '#type' => 'hidden',
    );
    if (array_key_exists('search_broadness', $get_results)) {
      $form['search']['search_broadness']['#default_value'] = $get_results['search_broadness'];

    } else {
      $form['search']['search_broadness']['#default_value'] = $cse_search_type;
    }
    $form['search'][$cse_url_text] = array(
      '#type' => 'search',
      '#theme_wrappers' => [],
      '#attributes' => [
        'placeholder' => t('Search'),
        'class' => ['form-control',
        'isu-form-control_search',
        'isu-search__search-field'],
        'title' => 'search box',
      ],
    );
    if (array_key_exists($cse_url_text, $get_results)) {
          $form['search'][$cse_url_text]['#default_value'] = $get_results[$cse_url_text];
    } else {
      $form['search'][$cse_url_text]['#default_value'] = '';
    }
    $form['submit']['search_submit'] =
    [
      '#type' => 'inline_template',
      '#template' => '<button class="isu-search__search-btn button js-form-submit form-submit btn" data-drupal-selector="edit-submit" type="submit" id="search_edit-submit" value="' . (preg_match("/" . $cse_results_page_name . "/", \Drupal::service('path.current')->getPath()) != FALSE ? 'resubmit search' : 'submit search') . '"><span class="fas fa-search" aria-hidden="true"></span><span class="isu-search__search-btn-text">Submit Search</span></button>',
    ];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state){}
  public function validateForm(array &$form, FormStateInterface $form_state){}
}
