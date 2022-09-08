<?php

namespace Drupal\display_county_jobs\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for staff_profile_primary module
 */
class DisplayCountyJobs extends ControllerBase
{

  /**
   * Redirect to User's profile page, if found.
   *
   * @return array
   *   A simple render array.
   */
  public function jobs()
  {
    // Deny any page caching on the current request.
    \Drupal::service('page_cache_kill_switch')->trigger();
    $config = Drupal::config('display_county_jobs.settings');
    $feed_url = str_replace(' ', '+', $config->get('feed_url'));
    $openings = json_decode(file_get_contents($feed_url), true);
    $site_name = \Drupal::config("system.site")->get('name');

    $results = '</ul>' . PHP_EOL;
    $results .= '<ul class="job-links">' . PHP_EOL;
    $results .= '<li><a href="/jobs">County Job Openings</a></li>' . PHP_EOL;
    $results .= '<li><a href="https://www.jobs.iastate.edu">ISU Job Openings</a></li>' . PHP_EOL;
    $results .= '<li><a href="https://www.extension.iastate.edu/diversity/">Diversity and Civil Rights</a></li>' . PHP_EOL;
    $results .= '</ul>' . PHP_EOL;

    if (empty($openings)) {
      $results .= sprintf('<p>No job openings for %s at this time</p>', $site_name) . PHP_EOL;
    } else {
      $results .= PHP_EOL . '<ul class="county-job-openings">' . PHP_EOL;
      foreach ($openings as $opening) {
        $results .= '  <li>' . PHP_EOL;
        $results .= '    <a href="' . $opening['view_node'] . '">' . trim($opening['title']) . '</a><br />' . PHP_EOL;
        $results .= '    ' . $opening['field_town_city'] . ', IA<br />' . PHP_EOL;
        $results .= '    ' . $opening['field_base_county'] . ' County Extension Office<br />' . PHP_EOL;
        //if ($opening['field_counties_served'] != $opening['field_base_county']) {
          //$results .= '    <em>Counties Served:</em> ' . $opening['field_counties_served'] . '<br >' . PHP_EOL;
        //}
        $results .= '    <em>Application Deadline:</em> ' . $opening['field_application_deadline_date'] .
          ($opening['field_open_until_filled']  === 'True' ? ' This position will remain open until filled' : '') .
          '<br />' . PHP_EOL;
        $results .= '  </li>' . PHP_EOL;
      }
    }


    // Return the results, when no redirect found
    $element = array(
      '#title' => 'Job Openings',
      '#markup' => $results,
      '#attached' => [
        'library' => ['display_county_jobs/display_county_jobs'],
      ],
    );

    return $element;
  }
}
