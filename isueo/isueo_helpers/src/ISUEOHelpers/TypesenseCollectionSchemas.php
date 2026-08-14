<?php

namespace Drupal\isueo_helpers\ISUEOHelpers;

use Drupal;
use Exception;
use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;

class TypesenseCollectionSchemas
{
  public static function getSchema(string $collection)
  {
    $definition = self::getDefinition($collection);
    if (empty($definition)) {
      Drupal::messenger()->addError('Not Found: Definition for ' . $collection);
      return [];
    }
    $schema = [
      'name' => $collection,
      'fields' => [],
      'default_sorting_field' => $definition['default_sorting_field'],
      'enable_nested_fields' => false,
      'symbols_to_index' => [],
      'token_separators' => []
    ];

    // Add the fields to the schema
    foreach ($definition['fields'] as $field => $type) {
      $schema['fields'][] =
        [
          'name' => $field,
          'type' => $type,
          'facet' => in_array($field, $definition['facets']),
          'optional' => in_array($field, $definition['optional']),
          'index' => true,
          'sort' => in_array($field, $definition['sort']),
          'infix' => false,
          'locale' => '',
          'stem' => false,
          'stem_dictionary' => '',
          'store' => true
        ];
    }
    return $schema;
  }

  private static function getDefinition(string $collection)
  {
    /*
     * Yank this framework, and paste it at the end of this function
     * 22 lines
     */
    /*

      'collection' => [
        'default_sorting_field' => 'sort_order',

        // Define the fields
        'fields' => [
          'sort_order' => 'int32',
        ],

        // Sort fields
        'sort' => [
          'sort_order',
        ],

        // Fields that are facets
        'facets' => [
        ],

        // Optional fields
        'optional' => [
        ],
      ],

    */
    $definitions = [
      'events' => [
        'default_sorting_field' => 'sort_order',

        // Sort fields
        'sort' => [
          'sort_order',
        ],

        // Define the fields
        'fields' => [
          'title' => 'string',
          'description' => 'string',
          'delivery_method' => 'string',
          'Program_State__c' => 'string',
          'Event_Location__c' => 'string',
          'PrimaryProgramUnit__c' => 'string',
          'category' => 'string',
          'topics' => 'string[]',
          'county' => 'string[]',
          'smugmug_id' => 'string',
          'sort_order' => 'int32',
          'last_updated_time' => 'int32',
          'Next_Start_Date__c' => 'int64',
          'sessions' => 'int64[]',
          'sessions_end_date_time' => 'int64[]',
          'Contact_Information_Name__c' => 'string',
          'Contact_Information_Email__c' => 'string',
          'Contact_Information_Phone__c' => 'string',
          'Delivery_Language__c' => 'string',
          'Instructor_Information_Name__c' => 'string',
          'Instructor_Information_Email__c' => 'string',
          'Instructor_Information_Phone__c' => 'string',
          'End_Date_and_Time__c' => 'int64',
          'Start_Time_and_Date__c' => 'int64',
          'Event_Location_Site_Building__c' => 'string',
          'Event_Location_Street_Address__c' => 'string',
          'Event_Location_Zip_Code__c' => 'string',
          'plp_program' => 'string',
          'Planned_Program_Website__c' => 'string',
          'Program_Offering_Website__c' => 'string',
          'Registration_Opens__c' => 'int64',
          'Registration_Deadline__c' => 'int64',
          'Registration_Link__c' => 'string',
        ],

        // Fields that are facets
        'facets' => [
          'delivery_method',
          'Delivery_Language__c',
          'PrimaryProgramUnit__c',
          'plp_program',
          'category',
          'topics',
          'county',
        ],

        // Optional fields
        'optional' => [
          'Program_State__c',
          'category',
          'topics',
          'county',
          'Contact_Information_Name__c',
          'Contact_Information_Email__c',
          'Contact_Information_Phone__c',
          'Delivery_Language__c',
          'Instructor_Information_Name__c',
          'Instructor_Information_Email__c',
          'Instructor_Information_Phone__c',
          'End_Date_and_Time__c',
          'Start_Time_and_Date__c',
          'Event_Location_Site_Building__c',
          'Event_Location_Street_Address__c',
          'Event_Location_Zip_Code__c',
          'smugmug_id',
          'plp_program',
          'Planned_Program_Website__c',
          'Program_Offering_Website__c',
          'Registration_Opens__c',
          'Registration_Deadline__c',
          'Registration_Link__c',
        ],
      ],

      'plp_programs' => [
        'default_sorting_field' => 'sort_order',

        // Define the fields
        'fields' => [
          'type' => 'string',
          'audiences' => 'string[]',
          'body' => 'string',
          'category_id' => 'int32',
          'category_name' => 'string',
          'children_body' => 'string[]',
          'children_title' => 'string[]',
          'event_program_ids' => 'string[]',
          'number_of_events' => 'int32',
          'program_area' => 'string',
          'search_terms' => 'string',
          'smugmug_id' => 'string',
          'sort_order' => 'int32',
          'summary' => 'string',
          'title' => 'string',
          'topic_ids' => 'int32[]',
          'topic_names' => 'string[]',
          'url' => 'string',
        ],

        // Sort fields
        'sort' => [
          'sort_order',
          'category_id',
          'number_of_events',
        ],

        // Fields that are facets
        'facets' => [
          'type',
          'category_name',
          'program_area',
          'topic_names',
          'audiences',
        ],

        // Optional fields
        'optional' => [
          'type',
        ],
      ],

      'extension_content' => [
        'default_sorting_field' => '',

        // Define the fields
        'fields' => [
          'text_content' => 'string',
          'title' => 'string',
          'changed' => 'string',
          'created' => 'string',
          'site_name' => 'string',
          'url' => 'string',
          'content_type' => 'string',
          'summary' => 'string',
          'rendered_content' => 'string',
          'published' => 'bool',
        ],

        // Sort fields
        'sort' => [
          'published',
        ],

        // Fields that are facets
        'facets' => [
          'content_type',
          'published',
          'site_name',
        ],

        // Optional fields
        'optional' => [
          'text_content',
        ],
      ],

      'events_programs' => [
        'default_sorting_field' => '',

        // Define the fields
        'fields' => [
          'last_updated_time' => 'int32',
          'Title' => 'string',
          'Planned_Program__c' => 'string',
          'end_date_time' => 'int64',
        ],

        // Sort fields
        'sort' => [
        ],

        // Fields that are facets
        'facets' => [
        ],

        // Optional fields
        'optional' => [
        ],
      ],

      // Add new Definitions here
    ];
    $definitions['deleteme_brian'] = $definitions['plp_programs'];

    // ForStaff collection should be the exact same as extension_content
    if ($collection == 'ForStaff') {
      $collection = 'extension_content';
    }
    $definitions['deleteme_brian'] = $definitions['plp_programs'];

    if (array_key_exists($collection, $definitions)) {
      return $definitions[$collection];
    } else {
      return [];
    }
  }

