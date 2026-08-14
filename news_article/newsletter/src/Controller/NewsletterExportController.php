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

    // Referenced content (articles, staff profiles) that's unpublished
    // shouldn't appear in the export at all — regardless of whether the
    // person exporting happens to have permission to view unpublished
    // content. Normal entity access checks would let it through for them,
    // so this is filtered explicitly rather than relying on that.
    $node = $this->excludeUnpublishedReferences($node);

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

// Staff author photos (SmugMug) are sized by CSS classes that most email
// clients ignore and that prepareHtmlForEmail() strips anyway. Bake the
// equivalent sizing into inline styles before that happens.
$html = $this->inlineStaffAuthorImageStyles($html);

// The photo and name/title sit side by side on the site via flexbox,
// which Outlook doesn't support — lay it out as a table instead.
$html = $this->layoutStaffAuthorSideBySide($html);

// "Create content" paragraphs put any image the editor embedded in the
// WYSIWYG body wherever they happened to place it in the text. Move it to
// the front, matching the image-title-description order article teasers
// already use.
$html = $this->reorderCreateContentImage($html);

// Turn the plain "Read more" links into colored, rounded buttons matching
// the banner (green for 4-H, red otherwise).
$html = $this->convertReadMoreLinksToButtons($html, $this->getBrandColor($node));

// Condense the "In This Issue" bullet list (from the featured-content
// paragraph) into a single "In this issue: ..." summary line, relocated to
// sit right under the banner instead of further down the page.
$html = $this->condenseInThisIssueNav($html);

// Give otherwise-unstyled links the brand link color instead of the
// email client's default blue.
$html = $this->applyDefaultLinkColor($html);

// (Optional) run your prepareHtmlForEmail cleanup.
$html = $this->prepareHtmlForEmail($html);

// Story thumbnails were sized for the old two-column layout; stretch them
// to fill the single-column width instead. This uses an MSO conditional
// comment containing what looks like (but isn't) a second nested comment
// ("<!--[if !mso]><!-->"), which prepareHtmlForEmail()'s own comment
// stripping misreads as an unrelated comment to strip — so this has to
// run after that, not before.
$html = $this->makeStoryImagesFullWidth($html);

