<?php

use Drupal\node\Entity\Node;
use Drupal\isueo_helpers\ISUEOHelpers;
use Drupal\isueo_helpers\ISUEOHelpers\Taxonomy;
use Drupal\isueo_helpers\ISUEOHelpers\Typesense;
use Drupal\taxonomy\Entity\Term;

// Return an array of existing nodes with MyData ID as the array key
function plp_programs_get_existing_nodes(string $content_type, string $mydata_id_field)
{
  $nids = \Drupal::entityQuery('node')->accessCheck(false)->condition('type', $content_type)->execute();
  $tmpnodes =  Node::loadMultiple($nids);
  $nodes = [];
  foreach ($tmpnodes as $node) {
    $nodes[$node->get($mydata_id_field)->getString()] = $node;
  }

  return $nodes;
}

// Return an array of items from a JSON file
function plp_programs_get_related_items(string $json_url, string $field_name, array $programs)
{
  $my_array = [];

  // Find measurements for the programs
  $raw = ISUEOHelpers\Files::fetch_url($json_url);
  $widgets = json_decode($raw, true);
  foreach ($widgets as $widget) {
    // HACK: Skip non-public events
    if (array_key_exists('Public_Event__c', $widget) && empty($widget['Public_Event__c'])) {
      continue;
    }
    $my_array[$widget[$field_name]][] = $widget;
  }

  return $my_array;
}

// Returns the number of item related to the program and it's children, could be indicators, measurements, events, etc
function plp_programs_get_number_of_items(string $programid, array $items, array $children)
{
  $value = 0;

  // Add in the number of items directly associated with this program
  if (array_key_exists($programid, $items)) {
    $value = $value + count($items[$programid]);
  }

  // Also add in the items associated with the children
  if (array_key_exists($programid, $children)) {
    foreach ($children[$programid] as $child) {
      if (array_key_exists($child['Id'], $items)) {
        $value = $value + count($items[$child['Id']]);
      }
    }
  }

  return $value;
}


// Returns a string, the contents of the body field
function plp_programs_generate_body(array $program)
{
  $body = '';
  if (!empty($program['Web_Description__c'])) {
    $body = $program['Web_Description__c'];
  } elseif (!empty($program['hed__Extended_Description__c'])) {
    $body = $program['hed__Extended_Description__c'];
  } else {
    $body = $program['hed__Description__c'];
  }

  if (!empty($program['Intended_Audiences__c'])) {
    $body .= '<p><b>Intended Audiences:</b><br />' . str_replace(';', ', ', $program['Intended_Audiences__c']) . '</p>';
  }

  return $body;
}

// Returns a corrected (hopefully) URL
function plp_programs_fix_url(string $website)
{
  if (!empty($website)) {
    if ('htt' != substr($website, 0, 3)) {
      $website = 'https://' . $website;
    }
  }

  return $website;
}

// Handle the child nodes for programs
function plp_programs_handle_children(array $children, array $programs)
{
  // Initialize some variables
  $existing_children = [];
  $child_nodes = plp_programs_get_existing_nodes('plp_child', 'field_plp_child_mydata_id');
  $child_nodeIds = array_keys($child_nodes);
  $program_child_nodes = [];

  foreach ($children as $program_id => $value) {
    // Skip the children whan the program isn't in the PLP
    if (!array_key_exists($program_id, $programs)) {
      continue;
    }

    foreach ($value as $child) {
      // Make sure the child should be shown
      //if (!$child['Show_on_Program_Catalog__c']) {
      if (!$child['Show_on_Program_Landing_Page__c']) {
        continue;
      }

      // Initialize some child specific variables
      $child_id = $child['Id'];
      $existing_children[] = $child_id;
      $mynode = null;
      $save = false;

      // Get the existing node, create a new one when necessary
      if (!empty($child_nodes[$child_id])) {
        $mynode = $child_nodes[$child_id];
      } else {
        $mynode = Node::create(array('type' => 'plp_child', 'title' => html_entity_decode($child['Name']), 'field_plp_child_mydata_id' => $child_id,));
        $save = true;
      }

      // Handle Title
      if ($mynode->getTitle() != html_entity_decode($child['Name'])) {
        $mynode->setTitle(html_entity_decode($child['Name']));
        $save = true;
      }

      // Handle Body
      $body = plp_programs_generate_body($child);
      if ($mynode->body->value != $body) {
        $mynode->body->value = $body;
        $mynode->body->format = 'basic';
        $save = true;
      }

      // Handle website link
      $website = empty($child['Planned_Program_Website__c']) ? '' : plp_programs_fix_url($child['Planned_Program_Website__c']);
      if ($mynode->get('field_plp_child_website')->uri != $website) {
        $mynode->set('field_plp_child_website', ['uri' => $website, 'title' => 'More about ' . $child['Name']]);
        $save = true;
      }

      // Publish the node if it's unpublished
      if (!$mynode->isPublished) {
        $mynode->setPublished();
        $save = true;
      }

      // Only save if there has been a changed, this saves a ton of time
      if ($save) {
        $mynode->save();
      }

      // Keep track of node numbers of children
      $program_child_nodes[$program_id][] = $mynode->id();
    }
  }

  // Find and unpublish unused children
  $nodes_to_unpublish = array_diff($child_nodeIds, $existing_children);
  if (!empty($nodes_to_unpublish)) {
    $nids = Drupal::entityQuery('node')->accessCheck(false)->condition('type', 'plp_child')->condition('status', 1)->condition('field_plp_child_mydata_id', $nodes_to_unpublish, 'IN')->execute();

    $tmpnodes =  Node::loadMultiple($nids);
    foreach ($tmpnodes as $node) {
      $node->setUnpublished()->save();
    }
  }

  // return the node numbers array
  return $program_child_nodes;
}

