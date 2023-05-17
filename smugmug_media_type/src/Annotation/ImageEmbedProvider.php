<?php

namespace Drupal\smugmug_media_type\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ImageEmbedProvider item annotation object.
 *
 * @Annotation
 */
class ImageEmbedProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