  public static function getSynonyms(string $collection)
  {
    return [
      'stem_synonyms' => ['synonyms' => ['stem', 'scie', 'scienc', 'science', 'sciences']],
      'spanish_synonyms' => ['synonyms' => ['Spanish', 'Latino', 'Latina', 'Latinx']],
      'beef_synonyms' => ['synonyms' => ['beef', 'cow', 'cows', 'calf', 'calv', 'calve', 'calves', 'calvi', 'calvin', 'calving', 'catt', 'cattl', 'cattle']],
      'canning_synonyms' => ['synonyms' => ['canning', 'preserve']],
      'certificate_synonyms' => ['synonyms' => ['certificate', 'certification']],
      'disability_synonyms' => ['synonyms' => ['disability', 'disable', 'disabled', 'disabilities']],
      'elderly_synonyms' => ['synonyms' => ['elderly', 'elderl', 'elder', 'aging', 'older']],
      'garden_synonyms' => ['synonyms' => ['gard', 'garde', 'garden', 'gardeni', 'gardenin', 'gardening', 'gardene', 'gardener', 'gardeners']],
      'horse_synonyms' => ['synonyms' => ['hors', 'horse', 'equin', 'equine']],
      'kids_synonyms' => ['synonyms' => ['kid', 'kids', 'child', 'childr', 'childre', 'children']],
      'pig_synonyms' => ['synonyms' => ['pork', 'pig', 'pigs', 'swine', 'hog', 'hogs']],
      'tax_synonyms' => ['synonyms' => ['tax', 'taxe', 'taxes']]
      //'_synonyms' => ['synonyms' => ['', '', '', '', ]],
    ];
  }
}
