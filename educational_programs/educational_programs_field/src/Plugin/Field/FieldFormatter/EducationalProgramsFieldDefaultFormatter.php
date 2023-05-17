<?php

/**
 * @file
 * Contains \Drupal\educational_programs_field\Plugin\field\formatter\SnippetsDefaultFormatter.
 */

namespace Drupal\educational_programs_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use DOMDocument;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Plugin implementation of the 'educational_programs_field_default' formatter.
 *
 * @FieldFormatter(
 *   id = "educational_programs_field_default",
 *   label = @Translation("Educational Programs"),
 *   field_types = {
 *     "educational_programs_field"
 *   }
 * )
 */
class EducationalProgramsFieldDefaultFormatter extends FormatterBase {

  public static $canonicalURL = '';

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Get the feed from MyData
    $fromFeed = file_get_contents('https://datastore.exnet.iastate.edu/mydata/EducationalPrograms.json');
    $fromFeed = str_replace('\u0026#039;', '\'', $fromFeed);
    $programs = json_decode($fromFeed, TRUE);

    // Do NOT cache a page with this block on it.
    \Drupal::service('page_cache_kill_switch')->trigger();
    $elements['#cache']['max-age'] = 0;
    $elements['#cache']['contexts'] = [];
    $elements['#cache']['tags'] = [];

    foreach ($items as $delta => $item) {
      // Skip if we don't have a taxonomy term ID
      if (empty($item->term_id)) {
        continue;
      }

      // Load the Taxonomy Term
      $term = Term::load($item->term_id);
      if ($term == null) {
        $output = PHP_EOL . '<div class="educational_program_description">Program not found</div>' . PHP_EOL;
        $elements[$delta] = array('#markup' => $output);
        continue;
      }

      // Find the description of the program by steppign through the list of Programs until we find the right one based on the MyData ID
      $description = 'Program not found';
      $website = "";
      $website_description = "";
      foreach ($programs as $program) {
        if ($program['Id'] == trim(strip_tags($term->getDescription()))) {
          if (!empty($program['Web_Description__c'])) {
            $description = str_replace(' target="_blank"', '', $program['Web_Description__c']);
          } elseif (!empty($program['hed__Extended_Description__c'])) {
            $description = $program['hed__Extended_Description__c'];
          } elseif (!empty($program['hed__Description__c'])) {
            $description = $program['hed__Description__c'];
          } else {
            $description = 'Description not found';
          }
          if (!empty($program['Smugmug_ID__c'])) {
            $description = '<img class="educational_program_image" src="https://photos.smugmug.com/photos/' . $program['Smugmug_ID__c'] . '/0/XL/' . $program['Smugmug_ID__c'] . '-XL.jpg" alt="" />'. $description . '<div class="clearer"></div>';
          }

          if (!empty($program['Planned_Program_Website__c'])) {
            $website = $program['Planned_Program_Website__c'];
            $website_description = "More about " . $program['Name'];
          }
          break;
        }
      }

      // Redirect if user is anonymous, we have a web site, and auto_redirect is enabled
      if (\Drupal::currentUser()->isAnonymous() && !empty($website) && !empty($item->auto_redirect)) {
        $response = new RedirectResponse($website);
        $response->send();
        continue;
      }

      // Render output
      $output = PHP_EOL;
      $output .= '<div class="educational_program">' . PHP_EOL;

      // Add a note if page is being redirected
      if (!empty($website) && !empty($item->auto_redirect)) {
        $output .= '<div class="educational_program_redirected">' . PHP_EOL;
        $output .= '<h4>Note: Page Redirected</h4>' . PHP_EOL;
        $output .= '<p>Public users will automatically be redirected to <a href="' . $website . '">' . $website . '</a></p>' . PHP_EOL;
        $output .= '</div>' . PHP_EOL;
      }

      $output .= '<div class="educational_program_description">' . PHP_EOL;
      $output .= $description . PHP_EOL;
      $output .= '</div>' . PHP_EOL;
      if (!empty($website)) {
        $output .= '<div class="educational_program_link"><a href="' . $website . '">' . $website_description . '</a></div>' . PHP_EOL;
      }
      $output .= '</div>' . PHP_EOL;

      //May need this stuff
      $tags = FieldFilteredMarkup::allowedTags();
      array_push($tags, 'iframe', 'div', 'h2', 'h3', 'h4', 'h5', 'h5', 'h6', 'footer', 'article', 'img');
      while (preg_match('/<iframe[a-zA-Z0-9\" =\/\._\?\%]+\/>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
        $output = substr_replace($output, "> </iframe>", strlen($matches[0][0])+$matches[0][1]-2, 11);
      }

      //$elements[$delta] = array('#markup' => $output);
      $elements[$delta] = array('#markup' => $output, '#allowed_tags' => $tags);
    }
    return $elements;
  }
}
