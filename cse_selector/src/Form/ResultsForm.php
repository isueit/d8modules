<?php
namespace Drupal\cse_selector\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ResultsForm extends FormBase {
  public function getFormId() {
    return 'search_results';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('cse_selector.settings');
    $cse_id_key = $config->get('cse_selector_id_key');
    $cse_narrow_search_text = $config->get('cse_selector_narrow_search_text');
    $cse_narrow_search_query = $config->get('cse_selector_narrow_search_query');
    $cse_wide_search_text = $config->get('cse_selector_wide_search_text');
    $cse_search_type = $config->get('cse_selector_default_search_type');
    $cse_url_text = $config->get('cse_selector_url_text');
    $cse_results_page_name = $config->get('cse_selector_results_page_name');
    $get_results = \Drupal::request()->query->all();
    if (array_key_exists('search_broadness' , $get_results)) {
      $searchbroadness = preg_replace("/[^a-z]+/","",$get_results['search_broadness']);
    }

    $form['#method'] = 'get';
    $form['search']['search_broadness'] = array(
      '#type' => 'radios',
      '#attributes' => array(
        'onchange' => 'add_param();form.submit("cse_selector_results_form");',
      ),
      '#executes_submit_callback' => TRUE,
      '#default_value' => (array_key_exists('search_broadness', $get_results) ? $searchbroadness : $cse_search_type),
    );

// Add the options to the radio button field
if ($cse_narrow_search_text) {
    $form['search']['search_broadness']['#options']['narrow'] = t($cse_narrow_search_text);
}
$form['search']['search_broadness']['#options']['wide'] = t($cse_wide_search_text);

    if (array_key_exists($cse_url_text, $get_results)) {
      $form['search'][$cse_url_text] = array(
        '#type' => 'hidden',
        '#default_value' => $get_results[$cse_url_text],
      );
    }
    $form['search']['search_submit']  = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );
    //Loads external JS file to connect with google api
    $form['#attached']['library'][] = 'cse_selector/cse_selector_results';
    $block = '';
    $block .= '<script class="cse_script">var cx="' . $cse_id_key . '";document.getElementById("edit-search-submit").style.display="none";</script>';
    $form['search']['script'] = array(
      '#type' => 'item',
      '#markup' => $block,
      '#allowed_tags' => ['script'],
    );

    $form['search']['results'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['gcse-searchresults-only'],
        'data-resultsUrl' => ["https://www.extension.iastate.edu" . base_path()],
        'data-queryParameterName' => [$cse_url_text],
      ],
      '#value' => '',
    ];
    if (array_key_exists('search_broadness' , $get_results) && $searchbroadness == 'narrow') {
      $form['search']['results']['#attributes']['data-as_sitesearch'] = $cse_narrow_search_query;
    }
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {}
  public function validateForm(array &$form, FormStateInterface $form_state){}
}
