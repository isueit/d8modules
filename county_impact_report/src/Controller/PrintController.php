<?php

namespace Drupal\county_impact_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;

/**
 * Controller for print page.
 */
class PrintController extends ControllerBase {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PrintController object.
   */
  public function __construct(Token $token, EntityTypeManagerInterface $entity_type_manager) {
    $this->token = $token;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays the print page.
   */
  public function printPage(NodeInterface $node) {
    // Get site name using token
    $site_name = $this->token->replace('[site:name]');
    
    // Get the current year
    $current_year = date('Y');
    
    // Get the node's alias URL
    $path_alias_manager = \Drupal::service('path_alias.manager');
    $alias = $path_alias_manager->getAliasByPath('/node/' . $node->id());
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();
    $base_path = $request->getBasePath();
    $full_url = $base_url . $base_path . $alias;
    
    // Debug logging
    \Drupal::logger('county_impact_report')->notice('Page URL: @url', ['@url' => $full_url]);

    // Build render array
    $build = [
      '#theme' => 'county_impact_report_print',
      '#node' => $node,
      '#site_name' => $site_name,
      '#year' => $current_year,
      '#page_url' => $full_url,
      '#attached' => [
        'library' => [
          'county_impact_report/print_styles',
        ],
      ],
    ];

    return $build;
  }

}