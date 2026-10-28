<?php

namespace Drupal\layout_builder_base_library\Plugin\Layout;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class BaseFourColumnsLayout extends BaseMultipleColumnsLayout {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'][] = 'layout-builder-base--four-columns';
    return $build;
  }

}
