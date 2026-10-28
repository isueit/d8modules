<?php

namespace Drupal\layout_builder_base_library\Plugin\Layout;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class BaseTwoColumnsLayout extends BaseMultipleColumnsLayout {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'][] = 'layout-builder-base--two-columns';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth() {
    $options = $this->getColumnWidthOptions();
    return $this->getDefaultConfigOption('two_column_width', $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidthOptions() {
    $options = [
      'layout--column-width--default' => $this->t('50% - 50%'),
      'layout--column-width--33-67' => $this->t('33% - 67%'),
      'layout--column-width--67-33' => $this->t('67% - 33%'),
      'layout--column-width--25-75' => $this->t('25% - 75%'),
      'layout--column-width--75-25' => $this->t('75% - 25%'),
    ];
    $this->moduleHandler->alter('layout_builder_base_two_column_width', $options);

    return $options;
  }

}
