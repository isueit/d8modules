<?php

namespace Drupal\county_jobs_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides a Jobs Block from a feed
 *
 * @Block(
 *   id = "county_jobs_block",
 *   admin_label = @Translation("County Jobs"),
 *   category = @Translation("Job Openings"),
 * )
 */
class CountyJobsBlock extends BlockBase
{
  const FEED_URL = 'https://www.extension.iastate.edu/jobs/feeds/county_jobs';

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    //Initialize some variables
    $config = $this->getConfiguration();
    $feed_url = !empty($config['feed_url']) ? $config['feed_url'] : self::FEED_URL;
    $feed_url = str_replace(' ', '+', $feed_url);
    $results = '';

    $all_listings = json_decode(ISUEOHelpers\Files::fetch_url($feed_url), true);
    $listings = [];
    foreach ($all_listings as $listing) {
      if (empty($config['county'])) {
        $listings[] = $listing;
      } else {
        if ($listing['field_base_county'] == $config['county']) {
          $listings[] = $listing;
        }
      }
    }

    $results .= '<ul class="job-links">' . PHP_EOL;
    $results .= '  <li><a href="https://www.extension.iastate.edu/jobs/">County Job Openings</a></li>' . PHP_EOL;
    $results .= '  <li><a href="https://www.jobs.iastate.edu">ISU Job Openings</a></li>' . PHP_EOL;
    $results .= '  <li><a href="https://www.extension.iastate.edu/diversity/">Diversity and Civil Rights</a></li>' . PHP_EOL;
    $results .= '</ul>' . PHP_EOL;

    if (empty($listings)) {
      $results .= '<p>No job openings at this time</p>' . PHP_EOL;
    } else {
      $results .= PHP_EOL . '<ul class="county-job-openings">' . PHP_EOL;
      foreach ($listings as $listing) {
        $results .= '  <li>' . PHP_EOL;
        $results .= '    <a href="' . $listing['view_node'] . '">' . trim($listing['title']) . '</a><br />' . PHP_EOL;
        $results .= '    ' . $listing['field_town_city'] . ', IA<br />' . PHP_EOL;
        $results .= '    ' . $listing['field_base_county'] . ' County Extension Office<br />' . PHP_EOL;
        $results .= '    <em>Application Deadline:</em> ' . $listing['field_application_deadline_date'] .
          ($listing['field_open_until_filled']  === 'True' ? ' This position will remain open until filled' : '') .
          '<br />' . PHP_EOL;
        $results .= '  </li>' . PHP_EOL;
      }
      $results .= '</ul>' . PHP_EOL;
    }


    return [
//      '#allowed_tags' => $tags,
      '#markup' => $results,
    ];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $config = $this->getConfiguration();

    $form['feed_url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL of Jobs Feed'),
      //'#description' => t('Zero (0) means display all events'),
      '#size' => 120,
      '#default_value' => !empty($config['feed_url']) ? $config['feed_url'] : self::FEED_URL,
    );

    // Get the list of Iowa Counties
    $taxonomy_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'counties_in_iowa']);
    if (sizeof($taxonomy_terms) > 0) {
      $counties = array('' => 'Include All');
      foreach ($taxonomy_terms as $taxonomy_term) {
        $counties[$taxonomy_term->label()] = $taxonomy_term->label();
      }

      $form['county'] = array(
        '#type' => 'select',
        '#options' => $counties,
        '#title' => t('Limit By county'),
        '#description' => t('If something is selected, then only show events for that county'),
        '#default_value' => $config['county'],
      );
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();

    $this->configuration['feed_url'] = $values['feed_url'];
    $this->configuration['county'] = array_key_exists('county', $values) ? $values['county'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    /*
    return array(
      'max_size' => '400',
      'base_color' => '#CC0000',
      'served_color' => '#F1BE48',

    );
    */
  }

  private function fixCounty($county_name)
  {
    /*
    $county_name = str_replace(' ', '_', $county_name);
    $county_name = str_replace('\'', '', $county_name);
    $county_name = str_replace('Pottawattamie_-_West', 'West_Pottawattamie', $county_name);
    $county_name = str_replace('Pottawattamie_-_East', 'East_Pottawattamie', $county_name);

    return $county_name;
    */
  }
}