// These tables are all layout scaffolding, not real tabular data, which
// Outlook's own accessibility checker flags for missing header rows.
// role="presentation" is the standard fix — it tells assistive tech to
// skip announcing it as a table rather than adding meaningless header
// cells. Scoped to tables with both cellpadding and cellspacing set (our
// own construction pattern) so a genuine data table an editor pastes into
// a WYSIWYG body isn't mistakenly stripped of its real semantics. Runs
// last since several earlier steps (staff byline layout, the "This
// month" summary) generate their own new tables.
$html = preg_replace('/<table\b(?=[^>]*\bcellpadding=)(?=[^>]*\bcellspacing=)/i', '<table role="presentation"', $html);

    // renderRoot() above collapses the node's render array into a plain
    // string, discarding the cache tags/contexts that would normally bubble
    // up from it. Without an explicit #cache here, Dynamic Page Cache has
    // nothing to invalidate on and would serve this response forever,
    // regardless of subsequent edits to the node, its content, or this
    // export logic itself.
    return [
      '#theme' => 'newsletter_export',
      '#html' => $html,
      '#node' => $node,
      '#attached' => [
        'library' => [
          'newsletter/export',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Strips field_newsletter_content items that reference unpublished
   * content, so they're excluded from the export unconditionally.
   *
   * "existing_content" paragraphs reference a node via
   * field_existing_content_search; "staff_information" paragraphs
   * reference one via field_staff_selector. Without this, an editor with
   * "view unpublished content" (e.g. an admin) would see — and could
   * accidentally send out — a newsletter including a staff profile or
   * article that isn't actually published yet.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The newsletter node.
   *
   * @return \Drupal\node\NodeInterface
   *   The node (a clone, if anything needed excluding) with unpublished
   *   references removed from field_newsletter_content.
   */
  protected function excludeUnpublishedReferences(NodeInterface $node) {
    if (!$node->hasField('field_newsletter_content') || $node->get('field_newsletter_content')->isEmpty()) {
      return $node;
    }

    $referencing_fields = [
      'existing_content' => 'field_existing_content_search',
      'staff_information' => 'field_staff_selector',
    ];

    $keep_deltas = [];
    foreach ($node->get('field_newsletter_content') as $delta => $item) {
      $paragraph = $item->entity;
      if (!$paragraph) {
        continue;
      }

      $reference_field = $referencing_fields[$paragraph->bundle()] ?? NULL;
      $referenced_entity = $reference_field && $paragraph->hasField($reference_field)
        ? $paragraph->get($reference_field)->entity
        : NULL;

      if ($referenced_entity instanceof NodeInterface && !$referenced_entity->isPublished()) {
        continue;
      }

      $keep_deltas[] = $delta;
    }

    if (count($keep_deltas) === $node->get('field_newsletter_content')->count()) {
      return $node;
    }

    $node = clone $node;
    $values = $node->get('field_newsletter_content')->getValue();
    $node->set('field_newsletter_content', array_values(array_intersect_key($values, array_flip($keep_deltas))));

    return $node;
  }

  /**
   * Determines the newsletter's brand color from its selected banner image.
   *
   * Mirrors the "hero_fallback_color" logic in
   * node--newsletter--newsletter-export.html.twig: the 4-H banners are
   * green, the general one is ISU red. Colors match the theme's
   * .btn-outline-success/-danger (base.css).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The newsletter node.
   *
   * @return string
   *   A hex color.
   */
  protected function getBrandColor(NodeInterface $node) {
    if (!$node->hasField('field_newsletter_hero') || $node->get('field_newsletter_hero')->isEmpty()) {
      return '#c8102e';
    }

    $hero_paragraph = $node->get('field_newsletter_hero')->entity;
    if (!$hero_paragraph || !$hero_paragraph->hasField('field_newsletter_featured_image') || $hero_paragraph->get('field_newsletter_featured_image')->isEmpty()) {
      return '#c8102e';
    }

    $image_value = $hero_paragraph->get('field_newsletter_featured_image')->value;
    return in_array($image_value, ['4h_dark', '4h_light'], TRUE) ? '#008540' : '#c8102e';
  }

  /**
   * Builds the inline style for an email-safe button matching the theme's
   * .btn-outline-danger/.btn-outline-success (base.css): transparent
   * background, colored border + text, pill-shaped corners.
   *
   * @param string $color
   *   The border/text color (hex).
   *
   * @return string
   *   A CSS style attribute value.
   */
  protected function getOutlineButtonStyle($color) {
    // margin-top so the button doesn't run flush against whatever
    // preceded it (e.g. Outlook Web zeroes out the default browser margin
    // a <p> tag would otherwise have, leaving nothing between them).
    return 'display:inline-block; margin-top:10px; background-color:transparent !important; color:' . $color . ' !important; border:1px solid ' . $color . ' !important; text-decoration:none; padding:6px 16px; border-radius:20px; font-size:14px; font-weight:bold;';
  }

  /**
   * Turns the theme's "Read more" links into email-safe, colored buttons.
   *
   * iastate_theme_preprocess_links() (iastate_theme.theme) styles the node
   * teaser's automatic "read more" link as
   * `<a class="btn btn-sm btn-outline-danger" title="...">Read more<span
   * class="visually-hidden"> about ...</span></a>` — an outlined link that
   * depends on site CSS most email clients ignore, plus a visually-hidden
   * span for the full accessible label that prepareHtmlForEmail() would
   * otherwise strip along with every other stripped-down class. Replace it
   * with an inline-styled button (colored, rounded corners like the site's
   * .btn), moving the full label to aria-label so it's still announced to
   * screen readers without being visible.
   *
   * @param string $html
   *   The HTML to process.
   * @param string $color
   *   The button's background color (hex).
   *
   * @return string
   *   The HTML with "Read more" links converted to buttons.
   */
  protected function convertReadMoreLinksToButtons($html, $color) {
    // Match the whole "toolbar" > <ul> > <li> > <a> wrapper Drupal core
    // renders the read-more link in, not just the <a> itself — leaving the
    // <ul>/<li> behind gives it a default browser bullet + list indent
    // (Outlook Web renders these like a real browser would, unlike desktop
    // Outlook's Word engine), which looked like a huge padding/margin
    // around the button. There's nothing else in that toolbar for a
    // teaser, so drop the wrapper entirely.
    return preg_replace_callback(
      '#<div\b[^>]*\brole="toolbar"[^>]*>\s*<ul[^>]*>\s*<li[^>]*>\s*<a\b[^>]*\bclass="[^"]*\bbtn-outline-danger\b[^"]*"[^>]*>\s*Read more\s*<span[^>]*>.*?</span>\s*</a>\s*</li>\s*</ul>\s*</div>#is',
      function (array $matches) use ($color) {
        preg_match('/\bhref="([^"]*)"/i', $matches[0], $href_match);
        preg_match('/\btitle="([^"]*)"/i', $matches[0], $title_match);
        $href = $href_match[1] ?? '#';
        $aria_label = !empty($title_match[1]) ? 'Read more about ' . $title_match[1] : 'Read more';

        return '<a href="' . $href . '" aria-label="' . $aria_label . '" style="' . $this->getOutlineButtonStyle($color) . '">Read more</a>';
      },
      $html
    );
  }

  /**
   * Moves an image embedded in a "create content" paragraph's WYSIWYG body
   * to the front, ahead of the title — matching the image-title-description
   * order article teasers (existing_content) already use.
   *
   * paragraph--create-content.html.twig renders
   * `<div class="paragraph paragraph--type--create-content ...">
   * <h2>TITLE</h2>BODY</div>` — any image the editor placed in the body
   * ends up wherever they left it, rather than consistently first. The
   * body itself can contain arbitrary nested markup (including its own
   * divs), so rather than trying to find this wrapper's own balanced
   * closing `</div>`, this scans up to whichever comes first: the next
   * paragraph's own wrapper, this controller's own styled divider between
   * content items, or the end of the string. That's a look-ahead, not a
   * captured/consumed boundary, so it's left untouched in the output —
   * this only reaches in and relocates the image.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with each create-content paragraph's image moved first.
   */
  protected function reorderCreateContentImage($html) {
    return preg_replace_callback(
      '#(<div\b[^>]*\bclass="[^"]*\bparagraph--type--create-content\b[^"]*"[^>]*>)(.*?)(?=<div\b[^>]*\bclass="[^"]*\bparagraph--type--|<hr\b[^>]*\bstyle=|$)#is',
      function (array $matches) {
        [, $open_tag, $inner] = $matches;
        if (!preg_match('#<img\b[^>]*/?>#i', $inner, $img_match)) {
          return $matches[0];
        }

        $inner_without_image = preg_replace('#<img\b[^>]*/?>#i', '', $inner, 1);
        return $open_tag . $img_match[0] . $inner_without_image;
      },
      $html
    );
  }

  /**
   * Condenses the "In This Issue" bullet list into a one-line summary,
   * relocated to right after the banner (marked by the "<!-- END BANNER
   * -->" comment in the twig template).
   *
   * paragraph--featured-content.html.twig renders this as
   * `<div><h3>In This Issue</h3><ul><li><a href="#id">Text</a></li>...
   * </ul></div>` wherever the featured-content paragraph sits in the
   * layout — this pulls that specific block out (leaving the paragraph's
   * title/body untouched) and drops a "In this issue: A, B, and C" sentence
   * in its place at the top of the page instead.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with the nav condensed and relocated.
   */
  protected function condenseInThisIssueNav($html) {
    $summary = '';

    $html = preg_replace_callback(
      '#<div\b[^>]*>\s*<h[23]\b[^>]*>In This Issue</h[23]>\s*<ul\b[^>]*>(.*?)</ul>\s*</div>#is',
      function (array $matches) use (&$summary) {
        preg_match_all('#<a\b[^>]*\bhref="([^"]+)"[^>]*>\s*([^<]*?)\s*</a>#is', $matches[1], $links, PREG_SET_ORDER);
        if (empty($links)) {
          return '';
        }

        $count = count($links);
        $html_links = '';
        foreach ($links as $index => $link) {
          $item = '<a href="' . $link[1] . '" style="color:#7c2529;">' . trim($link[2]) . '</a>';
          if ($index > 0) {
            $item = ($index === $count - 1 ? ($count > 2 ? ', and ' : ' and ') : ', ') . $item;
          }
          $html_links .= $item;
        }

        $summary = '<table border="0" cellpadding="0" cellspacing="0" width="600"><tr><td style="padding: 15px 18px; font-size: 14px; color: #555555;"><strong style="color:#202020;">In this issue:</strong> ' . $html_links . '</td></tr></table>';
        return '';
      },
      $html
    );

    if ($summary !== '') {
      $html = str_replace('<!-- END BANNER -->', '<!-- END BANNER -->' . $summary, $html);
    }

    return $html;
  }

  /**
   * Inlines the sizing/crop styling for staff author (SmugMug) photos, and
   * drops the placeholder image entirely when a staff member has none.
   *
   * On the site, ".isueo-staff-author--img" is a 75x75, overflow:hidden,
   * border-radius:100% wrapper DIV that clips the photo to a circle while
   * the image itself only gets its WIDTH constrained (letting it keep its
   * natural proportions rather than being stretched). Outlook doesn't
   * reliably support overflow:hidden clipping on a plain <div> the way
   * browsers do, so a wrapper div here doesn't actually crop anything —
   * it just adds markup around a photo that renders at its natural,
   * non-circular proportions regardless. Rather than ship a wrapper that
   * doesn't do anything, this only sizes the image by width (height left
   * to scale proportionally) and leaves it non-circular.
   *
   * Drupal's default field theming wraps the formatter's markup in its own
   * "field"/"field__item" divs, but exactly how many — it's turned out to
   * vary (sometimes just one, sometimes more) rather than the single fixed
   * depth this used to assume. Counting closing </div> tags to find the
   * end of that wrapper is fragile against that: get the count wrong and a
   * non-greedy match sails past the real boundary looking for the next
   * place two closing divs happen to line up, which can be well past the
   * staff member's name/title — deleting it along with everything else in
   * between. Matching the <img> itself directly, regardless of whatever
   * divs happen to wrap it, sidesteps the whole problem. The wrapper divs
   * are left in place (layoutStaffAuthorSideBySide() strips them
   * afterward) — harmless since they carry no surviving styling once
   * prepareHtmlForEmail() strips classes anyway.
   *
   * When a staff member has no photo, SmugmugIdFormatter renders a blank
   * placeholder image (class "staff_profile_blank_image") that's invisible
   * on the site but has nothing to fill it in for email, so drop it
   * outright rather than leaving an empty box behind.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with staff author image markup fixed up for email.
   */
  protected function inlineStaffAuthorImageStyles($html) {
    return preg_replace_callback(
      '#<img\b[^>]*\bclass="[^"]*\bstaff_profile_smugmug\b[^"]*"[^>]*/?>#i',
      function (array $matches) {
        if (strpos($matches[0], 'staff_profile_blank_image') !== FALSE) {
          return '';
        }

        $img = preg_replace('/\s(width|height)="[^"]*"/i', '', $matches[0]);
        return preg_replace(
          '/<img\b/i',
          '<img width="75" style="width:75px; height:auto; display:block;"',
          $img,
          1
        );
      },
      $html
    );
  }

  /**
   * Lays out the staff author byline (photo + name/title) side by side.
   *
   * On the site, ".isueo-staff-author { display:flex; align-items:center;
   * }" sits the photo and name/title next to each other — Outlook doesn't
   * support flexbox at all, so once that class is stripped for email the
   * divs just stack, putting the name/title underneath the photo instead
   * of beside it. This rebuilds it as a two-column table instead.
   *
   * Must run after inlineStaffAuthorImageStyles(), which resizes the photo
   * in place — but doesn't assume anything about how many divs still wrap
   * it (that's turned out to vary). Rather than count closing divs to find
   * where the ".isueo-staff-author--img" wrapper ends (fragile — see
   * inlineStaffAuthorImageStyles()), this captures everything up to the
   * next known landmark, the ".isueo-staff-author--body" div, and strips
   * any leftover <div> tags from that capture so only the bare (already
   * correctly sized) <img> — or nothing, if there wasn't one — ends up in
   * the table cell.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with the staff byline laid out as a table.
   */
  protected function layoutStaffAuthorSideBySide($html) {
    return preg_replace_callback(
      '#<div\b[^>]*\bclass="[^"]*\bisueo-staff-author\b(?!-)[^"]*"[^>]*>\s*(.*?)(?=<div\b[^>]*\bclass="[^"]*\bisueo-staff-author--body\b)<div\b[^>]*\bclass="[^"]*\bisueo-staff-author--body\b[^"]*"[^>]*>\s*<div\b[^>]*\bclass="[^"]*\bisueo-staff-author--header\b[^"]*"[^>]*>\s*(<p>.*?</p>)\s*</div>\s*</div>\s*</div>#is',
      function (array $matches) {
        $img_html = trim(preg_replace('#</?div\b[^>]*>#i', '', $matches[1]));
        $img_cell = $img_html !== '' ? '<td valign="top" width="75" style="padding-right: 12px;">' . $img_html . '</td>' : '';
        // Margin-top so this doesn't run right up against whatever came
        // before it (e.g. an article's "Read more" button) — the divider
        // that normally provides that breathing room is deliberately
        // deferred past a staff profile following an article, so this
        // needs its own spacing rather than relying on that.
        return '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 15px;"><tr>' . $img_cell . '<td valign="top">' . $matches[2] . '</td></tr></table>';
      },
      $html
    );
  }

  /**
   * Makes story thumbnail images span the full content width, scaled
   * proportionally (no height cap).
   *
   * These come through with a fixed width/height (e.g. "220x147", sized for
   * the old two-column layout where they sat beside the text) and no inline
   * style, so they no longer fill the single-column layout.
   *
   * No height is set at all — only width, with height left to scale
   * proportionally — because every technique tried to additionally cap the
   * height failed in some real client:
   * - Forcing both width and a fixed height, relying on `object-fit:cover`
   *   to prevent distortion, stretches the image in any client that
   *   honors the forced dimensions without also supporting object-fit.
   * - Wrapping the (width-only-sized) image in a fixed-max-height,
   *   overflow:hidden container instead avoids distortion, but in a
   *   client that enforces the container's max-height for layout purposes
   *   without actually clipping the overflowing image, the image spills
   *   past its allotted space and overlaps whatever comes after it.
   *
   * Outlook for Mac hits *both* failure modes (confirmed by testing) —
   * it neither supports object-fit nor reliably clips overflow — and
   * doesn't respond to MSO conditional comments either (that targets
   * classic Windows Outlook specifically, not Mac), so there's no way to
   * single it out and give it special-case markup. Full width, no cap,
   * is the one approach that's correct (no stretch, no overlap) in every
   * client, full stop — at the cost of images no longer sharing a
   * uniform height.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with story images resized to span the full width.
   */
  protected function makeStoryImagesFullWidth($html) {
    return preg_replace_callback(
      '#<img\b[^>]*\bloading="lazy"[^>]*>#i',
      function (array $matches) {
        $stripped_img = preg_replace('/\s(width|height)="[^"]*"/i', '', $matches[0]);

        return preg_replace(
          '/<img\b/i',
          '<img width="100%" style="width:100% !important; height:auto !important; display:block !important;"',
          $stripped_img,
          1
        );
      },
      $html
    );
  }

  /**
   * Colors otherwise-unstyled hyperlinks with the brand link color.
   *
   * Links Drupal renders plainly (article titles, staff names, body-text
   * links, etc.) inherit whatever default blue the email client uses.
   * Anything we've deliberately colored ourselves (buttons, the
   * "View all events" link) already carries its own inline `style`, so
   * only tags missing one are touched here.
   *
   * @param string $html
   *   The HTML to process.
   *
   * @return string
   *   The HTML with unstyled links given the brand link color.
   */
  protected function applyDefaultLinkColor($html) {
    return preg_replace_callback(
      '#<a\b((?:(?!\bstyle=)[^>])*)>#i',
      function (array $matches) {
        return '<a' . $matches[1] . ' style="color:#7c2529;">';
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
    // Screen-reader-only labels (e.g. a media field's "Image" label) are
    // invisible on the site via CSS that clips/hides them, not markup that
    // disappears on its own — since the class gets stripped below and no
    // external CSS survives into email anyway, drop these elements (and
    // their text) entirely rather than let them show up as visible junk.
    $html = preg_replace('#<(div|span)\b[^>]*\bclass="[^"]*\b(?:sr-only|visually-hidden)\b[^"]*"[^>]*>.*?</\1>#is', '', $html);

    // The "In This Issue" nav links to anchor ids on the section headings
    // further down the page — collect those before the id-stripping pass
    // below removes them as collateral damage.
    preg_match_all('/href="#([^"]+)"/i', $html, $anchor_matches);
    $referenced_ids = array_flip($anchor_matches[1]);

    // Remove Drupal-specific attributes but preserve MailChimp classes
    $html = preg_replace('/\s*data-[a-z-]+="[^"]*"/i', '', $html);

    // Remove Drupal-specific classes but keep mcn* and other email-safe classes
    $html = preg_replace_callback('/\s*class="([^"]*)"/i', function($matches) {
      $classes = explode(' ', $matches[1]);
      // Keep only classes that start with 'mcn' or common email-safe classes
      $keep_classes = array_filter($classes, function($class) {
        return strpos($class, 'mcn') === 0 ||
               in_array($class, ['templateContainer', 'columnWrapper', 'columnContainer', 'newsletter-export-force-white']);
      });

      if (!empty($keep_classes)) {
        return ' class="' . implode(' ', $keep_classes) . '"';
      }
      return '';
    }, $html);

    // Remove most Drupal IDs but preserve template IDs and anchor targets
    // that an in-page link (like the "In This Issue" nav) points to.
    $html = preg_replace_callback('/\s*id="([^"]*)"/i', function($matches) use ($referenced_ids) {
      $id = $matches[1];
      // Keep only IDs that start with 'template', or that are an anchor target.
      if (strpos($id, 'template') === 0 || isset($referenced_ids[$id])) {
        return ' id="' . $id . '"';
      }
      return '';
    }, $html);

    // Remove empty attributes
    $html = preg_replace('/\s+>/', '>', $html);
    
    // Remove non-conditional comments, but keep MSO conditional comments —
    // both the single-comment "<!--[if mso]>...<![endif]-->" form and the
    // closing half of the two-comment "hide from mso, show to everyone
    // else" form, "<!--<![endif]-->" (its content starts with "<![endif]"
    // rather than "[if", so it needs its own exception here too).
    $html = preg_replace('/<!--(?!\[if|<!\[endif\])(.|\s)*?-->/', '', $html);
    
    // Convert relative image URLs to absolute
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $html = preg_replace('/src="\/([^"]+)"/i', 'src="' . $base_url . '/$1"', $html);
    
    // Trim whitespace
    $html = trim($html);
    
    return $html;
  }

}