<?php

namespace Drupal\layout_builder_base_library\Plugin\Layout;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class BaseCustomizableColumnsLayout extends BaseMultipleColumnsLayout
{

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'][] = 'layout-builder-base--customizable-columns';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCustomizableColumnsOptions() {
    $options = [
      'layout--customizable-columns--autofit' => $this->t('Autofit'),
      'layout--customizable-columns--autofill' => $this->t('Autofill'),
      'layout--customizable-columns--2-col' => $this->t('2 Columns'),
      'layout--customizable-columns--3-col' => $this->t('3 Columns'),
      'layout--customizable-columns--4-col' => $this->t('4 Columns'),
    ];
    $this->moduleHandler->alter('layout_builder_customizable_columns', $options);

    return $options;

  }
}
