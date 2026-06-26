<?php

namespace Drupal\newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for exporting newsletter content.
 */
class NewsletterExportController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a NewsletterExportController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Exports node content for newsletter use.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return array
   *   A render array.
   */
  public function export(NodeInterface $node) {
    // Check if user has access to view this node
    if (!$node->access('view')) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Build the node using the 'newsletter_export' view mode
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $build = $view_builder->view($node, 'newsletter_export');
    
    // Render the node
     $rendered = \Drupal::service('renderer')->renderRoot($build);
    
// Convert all relative URLs to absolute.
$base_url = \Drupal::request()->getSchemeAndHttpHost();
$html = preg_replace('/(src|href)="\/([^"]+)"/i', '$1="' . $base_url . '/$2"', $rendered);

// (Optional) run your prepareHtmlForEmail cleanup.
$html = $this->prepareHtmlForEmail($html);

    // Return a page with the HTML in a copyable format
    return [
      '#theme' => 'newsletter_export',
      '#html' => $html,
      '#node' => $node,
      '#attached' => [
        'library' => [
          'newsletter/export',
        ],
      ],
    ];
  }

  /**
   * Prepares HTML for email by inlining styles and cleaning markup.
   *
   * @param string $html
   *   The HTML to prepare.
   *
   * @return string
   *   The prepared HTML.
   */
  protected function prepareHtmlForEmail($html) {
    // Remove Drupal-specific attributes but preserve MailChimp classes
    $html = preg_replace('/\s*data-[a-z-]+="[^"]*"/i', '', $html);
    
    // Remove Drupal-specific classes but keep mcn* and other email-safe classes
    $html = preg_replace_callback('/\s*class="([^"]*)"/i', function($matches) {
      $classes = explode(' ', $matches[1]);
      // Keep only classes that start with 'mcn' or common email-safe classes
      $keep_classes = array_filter($classes, function($class) {
        return strpos($class, 'mcn') === 0 || 
               in_array($class, ['templateContainer', 'columnWrapper', 'columnContainer']);
      });
      
      if (!empty($keep_classes)) {
        return ' class="' . implode(' ', $keep_classes) . '"';
      }
      return '';
    }, $html);
    
    // Remove most Drupal IDs but preserve template IDs
    $html = preg_replace_callback('/\s*id="([^"]*)"/i', function($matches) {
      $id = $matches[1];
      // Keep only IDs that start with 'template'
      if (strpos($id, 'template') === 0) {
        return ' id="' . $id . '"';
      }
      return '';
    }, $html);
    
    // Remove empty attributes
    $html = preg_replace('/\s+>/', '>', $html);
    
    // Remove non-conditional comments (but keep MailChimp conditional comments)
    $html = preg_replace('/<!--(?!\[if)(.|\s)*?-->/', '', $html);
    
    // Convert relative image URLs to absolute
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $html = preg_replace('/src="\/([^"]+)"/i', 'src="' . $base_url . '/$1"', $html);
    
    // Trim whitespace
    $html = trim($html);
    
    return $html;
  }

}