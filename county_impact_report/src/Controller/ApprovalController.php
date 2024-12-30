<?php

namespace Drupal\county_impact_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides route responses for the program_offering_blocks  module.
 */
class ApprovalController extends ControllerBase
{
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function regional_director()
  {
    // Do NOT cache the events details page
    \Drupal::service('page_cache_kill_switch')->trigger();

    $results = '';
    $title = 'Error';
    $nids = \Drupal::entityQuery('node')->accessCheck(false)->condition('type', 'county_impact_report')->sort('created', 'DESC')->execute();
    $nodes = Node::loadMultiple($nids);
    reset($nodes);
    $node = empty($nodes) ? null : $nodes[array_key_first($nodes)];

    if (empty($node)) {
      $title = 'Can\'t find the County Impact Report';
    } else {
      $params = county_impact_report_build_params($node);
      $params['key'] = 'county_impact_report_to_reed';
      $params['to'] = $params['advancement_specialist'];
      $params['reply'] = 'Content Editor <' . $params['content_editor'] . '>';
      $params['subject'] = 'Review - ' . $params['title'];

      $title = 'Regional Director Approval Page';
      $results = '<h3>' . $node->getTitle() . '</h3>';
      \Drupal::messenger()->addMessage('Sending email to Advancement Specialist');
      county_impact_report_send_mail($params);

      $params['subject'] = 'Approval Link for ' . $params['title'];
      $params['message'] = 'The approval link for ' . $params['title'] . ' is ' . \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl() . '/advancement_specialist_approval/'
        . 'Only use this link for the FINAL approval';
      $params['reply'] = 'Extension Web <extensionweb@iastate.edu>';

      county_impact_report_send_mail($params);
    }

    $element = array(
      '#title' => $title,
      '#markup' => $results,
    );

    return $element;
  }

  public function advancement_specialist()
  {
    // Do NOT cache the events details page
    \Drupal::service('page_cache_kill_switch')->trigger();

    $results = '';
    $title = 'Error';
    $nids = \Drupal::entityQuery('node')->accessCheck(false)->condition('type', 'county_impact_report')->sort('created', 'DESC')->execute();
    $nodes = Node::loadMultiple($nids);
    reset($nodes);
    $node = empty($nodes) ? null : $nodes[array_key_first($nodes)];

    if (empty($node)) {
      $title = 'Can\'t find the County Impact Report';
    } else {
      $title = 'Advancement Specialist Approval Page';
      $results = '<h3><a href="' . \Drupal::request()->getBaseUrl() . \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$node->id()) . '">' . $node->getTitle() . '</a></h3>';
      $node->setPublished();
      $node->save();
      \Drupal::messenger()->addMessage('Publishing the County Impact Report');
    }

    $element = array(
      '#title' => $title,
      '#markup' => $results,
    );

    return $element;
  }
}