// Return an array of counties that are in programs
function plp_programs_get_counties(array $programs_county_list, array $include_programs)
{
  $tmpstring = '';
  // Grab counties using this program
  foreach ($include_programs as $programid) {
    if (array_key_exists($programid, $programs_county_list)) {
      if (!empty($programs_county_list[$programid][0]['countiesLocal'])) {
        $tmpstring .= $programs_county_list[$programid][0]['countiesLocal'] . ';';
      }
      if (!empty($programs_county_list[$programid][0]['countiesRedirected'])) {
        $tmpstring .= $programs_county_list[$programid][0]['countiesRedirected'] . ';';
      }
    }
  }

  // Remove trailing ';', remove duplicates, and sort
  if (!empty($tmpstring)) {
    $tmpstring = rtrim($tmpstring, ';');
    $tmparray = array_unique(explode(';', $tmpstring));
    sort($tmparray);
    $tmpstring = implode(';', $tmparray);
  }

  return $tmpstring;
}

// Calculate the sort number for the program
function plp_programs_sort_calculator($mynode, array $child_nodes, string $program_id, $config)
{
  $tmpnum = 0;
  // Handle premoted on counties
  if (!empty($mynode->field_plp_program_on_counties->value)) {
    $tmpstring = $mynode->field_plp_program_on_counties->value;
    $tmpnum += $config->get('counties.county_boost') + ($config->get('counties.county_multiplier') * intval(count(explode(';', $tmpstring)) / $config->get('counties.county_interval')));
  }

  // Handle indicators and measurements
  if (!empty($mynode->field_plp_program_num_indicators->value)) {
    $tmpnum += $config->get('indicators.indicator_boost') + ($config->get('indicators.indicator_multiplier') * intval($mynode->field_plp_program_num_indicators->value / $config->get('indicators.indicator_interval')));
  }
  if (!empty($mynode->field_plp_program_num_measures->value)) {
    $tmpnum += $config->get('measurements.measurement_boost') + ($config->get('measurements.measurement_multiplier') * intval($mynode->field_plp_program_num_measures->value / $config->get('measurements.measurement_interval')));
  }

  // Handle events
  if (!empty($mynode->field_plp_program_num_events->value)) {
    $tmpnum += $config->get('events.event_boost') + ($config->get('events.event_multiplier') * intval($mynode->field_plp_program_num_events->value / $config->get('events.event_interval')));
  }

  // Handle children
  if (array_key_exists($program_id, $child_nodes)) {
    $tmpnum += $config->get('children.child_boost') + ($config->get('children.child_multiplier') * intval(count($child_nodes[$program_id]) / $config->get('children.child_interval')));
  }

  $num_courses = count($mynode->get('field_plp_program_courses'));
  if ($num_courses > 0) {
    $tmpnum += $config->get('courses.course_boost') + ($config->get('courses.course_multiplier') * intval($num_courses / $config->get('courses.course_interval')));
  }

  return $tmpnum;
}

// Handle hierarchy of categories and topics
function plp_programs_handle_topic_hierarchy(array $categories_topics, array $programs)
{
  foreach ($programs as $program) {
    // Get the ID of the category's taxonomy term
    if (!empty($program['Program_landing_Page_Category__c'])) {
      $category_id = $categories_topics[$program['Program_landing_Page_Category__c']];
    } else {
      continue;
    }

    // Step through each topic
    $topics = empty($program['Program_Landing_Page_Topic__c']) ? [] : explode(';', $program['Program_Landing_Page_Topic__c']);
    foreach ($topics as $topic) {
      // Make sure we have a taxonomy term for this topic
      if (!empty($topic) && array_key_exists($topic, $categories_topics)) {
        // Get the taxonomy term
        $parent_ids = [];
        $term = Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->load($categories_topics[$topic]);

        // Build an array of the parent ids
        foreach ($term->parent as $parent) {
          if ($parent->target_id > 0) {
            $parent_ids[] = $parent->target_id;
          }
        }

        // Check if the current category is in the parent array, if not, add it
        if (!in_array($category_id, $parent_ids)) {
          $parent_ids[] = $category_id;
          $term->set('parent', $parent_ids);
          $term->save();
        }
      }
    }
  }
}
