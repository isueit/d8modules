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
use Drupal\isueo_helpers\ISUEOHelpers;

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
class EducationalProgramsFieldDefaultFormatter extends FormatterBase
{

  public static $canonicalURL = '';

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $elements = array();

    // Get the feed from MyData
    $fromFeed = ISUEOHelpers\Files::fetch_url('https://datastore.exnet.iastate.edu/mydata/EducationalPrograms.json');
    $fromFeed = str_replace('\u0026#039;', '\'', $fromFeed);
    $products = json_decode($fromFeed, TRUE);

    // Record types to include in the catalog
    // 0128Z000000yeo8QAA = Product Family Record Type
    // 012460000012EiaAAE = Educational Program Record Type
    // 0124p000000S43XAAS = Educational Service or Consultation
    //$types = ['0128Z000000yeo8QAA', '012460000012EiaAAE', '0124p000000S43XAAS'];

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
        continue;
      }
      $program_id = trim(strip_tags($term->getDescription()));

      // Find the description of the program by stepping through the list of Programs until we find the right one based on the MyData ID
      $website = '';
      $description = '';
      $redirected = false;
      $subprograms = [];
      $program = [];

      foreach ($products as $product) {
        //if (!in_array($product['RecordTypeId'], $types)) {
          //continue;
        //}
        if ($product['Id'] == $program_id) {
          $description = $this::educational_programs_field_get_description($product);
          if (!empty($product['Planned_Program_Website__c'])) {
            $website = $product['Planned_Program_Website__c'];
          }

          // Add a note if page is being redirected
          if (!empty($website) && !empty($item->auto_redirect)) {
            $redirected = true;
          }

          $program = [
            'name' => $product['Name'],
            'description' => [
              '#markup' => $description,
            ],
            'url' => $website,
            'smugmug_id' => $product['Smugmug_ID__c'],
            'redirected' => $redirected,
            'hide_image' => !empty($item->hide_image),
          ];
        }

        if (
          !empty($product['Related_Program__c'])
          && $program_id == $product['Related_Program__c']
          && ($product['Show_on_Program_Landing_Page__c'] || $product['Public_Access__c'])
        ) {
          $subprograms[$product['Name']] = [
            //'#theme' => 'educational_programs_field_program',
            'name' => $product['Name'],
            'url' => $product['Planned_Program_Website__c'],
            'description' => ['#markup' => $this::educational_programs_field_get_description($product),],
          ];
        }
      }
      ksort($subprograms);
      $elements[$delta] = [
        '#theme' => 'educational_programs_field_default',
        '#program' => $program,
        '#children' => [

          '#theme' => 'educational_programs_field_children',
          '#subprograms' => $subprograms,
        ],
      ];

      // Redirect if user is anonymous, we have a web site, and auto_redirect is enabled
      if (\Drupal::currentUser()->isAnonymous() && !empty($website) && !empty($item->auto_redirect)) {
        $response = new RedirectResponse($website);
        $response->send();
        continue;
      }

      //May need this stuff
      //$tags = FieldFilteredMarkup::allowedTags();
      //array_push($tags, 'iframe', 'div', 'h2', 'h3', 'h4', 'h5', 'h5', 'h6', 'footer', 'article', 'img');
      //while (preg_match('/<iframe[a-zA-Z0-9\" =\/\._\?\%]+\/>/', $description, $matches, PREG_OFFSET_CAPTURE)) {
      //$description = substr_replace($description, "> </iframe>", strlen($matches[0][0])+$matches[0][1]-2, 11);
      //}

    }

    return $elements;
  }

  private static function educational_programs_field_get_description($product)
  {
    $description = '';
    if (!empty($product['Web_Description__c'])) {
      $description = str_replace(' target="_blank"', '', $product['Web_Description__c']);
    } elseif (!empty($product['hed__Extended_Description__c'])) {
      $description = $product['hed__Extended_Description__c'];
    } elseif (!empty($product['hed__Description__c'])) {
      $description = $product['hed__Description__c'];
    } else {
      $description = 'Description not found';
    }

    return $description;
  }
}
