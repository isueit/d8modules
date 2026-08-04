<?php

namespace Drupal\newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Constructs a NewsletterExportController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, FileUrlGeneratorInterface $file_url_generator, ImageFactory $image_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('file_url_generator'),
      $container->get('image.factory')
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

// Image styles across the site convert derivatives to WebP, which desktop
// Outlook can't render (shows as a missing/blank image). Swap those out for
// the original, non-WebP file.
$html = $this->stripWebpImageStyles($html);

// Staff author photos (SmugMug) are sized/circle-cropped by CSS classes
// that most email clients ignore and that prepareHtmlForEmail() strips
// anyway. Bake the equivalent sizing into inline styles before that happens.
$html = $this->inlineStaffAuthorImageStyles($html);

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
   * Inlines the sizing/crop styling for staff author (SmugMug) photos.
   *
   * On the site, ".isueo-staff-author--img { width:75px; height:75px;
   * border-radius:100%; overflow:hidden; ... }" plus
   * ".isueo-staff-author--img img.staff_profile_smugmug { max-width:100%; }"
   * scale the SmugMug photo down to the container width and clip anything
   * past 75px tall. Neither rule survives into the export (email clients
   * mostly ignore external/embedded stylesheets, and prepareHtmlForEmail()
   * strips the classes besides), so without this the SmugMug image renders
   * at its full fetched size. Replicate the same sizing with inline styles.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with staff author image markup carrying inline sizing.
   */
  protected function inlineStaffAuthorImageStyles($html) {
    return preg_replace_callback(
      '#<div class="isueo-staff-author--img">\s*(<img\b[^>]*\bclass="[^"]*\bstaff_profile_smugmug\b[^"]*"[^>]*/?>)\s*</div>#i',
      function (array $matches) {
        $img = preg_replace('/\s(width|height)="[^"]*"/i', '', $matches[1]);
        $img = preg_replace('/<img\b/i', '<img width="75" style="width:75px; height:auto; display:block;"', $img, 1);
        return '<div style="width:75px; height:75px; border-radius:50%; overflow:hidden; display:inline-block;">' . $img . '</div>';
      },
      $html
    );
  }

  /**
   * Rewrites WebP image style derivatives to email-safe, resized copies.
   *
   * Most of the site's image styles include a "Convert to WebP" effect,
   * which is great for browsers but unsupported by desktop Outlook's
   * rendering engine, so those images come through blank in an exported
   * newsletter. Regenerate the same derivative minus the WebP conversion,
   * so the image is still resized down (not the full-resolution original)
   * but saved as a normal JPEG/PNG that every email client can display.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with WebP style derivative URLs rewritten.
   */
  protected function stripWebpImageStyles($html) {
    return preg_replace_callback(
      '#(["\'])([^"\']*?/files/styles/([^/"\']+)/public/([^"\']+?)\.webp)(?:\?[^"\']*)?\1#i',
      function (array $matches) {
        $url = $this->getEmailSafeDerivativeUrl($matches[3], $matches[4]) ?? $this->getOriginalFileUrl($matches[2]);
        return $matches[1] . $url . $matches[1];
      },
      $html
    );
  }

  /**
   * Builds an absolute URL to the un-styled original file.
   *
   * Used as a fallback when an email-safe derivative can't be generated.
   *
   * @param string $webp_url
   *   The full WebP derivative URL matched from the HTML.
   *
   * @return string
   *   The absolute URL to the original file.
   */
  protected function getOriginalFileUrl($webp_url) {
    $url = preg_replace('#/files/styles/[^/"\']+/public/#', '/files/', $webp_url);
    return preg_replace('/\.webp$/i', '', $url);
  }

  /**
   * Regenerates an image style's derivative without its WebP conversion.
   *
   * @param string $style_name
   *   The image style machine name (e.g. "large").
   * @param string $relative_path
   *   The file path relative to the public:// stream, without extension
   *   changes applied by the style (e.g. "images/photo.jpeg").
   *
   * @return string|null
   *   An absolute URL to the resized, non-WebP file, or NULL if it
   *   couldn't be generated (missing style, missing source file, etc.).
   */
  protected function getEmailSafeDerivativeUrl($style_name, $relative_path) {
    $style = $this->entityTypeManager->getStorage('image_style')->load($style_name);
    if (!$style) {
      return NULL;
    }

    // The captured path comes straight out of a URL, so it's still
    // percent-encoded (e.g. "%20", "%27") — decode before touching the
    // filesystem.
    $relative_path = rawurldecode($relative_path);

    $original_uri = 'public://' . $relative_path;
    if (!file_exists($original_uri)) {
      return NULL;
    }

    $destination_uri = 'public://newsletter-export/' . $style_name . '/' . $relative_path;

    if (!file_exists($destination_uri)) {
      $this->fileSystem->prepareDirectory($this->fileSystem->dirname($destination_uri), FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      $image = $this->imageFactory->get($original_uri);
      if (!$image->isValid()) {
        return NULL;
      }

      // Apply every effect from the style except the WebP conversion, so
      // the image is still resized/cropped the same way.
      foreach ($style->getEffects() as $effect) {
        if ($effect->getPluginId() === 'image_convert') {
          continue;
        }
        $effect->applyEffect($image);
      }

      if (!$image->save($destination_uri)) {
        return NULL;
      }
    }

    return $this->fileUrlGenerator->generateAbsoluteString($destination_uri);
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