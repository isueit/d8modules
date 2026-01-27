<?php

namespace Drupal\county_office_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;

/**
 * Form for managing county data synchronization.
 */
class CountySyncForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'county_office_map_sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  $form['description'] = [
    '#markup' => '<p>' . $this->t('Sync contact information from county office websites to taxonomy fields. This scrapes the homepage of each county site.') . '</p>',
  ];
  
  $form['sync_all'] = [
    '#type' => 'details',
    '#title' => $this->t('Sync All Counties'),
    '#open' => TRUE,
  ];
  
  $form['sync_all']['info'] = [
    '#markup' => '<p><strong>' . $this->t('This will attempt to sync contact information from all county websites. This may take several minutes.') . '</strong></p>',
  ];
  
  $form['sync_all']['run_all'] = [
    '#type' => 'link',
    '#title' => $this->t('Run Full Sync'),
    '#url' => Url::fromRoute('county_office_map.sync_all'),
    '#attributes' => [
      'class' => ['button', 'button--primary', 'button--danger'],
      'onclick' => 'return confirm("This will sync all counties. Are you sure?")',
    ],
  ];
  
  // List all counties with individual sync buttons
  $form['counties'] = [
    '#type' => 'details',
    '#title' => $this->t('Sync Individual Counties'),
    '#open' => TRUE,
  ];
  
  $form['counties']['info'] = [
    '#markup' => '<p>' . $this->t('Test with individual counties first to make sure the scraping is working correctly.') . '</p>',
  ];
  
  $vocabulary_name = 'counties_in_iowa';
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree($vocabulary_name);
  
  $header = [
    $this->t('County'),
    $this->t('Website'),
    $this->t('Last Synced'),
    $this->t('Status'),
    $this->t('Actions'),
  ];
  
  $rows = [];
  foreach ($terms as $term) {
    $term_obj = Term::load($term->tid);
    if (!$term_obj) {
      continue;
    }
    
    // Check if website URL exists
    $website_url = '';
    if ($term_obj->hasField('field_website') && !$term_obj->get('field_website')->isEmpty()) {
      $website_url = $term_obj->get('field_website')->uri;
    }
    
    // Check if manual override is set
    $manual_override = FALSE;
    if ($term_obj->hasField('field_manual_override')) {
      $manual_override = $term_obj->get('field_manual_override')->value;
    }
    
    // Get last synced date
    $last_synced = '';
    if ($term_obj->hasField('field_last_synced') && !$term_obj->get('field_last_synced')->isEmpty()) {
      $timestamp = $term_obj->get('field_last_synced')->value;
      $last_synced = \Drupal::service('date.formatter')->format($timestamp, 'short');
    }
    
    $status = [];
    if (empty($website_url)) {
      $status[] = $this->t('No website URL');
    }
    if ($manual_override) {
      $status[] = $this->t('Manual override');
    }
    
    // Check if has data
    $has_data = [];
    if ($term_obj->hasField('field_contact_creator_address') && !$term_obj->get('field_contact_creator_address')->isEmpty()) {
      $has_data[] = 'Addr';
    }
    if ($term_obj->hasField('field_contact_creator_email') && !$term_obj->get('field_contact_creator_email')->isEmpty()) {
      $has_data[] = 'Email';
    }
    if ($term_obj->hasField('field_contact_creator_phone') && !$term_obj->get('field_contact_creator_phone')->isEmpty()) {
      $has_data[] = 'Phone';
    }
    if ($term_obj->hasField('field_contact_creator_hours') && !$term_obj->get('field_contact_creator_hours')->isEmpty()) {
      $has_data[] = 'Hours';
    }
    
    if (!empty($has_data)) {
      $status[] = 'Has: ' . implode(', ', $has_data);
    }
    
    // Build sync link as render array
    $sync_link = '';
    if (!empty($website_url) && !$manual_override) {
      $sync_link = [
        '#type' => 'link',
        '#title' => $this->t('Sync'),
        '#url' => Url::fromRoute('county_office_map.sync_county', ['county_tid' => $term->tid]),
        '#attributes' => [
          'class' => ['button', 'button--small'],
        ],
      ];
    }
    
    // Build website link as render array
    $website_display = '';
    if (!empty($website_url)) {
      $website_display = [
        '#type' => 'link',
        '#title' => $this->t('Visit'),
        '#url' => Url::fromUri($website_url),
        '#attributes' => [
          'target' => '_blank',
          'class' => ['button', 'button--extrasmall'],
        ],
      ];
    }
    
    // Add row with render arrays wrapped in 'data' key
    $rows[] = [
      'data' => [
        $term->name,
        ['data' => $website_display],
        $last_synced ?: $this->t('Never'),
        !empty($status) ? implode(' | ', $status) : '-',
        ['data' => $sync_link],
      ],
    ];
  }
  
  $form['counties']['table'] = [
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => $this->t('No counties found.'),
    '#sticky' => TRUE,
  ];
  
  return $form;
}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form doesn't have a submit button, actions are links
  }

}