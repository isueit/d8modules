<?php

namespace Drupal\layout_builder_base_library\Plugin\Layout;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class BaseThreeColumnsLayout extends BaseMultipleColumnsLayout {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'][] = 'layout-builder-base--three-columns';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth() {
    $options = $this->getColumnWidthOptions();
    return $this->getDefaultConfigOption('three_column_width', $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidthOptions() {
    $options = [
      'layout--column-width--default' => $this->t('33% - 33% - 33%'),
      'layout--column-width--25-50-25' => $this->t('25% - 50% - 25%'),
      'layout--column-width--25-25-50' => $this->t('25% - 25% - 50%'),
      'layout--column-width--50-25-25' => $this->t('50% - 25% - 25%'),
    ];
    $this->moduleHandler->alter('layout_builder_base_three_column_width', $options);

    return $options;
  }

}
